<?php
declare(strict_types = 1);

namespace FGTCLB\HttpApi\Http;

use FGTCLB\HttpApi\RolesProvider;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class GlobalApiRolesProvider implements RolesProvider
{
    public function getRolesForCurrentUser(): array
    {
        $frontendUserAspect = GeneralUtility::makeInstance(Context::class)
            ->getAspect('frontend.user');

        $userAuthenticated = $frontendUserAspect->get('isLoggedIn');

        $roles = [
            new GlobalApiRole(GlobalApiRole::ANY_USER)
        ];
        if ($userAuthenticated === true) {
            $roles[] = new GlobalApiRole(GlobalApiRole::AUTHENTICATED_USER);
        }
        return $roles;
    }
}
