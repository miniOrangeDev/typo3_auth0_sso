<?php

defined('TYPO3') or die();

use TYPO3\CMS\Core\Information\Typo3Version;

call_user_func(
    function () {
        $version = new Typo3Version();
        if (version_compare($version, '10.0.0', '>=')) {
            $extensionName = 'auth0_sso';
            $cache_actions_beoidc = [Miniorange\Auth0SSO\Controller\BeoidcController::class => 'request'];
        } else {
            $extensionName = 'Miniorange.auth0_sso';
            $cache_actions_beoidc = ['Beoidc' => 'request'];
        }

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            $extensionName,
            'Feoidc',
            'feoidc'
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            $extensionName,
            'Response',
            'response'
        );

    }
);
