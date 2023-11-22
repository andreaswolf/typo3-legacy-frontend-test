<?php

\FGTCLB\HttpApi\Api::version(1)->addRoute('frontend-tests/setup', [
    'post' => \AndreasWolf\FrontendTest\Controller\TestSystemSetupController::class . '::setupSystem',
], false);

\FGTCLB\HttpApi\Api::version(1)->addRoute('frontend-tests/login', [
    'post' => \AndreasWolf\FrontendTest\Controller\TestUserLoginController::class . '::login',
], false);

\FGTCLB\HttpApi\Api::version(1)->addRoute('frontend-tests/logout', [
    'post' => \AndreasWolf\FrontendTest\Controller\TestUserLoginController::class . '::logout',
], false);
