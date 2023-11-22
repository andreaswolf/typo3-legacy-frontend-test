<?php

return [
    'frontend' => [
        'fgtclb/api/routing' => [
            'target' => \FGTCLB\HttpApi\Middleware\ApiRequestRouter::class,
            'after' => [
                'typo3/cms-frontend/authentication',
            ],
            'before' => [
                'typo3/cms-frontend/static-route-resolver',
            ],
        ],
        'fgtclb/api/resolving' => [
            'target' => \FGTCLB\HttpApi\Middleware\ApiRequestResolver::class,
            'after' => [
                'fgtclb/api/routing',
                'typo3/cms-frontend/site',
            ],
            'before' => [
                'typo3/cms-frontend/prepare-tsfe-rendering',
                'typo3/cms-frontend/page-resolver',
            ],
        ],
        'fgtclb/api/access' => [
            'target' => \FGTCLB\HttpApi\Middleware\ApiAccessGuard::class,
            'after' => [
                'fgtclb/api/routing',
            ],
            'before' => [
                'fgtclb/api/resolving',
            ],
        ],
        'fgtclb/api/tsfe' => [
            'target' => \FGTCLB\HttpApi\Middleware\TypoScriptFrontendSetup::class,
            'after' => [
                'fgtclb/api/routing',
            ],
            'before' => [
                'fgtclb/api/resolving',
            ],
        ],
    ],
];
