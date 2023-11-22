<?php
declare(strict_types = 1);

namespace FGTCLB\HttpApi\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Page\PageAccessFailureReasons;

/**
 * Perform a basic TSFE setup to ensure e.g. typoLink works properly in API endpoints
 */
final class TypoScriptFrontendSetup implements MiddlewareInterface
{
    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $controller = $request->getAttribute('api.controller', null);

        if ($controller === null) {
            return $handler->handle($request);
        }

        if (GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion() < 10) {
            // disable page errors
            $GLOBALS['TYPO3_CONF_VARS']['FE']['pageUnavailable_handling'] = false;
            $GLOBALS['TSFE']->fetch_the_id();
            $GLOBALS['TSFE']->getConfigArray();
            $GLOBALS['TSFE']->settingLanguage();
            $GLOBALS['TSFE']->settingLocale();
            $GLOBALS['TSFE']->newCObj();
        } else {
            // This code part was copied from TypoScriptFrontendInitialization Middleware to initialize a TSFE in TYPO3 v10 and v11
            $context = GeneralUtility::makeInstance(Context::class);

            $GLOBALS['TYPO3_REQUEST'] = $request;

            /** @var Site $site */
            $site = $request->getAttribute('site', null);
            $pageArguments = $request->getAttribute('routing', null);
            if (!$pageArguments instanceof PageArguments) {
                // Page Arguments must be set in order to validate. This middleware only works if PageArguments
                // is available, and is usually combined with the Page Resolver middleware
                return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                    $request,
                    'Page Arguments could not be resolved',
                    ['code' => PageAccessFailureReasons::INVALID_PAGE_ARGUMENTS]
                );
            }

            $controller = GeneralUtility::makeInstance(
                TypoScriptFrontendController::class,
                $context,
                $site,
                $request->getAttribute('language', $site->getDefaultLanguage()),
                $pageArguments,
                $request->getAttribute('frontend.user', null)
            );
            if ($pageArguments->getArguments()['no_cache'] ?? $request->getParsedBody()['no_cache'] ?? false) {
                $controller->set_no_cache('&no_cache=1 has been supplied, so caching is disabled! URL: "' . (string)$request->getUri() . '"');
            }
            // Usually only set by the PageArgumentValidator
            if ($request->getAttribute('noCache', false)) {
                $controller->no_cache = true;
            }

            $controller->determineId($request);

            $request = $request->withAttribute('frontend.controller', $controller);
            // Make TSFE globally available
            // @todo deprecate $GLOBALS['TSFE'] once TSFE is retrieved from the
            //       PSR-7 request attribute frontend.controller throughout TYPO3 core
            $GLOBALS['TSFE'] = $controller;
        }

        return $handler->handle($request);
    }
}
