<?php

declare(strict_types=1);

$typo3Version = new \TYPO3\CMS\Core\Information\Typo3Version();

$config = [
    'includes' => [],
];

if ($typo3Version->getMajorVersion() === 11) {
    $config['includes'][] = __DIR__ . '/../baseline-v11.neon';
    $config['parameters']['typo3']['requestGetAttributeMapping']['api.requiredRoles'] = 'list<\FGTCLB\HttpApi\ApiRole>';
}

return $config;
