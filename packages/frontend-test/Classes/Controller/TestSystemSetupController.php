<?php
declare(strict_types = 1);

namespace AndreasWolf\FrontendTest\Controller;

use AndreasWolf\FrontendTest\FrontendTestCase;
use AndreasWolf\FrontendTest\Service\TestSystemSetup;
use FGTCLB\HttpApi\Http\ApiResponse;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TestSystemSetupController
{
    /**
     * Creates a "child" test system for the given testCase class, if it does not exist.
     *
     * If the system already exists, its database is reset to the original state.
     *
     * Returns the test system ID (a seven char hex string).
     */
    public function setupSystem(ServerRequestInterface $request): Response
    {
        $data = (array)$request->getParsedBody();

        if (!isset($data['testCase'])) {
            return ApiResponse::error('Missing parameter "testCase"');
        }
        $testCase = $data['testCase'];

        if (!class_exists($testCase)) {
            return ApiResponse::error(sprintf('Could not find test case "%s"', $testCase));
        }

        if (!in_array(FrontendTestCase::class, class_implements($testCase) ?: [], true)) {
            return ApiResponse::error(sprintf('Can only setup test cases that implements "%s"', FrontendTestCase::class));
        }
        /** @var class-string<FrontendTestCase> $testCase */
        $setup = GeneralUtility::makeInstance(TestSystemSetup::class);
        $testSystemId = $setup->setupTestSystem($testCase, $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['frontend_test']['htaccessTemplateFile'] ?: null);

        return ApiResponse::basicSuccess($testSystemId);
    }
}
