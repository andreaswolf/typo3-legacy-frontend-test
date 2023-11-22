<?php
declare(strict_types = 1);

namespace AndreasWolf\FrontendTest\Service;

use TYPO3\CMS\Core\Package\Exception\PackageManagerCacheUnavailableException;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * Override for the core PackageManager that is used during test system setup.
 *
 * Ensures that we get the list of extensions as the loaded extensions from the test case that is being set up, and not
 * the main system (or the test case that currently runs, in the case of tests that are executed for the test system
 * setup).
 *
 * All caching in this class was disabled to prevent messing with any caches or other data of the "mother" TYPO3 system
 * (in whose context this class is used).
 */
class TestSystemPackageManager extends PackageManager
{
    /** @var string */
    private $testSystemPath;

    /**
     * Injector for the test system path. Required for this class to be able to find the packages inside the "child"
     * test system.
     */
    public function setTestSystemPath(string $testSystemPath): void
    {
        // The extensions are all below typo3temp/var/tests/functional-abc1234/, either in typo3/sysext/ or
        // typo3conf/ext/
        $this->testSystemPath = $testSystemPath;
        $this->packagesBasePath = $testSystemPath;
    }

    protected function loadPackageStates(): void
    {
        $this->packageStatesPathAndFilename = sprintf('%stypo3conf/PackageStates.php', $this->testSystemPath);
        parent::loadPackageStates();
    }

    protected function loadPackageManagerStatesFromCache(): void
    {
        // always initialize state
        throw new PackageManagerCacheUnavailableException('', 1641574866);
    }

    protected function saveToPackageCache(): void
    {
        // no-op
    }

    protected function savePackageStates(): void
    {
        // no-op
    }
}
