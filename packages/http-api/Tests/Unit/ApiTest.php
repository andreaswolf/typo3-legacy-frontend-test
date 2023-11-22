<?php
declare(strict_types = 1);

namespace FGTCLB\HttpApi\Tests\Unit;

use FGTCLB\HttpApi\Api;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Testcase for FGTCLB\HttpApi\Api
 */
class ApiTest extends UnitTestCase
{
    /**
     * Tear down this testcase
     */
    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        Api::reset();
    }

    /**
     * @test
     */
    public function buildsApiForVersion()
    {
        $v1Api = Api::version(1);
        $this->assertEquals(1, $v1Api->getVersion());

        $v2Api = Api::version(2);
        $this->assertEquals(2, $v2Api->getVersion());
    }

    /**
     * @test
     */
    public function buildsApiOnce()
    {
        $api = Api::version(1);

        $this->assertSame($api, Api::version(1));
    }

    /**
     * @test
     */
    public function returnsRegisteredApis()
    {
        Api::version(1);
        Api::version(2);

        $this->assertCount(2, Api::all());
    }

    /**
     * @test
     */
    public function managesRoutes()
    {
        $api = Api::version(1);
        $api->addRoute('users', [
            'get' => 'UsersController::index',
        ]);
        $api->addRoute('posts', [
            'get' => 'PostsController::index',
            'post' => 'PostsController::store',
        ]);

        $routes = $api->getRoutes();

        $this->assertCount(3, $routes);
    }

    /**
     * @test
     */
    public function allRoutesWithPrefixesReturnsAllRoutesWithVersionPrefixed(): void
    {
        $apiV1 = Api::version(1);
        $apiV1->addRoute('foo', [
            'get' => 'FooController::index',
        ]);
        $apiV1->addRoute('bar', [
            'get' => 'BarController::list',
        ]);

        $apiV2 = Api::version(2);
        $apiV2->addRoute('foo', [
            'get' => 'FooV2Controller::index',
        ]);

        $routes = Api::allRoutesWithPrefies();

        self::assertCount(3, $routes);
    }
}
