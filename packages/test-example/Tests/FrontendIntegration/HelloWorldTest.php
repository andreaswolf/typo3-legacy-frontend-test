<?php
declare(strict_types=1);

namespace AndreasWolf\TestExample\Tests\FrontendIntegration;

use AndreasWolf\FrontendTest\Service\TestSystem;
use AndreasWolf\FrontendTest\Test\FrontendIntegrationTestCase;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class HelloWorldTest extends FrontendIntegrationTestCase
{
    /**
     * @param array<mixed> $data
     */
    public function __construct(string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $requestHostWithScheme = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST');
        $urlParts = parse_url((string) $requestHostWithScheme);
        $requestHost = $urlParts['host'];

        ArrayUtility::mergeRecursiveWithOverrule(
            $this->configurationToUseInTestInstance,
            [
                'SYS' => [
                    // ensure our session cookie gets set, even though we are in a subdirectory (/_ft-1234abc/ from the user's
                    // perspective, but on the server this is /typo3temp/var/tests/functional-1234abc/, which would also be set
                    // in the Cookie path, which would therefore by the client when making requests
                    'cookieDomain' => $requestHost,
                ],
            ]
        );
    }

    public function setUpTestSystem(TestSystem $system): void
    {
        parent::setUpTestSystem($system);

        $this->setUpSitesWithTestSystem($system, 1, [
            1 => __DIR__ . '/Fixtures/sitemock/config.yaml',
        ]);
    }

    protected function importTestData(): void
    {
        $this->importDataSet(__DIR__ . '/Fixtures/PageTreeWithFrontendOutput.xml');
    }
}