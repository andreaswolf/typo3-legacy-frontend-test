<?php
declare(strict_types = 1);

namespace FGTCLB\HttpApi\Tests\Unit\Http;

use FGTCLB\HttpApi\Http\ApiResponse;
use FGTCLB\HttpApi\Resource\ResourceInterface;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Testcase for FGTCLB\HttpApi\Http\ApiResponse
 */
class ApiResponseTest extends UnitTestCase
{
    /**
     * @test
     */
    public function buildsSuccessResponse()
    {
        $resource = new class implements ResourceInterface {
            /**
             * Convert this resource to a plain array
             *
             * @return array
             */
            public function toArray(): array
            {
                return ['test' => 'foo'];
            }
        };
        $response = ApiResponse::success($resource);
        $expected = [
            'success' => true,
            'data' => [
                'test' => 'foo',
            ],
        ];

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($expected, json_decode((string)$response->getBody(), true));
    }

    /**
     * @test
     */
    public function buildsErrorResponse()
    {
        $response = ApiResponse::error('Something failed');
        $expected = [
            'success' => false,
            'error' => 'Something failed',
        ];

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals($expected, json_decode((string)$response->getBody(), true));

        $this->assertEquals(403, ApiResponse::error('Access denied', 403)->getStatusCode());
    }
}
