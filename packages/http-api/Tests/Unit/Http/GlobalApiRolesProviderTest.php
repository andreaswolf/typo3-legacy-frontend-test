<?php
declare(strict_types = 1);

namespace FGTCLB\HttpApi\Tests\Unit\Http;

use FGTCLB\HttpApi\Http\GlobalApiRolesProvider;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class GlobalApiRolesProviderTest extends UnitTestCase
{
    use ProphecyTrait;

    /** @test */
    public function getRolesForCurrentUserReturnsAnyUserRoleIfUserIsNotAuthenticated(): void
    {
        $mockUserAspect = $this->prophesize(UserAspect::class);
        $mockUserAspect->get('isLoggedIn')->willReturn(false);

        GeneralUtility::makeInstance(Context::class)->setAspect('frontend.user', $mockUserAspect->reveal());

        $subject = new GlobalApiRolesProvider();

        $result = $subject->getRolesForCurrentUser();

        static::assertCount(1, $result);
        static::assertSame('global:any-user', (string)($result[0]));
    }

    /** @test */
    public function getRolesForCurrentUserReturnsAnyUserAndAuthenticatedUserRolesIfUserIsAuthenticated(): void
    {
        $mockUserAspect = $this->prophesize(UserAspect::class);
        $mockUserAspect->get('isLoggedIn')->willReturn(true);

        GeneralUtility::makeInstance(Context::class)->setAspect('frontend.user', $mockUserAspect->reveal());

        $subject = new GlobalApiRolesProvider();

        $result = $subject->getRolesForCurrentUser();

        static::assertCount(2, $result);
        static::assertSame('global:any-user', (string)($result[0]));
        static::assertSame('global:authenticated-user', (string)($result[1]));
    }
}
