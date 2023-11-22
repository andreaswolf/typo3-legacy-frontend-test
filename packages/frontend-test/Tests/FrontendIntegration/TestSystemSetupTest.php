<?php
declare(strict_types = 1);

namespace AndreasWolf\FrontendTest\Tests\FrontendIntegration;

use AndreasWolf\FrontendTest\Tests\FrontendIntegration\Fixtures\ExampleTestCase;
use AndreasWolf\OracleConnector\Tests\Integration\TestUsersRepository;
use FGTCLB\HttpApi\Tests\Functional\ApiTestTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Tests setup of a test TYPO3 instance inside public/typo3temp/var/tests/.
 *
 * Because this also does an HTTP request to the frontend, we need to run this with a full-blown docker-compose
 * environment + Træfik proxy, which is why this is not in Tests/Functional/
 *
 * @covers \AndreasWolf\FrontendTest\Service\TestSystemSetup
 */
class TestSystemSetupTest extends FunctionalTestCase
{
    use ApiTestTrait;

    /** @var string[] */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/http_api',
        'typo3conf/ext/frontend_test',
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->importDataSet(__DIR__ . '/Fixtures/BasicPagetree.xml');
    }

    /** @test */
    public function setupTestSystemCreatesTestFolder(): string
    {
        $testCase = ExampleTestCase::class;

        $testSystemId = $this->getTestSystemIdForTestCase($testCase);
        $targetFolder = sprintf('%s/typo3temp/var/tests/functional-%s/', ORIGINAL_ROOT, $testSystemId);

        GeneralUtility::rmdir($targetFolder);

        $result = $this->runApiRequest(
            (new ServerRequest('POST', '/api/v1/api-tests/setup'))->withParsedBody([
                'testCase' => $testCase,
            ])
        );

        static::assertInstanceOf(Response::class, $result);
        static::assertSame(200, $result->getStatusCode());
        $decodedBody = json_decode($result->getBody()->getContents(), true);
        static::assertIsArray($decodedBody);
        static::assertTrue($decodedBody['success']);

        static::assertSame($testSystemId, $decodedBody['message']);

        static::assertFileExists($targetFolder);
        static::assertFileExists($targetFolder . '/.htaccess');

        return $testSystemId;
    }

    /**
     * @test
     * @depends setupTestSystemCreatesTestFolder
     */
    public function frontendPageInTestSystemCanBeRendered(string $testSystemId): void
    {
        $result = $this->performFrontendGetRequest(
            $testSystemId,
            'index.php',
            [
                'id' => 1,
                'no_cache' => 1,
            ]
        );

        static::assertInstanceOf(\GuzzleHttp\Psr7\Response::class, $result);
        static::assertSame(200, $result->getStatusCode());
        $body = $result->getBody()->getContents();
        static::assertSame('Hello World', $body);
    }

    /**
     * @test
     * @depends setupTestSystemCreatesTestFolder
     */
    public function frontendUserIsLoggedInWhenLoginParametersAreGivenInRequest(string $testSystemId): void
    {
        $emailAddress = 'fes-test1@a-w.io';
        $password = GeneralUtility::makeInstance(TestUsersRepository::class)->getPassword($emailAddress);

        $result = $this->performFrontendGetRequest(
            $testSystemId,
            'index.php',
            [
                'id' => 2,
                // logintype/user/pass triggers the standard frontend login. "pid" is the folder ID where the users
                // are searched
                'logintype' => 'login',
                'user' => $emailAddress,
                'pass' => $password,
                'pid' => 2,
            ]
        );

        static::assertInstanceOf(\GuzzleHttp\Psr7\Response::class, $result);
        static::assertSame(200, $result->getStatusCode());
        $body = $result->getBody()->getContents();
        static::assertSame('50000101', $body);
    }

    /**
     * @param array<string, string|int|null> $queryParameters
     */
    private function performFrontendGetRequest(string $testSystemId, string $uri, array $queryParameters): ResponseInterface
    {
        $client = new Client([
            // disable SSL certificate verification, since libcURL does not correctly use SNI, and we must use the
            // container name instead of the actual hostname for connecting (and supply the desired hostname via the
            // Host: header)
            'verify' => false,
        ]);

        // this is a URL our tools container (and possibly a browser container) can reach. This can also have a different
        // host name than $HOST, since we supply the desired hostname via the Host: header
        $frontendUrl = getenv('FRONTEND_INTEGRATION_TEST_URL');
        if ($frontendUrl === false) {
            static::fail('Environment variable FRONTEND_INTEGRATION_TEST_URL must be set');
        }
        $uri = (new Uri($frontendUrl))
            ->withPath(sprintf('/_ft-%s/%s', $testSystemId, $uri))
            ->withQuery(http_build_query($queryParameters));

        return $client->get($uri, [
            'headers' => [
                'Host' => getenv('HOST'),
            ]
        ]);
    }

    private function getTestSystemIdForTestCase(string $testCase): string
    {
        return substr(sha1($testCase), 0, 7);
    }
}
