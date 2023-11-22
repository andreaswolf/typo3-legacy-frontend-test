<?php
declare(strict_types = 1);

namespace AndreasWolf\FrontendTest\Controller;

use FGTCLB\HttpApi\Http\ApiResponse;
use AndreasWolf\OracleConnector\Functions\FesOracleFunctionsParameters;
use AndreasWolf\OracleConnector\Tests\Integration\TestUsersRepository;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

class TestUserLoginController
{
    /**
     * Performs a login of a user given with the username parameter, and returns the session ID (+ a session cookie
     * which is automatically set by the FE user auth).
     */
    public function login(ServerRequestInterface $request): Response
    {
        if (Environment::getContext()->isTesting() === false) {
            return ApiResponse::error('Only available in Testing contexts', 403);
        }

        $data = (array)$request->getParsedBody();
        $username = $data['username'];
        if ($username === null) {
            return ApiResponse::error('Missing username', 400);
        }

        $repo = GeneralUtility::makeInstance(TestUsersRepository::class);
        $password = $data['password'] ?? $repo->getPassword($username);

        if ($password === null) {
            return ApiResponse::error('Unknown user', 403);
        }

        // ensure our session cookie gets set, even though we are in a subdirectory (/_ft-1234abc/ from the user's
        // perspective, but on the server this is /typo3temp/var/tests/functional-1234abc/, which would also be set
        // in the Cookie path, which would therefore be ignored by the server
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['cookieDomain'] = $request->getUri()->getHost();

        $POST_backup = $_POST;

        $_POST['logintype'] = 'login';
        $_POST['user'] = $username;
        $_POST['pass'] = $password;

        $userAuthentication = GeneralUtility::makeInstance(FrontendUserAuthentication::class);
        $userAuthentication->checkPid_value = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['frontend_test']['userStoragePage'] ?? 0;

        $userAuthentication->start();
        // do NOT call checkAuthentication() here, contrary to its name, it actually removes the session cookie that
        // start() has just set; it's unclear why this happens, probably this is because we just logged in, and
        // checkAuthentication() is more for situations where no login happened in the same request, but the user was
        // authenticated previously (and the session needs to be restored from the cookie)

        // write the Oracle session ID to the TYPO3 FE user session; this is normally done in
        // \AndreasWolf\Users\Middleware\OracleSessionIdEnricher::process, but this was already called at this point (because
        // the session ID is needed in most other API endpoints)
        /** @var FesOracleFunctionsParameters $parameters */
        $parameters = GeneralUtility::makeInstance(Context::class)->getAspect('fes.oracle');
        $userAuthentication->setAndSaveSessionData('fesSessionID', $parameters->sessionId);

        $_POST = $POST_backup;

        $response = ApiResponse::success([
            'sessionId' => $userAuthentication->getSession()->getIdentifier(),
        ]);
        return $userAuthentication->appendCookieToResponse($response);
    }

    public function logout(ServerRequestInterface $request): Response
    {
        if (Environment::getContext()->isTesting() === false) {
            return ApiResponse::error('Only available in Testing contexts', 403);
        }

        $frontendUserAuthentication = GeneralUtility::makeInstance(FrontendUserAuthentication::class);
        $frontendUserAuthentication->removeCookie($frontendUserAuthentication->name);
        $frontendUserAuthentication->setAndSaveSessionData('fesSessionID', '');

        return ApiResponse::success();
    }
}
