<?php
declare(strict_types = 1);

namespace FGTCLB\HttpApi\Tests\Functional;

use FES\OracleConnector\Tests\Integration\OracleEnvironment;
use FGTCLB\HttpApi\Middleware\ApiRequestResolver;
use FGTCLB\HttpApi\Middleware\ApiRequestRouter;
use FGTCLB\HttpApi\Middleware\TypoScriptFrontendSetup;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\MiddlewareDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Http\RequestHandler;

/**
 * @ToDo TypoScript is also required for an API request! The trait should ensure that a default set of TypoScript is loaded.
 *       A simple page = PAGE is enough to call the TSFE without errors
 */
trait ApiTestTrait
{
    // TODO this dependency is problematic, since EXT:http_api does not depend on EXT:oracle_connector
    use OracleEnvironment;

    /**
     * Runs an API request, with HTTP routing and an appropriate user login.
     *
     * @param string $username The Oracle username (email address)
     */
    public function runApiRequestWithOracleUser(string $username, ServerRequestInterface $request): ResponseInterface
    {
        // TODO we might need to re-initialize the global Context instance here and override various settings (e.g. the Oracle context)
        //      this could also be part of a middleware
        $userId = $this->performUserLogin($username);

        $this->setUpTypoScriptFrontend($userId);

        return $this->buildMiddlewareStackAndDispatchRequest($request);
    }

    /**
     * Runs an API request, with HTTP routing and an appropriate user login.
     */
    public function runApiRequest(ServerRequestInterface $request): ResponseInterface
    {
        $this->setUpTypoScriptFrontend(null);

        return $this->buildMiddlewareStackAndDispatchRequest($request);
    }

    private function buildMiddlewareStackAndDispatchRequest(ServerRequestInterface $request): ResponseInterface
    {
        // middlewares in the order in which they will be called
        $middlewares = [
            GeneralUtility::makeInstance(ApiRequestRouter::class),
            GeneralUtility::makeInstance(TypoScriptFrontendSetup::class),
            GeneralUtility::makeInstance(ApiRequestResolver::class),
        ];

        // Reset persistence state so we don't get stale objects from a previous request
        GeneralUtility::makeInstance(ObjectManager::class)->get(PersistenceManager::class)->clearState();

        $requestHandler = GeneralUtility::makeInstance(RequestHandler::class);
        // array_reverse is necessary because the middleware stack is processed bottom-up
        $dispatcher = new MiddlewareDispatcher($requestHandler, array_reverse($middlewares));

        return $dispatcher->handle($request);
    }

    /**
     * Creates instances of the TypoScript frontend controller and frontend user authentication and makes them available
     * in $GLOBALS and Context
     */
    protected function setUpTypoScriptFrontend(?int $fesUserId = null): void
    {
        $GLOBALS['TSFE'] = GeneralUtility::makeInstance(TypoScriptFrontendController::class, null, 1, 0);
        $userAuthentication = GeneralUtility::makeInstance(FrontendUserAuthentication::class);
        $GLOBALS['TSFE']->fe_user = $userAuthentication;
        GeneralUtility::makeInstance(Context::class)
            ->setAspect('frontend.user', GeneralUtility::makeInstance(UserAspect::class, $userAuthentication));

        if ($fesUserId !== null) {
            /** @var false|array<string, int|string> $userRecord */
            $userRecord = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('fe_users')
                ->select(['*'], 'fe_users', ['tx_users_fesuserid' => $fesUserId])
                ->fetch(\PDO::FETCH_ASSOC);

            if ($userRecord === false) {
                throw new \RuntimeException('Could not load fe_users record for FES user ' . $fesUserId, 1623840696);
            }

            $userAuthentication->user = $userRecord;
        }
    }
}
