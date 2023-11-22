<?php
declare(strict_types = 1);

namespace FGTCLB\HttpApi\Tests\Unit\Http;

use FGTCLB\HttpApi\Http\GlobalApiRole;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class GlobalApiRoleTest extends UnitTestCase
{
    /** @test */
    public function castingRoleToStringYieldsFullRoleName(): void
    {
        static::assertSame('global:authenticated-user', (string)(new GlobalApiRole(GlobalApiRole::AUTHENTICATED_USER)));
    }
}
