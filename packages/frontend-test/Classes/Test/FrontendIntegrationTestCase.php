<?php
declare(strict_types = 1);

namespace AndreasWolf\FrontendTest\Test;

use AndreasWolf\FrontendTest\FrontendTestCase;
use AndreasWolf\FrontendTest\Service\TestSystem;
use AndreasWolf\OracleConnector\Tests\Functional\OracleDatabaseTestCase;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Base class for frontend integration tests. Extend this and define test data to import, then call the API endpoint
 * to set up the test system with the extensions defined in this class and the imported data.
 */
abstract class FrontendIntegrationTestCase extends FunctionalTestCase implements FrontendTestCase
{
    public function setUpTestSystem(TestSystem $system): void
    {
        // make sure the API methods in this package are available in the built test system
        $this->testExtensionsToLoad[] = 'typo3conf/ext/frontend_test';
        $this->testExtensionsToLoad[] = 'typo3conf/ext/http_api';
        $this->testExtensionsToLoad = array_unique($this->testExtensionsToLoad);

        ArrayUtility::mergeRecursiveWithOverrule(
            $this->configurationToUseInTestInstance,
            [
                'SYS' => [
                    // This is required for the mapping from /_ft-xxxx/ to typo3temp/var/tests/functional-xxxx/ to work,
                    // see the .htaccess template in Resources/Private/Templates/TestSystemHtaccessFile.txt
                    'requestURIvar' => '_SERVER|REDIRECT_API_REQUEST_URI',
                ]
            ]
        );

        $system->setUp(
            $this->coreExtensionsToLoad,
            $this->testExtensionsToLoad,
            $this->pathsToLinkInTestInstance,
            $this->configurationToUseInTestInstance,
            $this->additionalFoldersToCreate
        );

        $this->importTestData();
    }

    /**
     * @param int $pageId
     * @param array<string|int, string> $sites A map of root page ID/site identifier to site template file
     */
    protected function setUpSitesWithTestSystem(TestSystem $system, $pageId, array $sites): void
    {
        if (empty($sites[$pageId])) {
            $sites[$pageId] = 'ntf://Frontend/site.yaml';
        }

        foreach ($sites as $identifier => $file) {
            $target = sprintf('%s/typo3conf/sites/%s/config.yaml', $system->getSystemPath(), $identifier);

            if (!file_exists($target)) {
                GeneralUtility::mkdir_deep(dirname($target));
                if (!file_exists($file)) {
                    $file = GeneralUtility::getFileAbsFileName($file);
                }
                $fileContent = file_get_contents($file);
                if ($fileContent === false) {
                    throw new \InvalidArgumentException(sprintf('Could not find template for site %s', $identifier), 1646416293);
                }
                $fileContent = str_replace('{rootPageId}', (string)$pageId, $fileContent);
                // we must use the internal URL here since TYPO3 only sees the request behind the reverse proxy, not
                // the actual public URL (for some reason…)
                $fileContent = str_replace(
                    '{baseUrl}',
                    sprintf(
                        '%s/_ft-%s/',
                        rtrim(getenv('TYPO3_URL') ?: '', '/'),
                        $system->getSystemIdentifier()
                    ),
                    $fileContent
                );
                GeneralUtility::writeFile($target, $fileContent);
            }
        }
    }

    abstract protected function importTestData(): void;
}
