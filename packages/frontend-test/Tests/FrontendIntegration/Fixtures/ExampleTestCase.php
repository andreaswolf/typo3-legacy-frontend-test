<?php
declare(strict_types = 1);

namespace AndreasWolf\FrontendTest\Tests\FrontendIntegration\Fixtures;

use AndreasWolf\FrontendTest\Test\FrontendIntegrationTestCase;

class ExampleTestCase extends FrontendIntegrationTestCase
{
    /** @var string[] */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/base',
        'typo3conf/ext/http_api',
        'typo3conf/ext/rooms',
        'typo3conf/ext/users',
    ];

    /**
     * @param mixed[] $data
     */
    public function __construct(?string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->configurationToUseInTestInstance = array_merge(
            $this->configurationToUseInTestInstance,
            [
                'EXTENSIONS' => [
                    'users' => [
                        'defaultUsergroup' => 1,
                    ],
                ],
            ]
        );
    }

    public function importTestData(): void
    {
        $this->importDataSet(__DIR__ . '/BasicPagetreeWithRoom.xml');
    }
}
