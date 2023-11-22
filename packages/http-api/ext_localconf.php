<?php

(function () {
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['http_api']['rolesProviders'][] =
        \FGTCLB\HttpApi\Http\GlobalApiRolesProvider::class;
})();
