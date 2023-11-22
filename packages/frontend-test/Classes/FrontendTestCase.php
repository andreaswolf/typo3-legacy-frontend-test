<?php
declare(strict_types = 1);

namespace AndreasWolf\FrontendTest;

use AndreasWolf\FrontendTest\Service\TestSystem;

/**
 * Interface that must be implemented by test cases that can be initialized via the HTTP API.
 */
interface FrontendTestCase
{
    public function setUpTestSystem(TestSystem $system): void;
}
