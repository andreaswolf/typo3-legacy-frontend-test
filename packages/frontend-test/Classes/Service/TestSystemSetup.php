<?php
declare(strict_types = 1);

namespace AndreasWolf\FrontendTest\Service;

use AndreasWolf\FrontendTest\FrontendTestCase;
use Nimut\TestingFramework\Bootstrap\BootstrapFactory;
use SebastianBergmann\Template\Template;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Wrapper around the setup of a test system, i.e. a test folder in public/typo3temp/var/tests/.
 *
 * This creates a "child" TYPO3 system in this tests/ subfolder, from a running TYPO3 "mother" system.
 */
class TestSystemSetup implements SingletonInterface
{
    /**
     * Creates a test system based on the given test case class.
     *
     * This reuses the original test setup of nimut/testing-framework via a wrapper in the test case class, with some
     * alterations to account for our different setup: this setup process is called from within an active TYPO3 request,
     * while the nimut/testing-framework setup is called by the PHPUnit test runner. Therefore, we need to do some extra
     * steps here, like changing the active database name and using a different package manager.
     *
     * @see TestSystem for more details on how the setup works
     *
     * @param class-string<FrontendTestCase> $testCaseClass
     * @return string The identifier of the created test system
     */
    public function setupTestSystem(string $testCaseClass, ?string $htaccessTemplateFile): string
    {
        $bootstrap = BootstrapFactory::createBootstrapInstance();
        $bootstrap->bootstrapFunctionalTestSystem();

        $testSystem = new TestSystem($testCaseClass);

        $originalDatabaseName = $testSystem->changeDatabaseName();

        // passing in the test case name to the (PHPUnit) test case
        $testCase = new $testCaseClass($testCaseClass, []);
        $testCase->setUpTestSystem($testSystem);

        $testSystem->restoreOriginalDatabaseName($originalDatabaseName);

        $this->writeHtaccessFile($testSystem, $htaccessTemplateFile);

        return $testSystem->getSystemIdentifier();
    }

    private function writeHtaccessFile(TestSystem $testSystem, ?string $htaccessTemplateFile): void
    {
        $htaccessTemplateFile ??= 'EXT:frontend_test/Resources/Private/Templates/TestSystemHtaccessFile.txt';
        $targetFile = sprintf('%s/.htaccess', $testSystem->getSystemPath());

        $templateClass = class_exists(\Text_Template::class) ? \Text_Template::class : Template::class;
        $template = new $templateClass(
            GeneralUtility::getFileAbsFileName($htaccessTemplateFile)
        );
        $template->setVar(
            [
                // the test system path must not end with a slash, otherwise some things will not work anymore
                'testSystemPath' => rtrim($testSystem->getSystemPath(), '/'),
            ]
        );

        $template->renderTo($targetFile);
    }
}
