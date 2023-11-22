<?php
declare(strict_types = 1);

namespace FGTCLB\HttpApi\Tests\Unit\Http;

use FGTCLB\HttpApi\Http\ApiParameters;
use FGTCLB\HttpApi\Http\UndefinedApiParameterException;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Testcase for FGTCLB\HttpApi\Http\ApiParameters
 */
class ApiParametersTest extends UnitTestCase
{
    /**
     * @test
     */
    public function returnsParameters()
    {
        $apiParameters = new ApiParameters([
            'foo' => 1,
            'bar' => 'qux',
        ]);

        $this->assertEquals(1, $apiParameters->get('foo'));
        $this->assertEquals('qux', $apiParameters->get('bar'));
    }

    /**
     * @test
     */
    public function throwsExceptionOnUndefinedParameter()
    {
        $apiParameters = new ApiParameters([
            'foo' => 1,
        ]);

        $this->expectException(UndefinedApiParameterException::class);

        $apiParameters->get('bar');
    }

    /**
     * @test
     * @dataProvider internalParameters
     */
    public function skipsInternalParameters(string $parameter)
    {
        $apiParameters = new ApiParameters([
            $parameter => 'foo',
        ]);

        $this->expectException(UndefinedApiParameterException::class);

        $apiParameters->get($parameter);
    }

    public function internalParameters(): array
    {
        return [
            ['_route'],
            ['_controller'],
        ];
    }
}
