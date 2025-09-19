<?php

defined('TYPO3') or die();

use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Core\Information\Typo3Version;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['security.backend.enforceContentSecurityPolicy'] = false;
$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['enforceValidation'] = false;
$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'] = ['idp_name', 'RelayState', 'option', 'SAMLRequest', 'SAMLResponse', 'SigAlg', 'Signature', 'type', 'app', 'code', 'state', 'logintype'];

call_user_func(
    function () {
        $pluginNameBeoidc = "Beoidc";
        $pluginNameFeoidc = 'Feoidc';
        $pluginNameResponse = 'Response';
        $pluginNameLogout = 'Logout';
        $version = new Typo3Version();
        if (version_compare($version, '10.0.0', '>=')) {
            $extensionName = 'auth0_sso';
            $cache_actions_beoidc = [Miniorange\Auth0SSO\Controller\BeoidcController::class => 'request'];
            $cache_actions_feoidc = [Miniorange\Auth0SSO\Controller\FeoidcController::class => 'request'];
            $non_cache_actions_feoidc = [Miniorange\Auth0SSO\Controller\FeoidcController::class => 'control'];
            $cache_actions_response = [Miniorange\Auth0SSO\Controller\ResponseController::class => 'response'];
            $cache_actions_logout = [Miniorange\Auth0SSO\Controller\LogoutController::class => 'logout'];
        } else {
            $extensionName = 'miniorange.auth0_sso';
            $cache_actions_beoidc = ['Beoidc' => 'request'];
            $cache_actions_feoidc = ['Feoidc' => 'request'];
            $non_cache_actions_feoidc = ['Feoidc' => 'control'];
            $cache_actions_response = ['Response' => 'response'];
            $cache_actions_logout = ['Logout' => 'logout'];
        }

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            $extensionName,
            $pluginNameBeoidc,
            [
                'Beoidc' => 'request',
            ]
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            $extensionName,
            $pluginNameFeoidc,
            $cache_actions_feoidc,
            // non-cacheable actions
            $non_cache_actions_feoidc
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            $extensionName,
            $pluginNameResponse,
            $cache_actions_response
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            $extensionName,
            $pluginNameLogout,
            $cache_actions_logout
        );

        // Migration for encrypted countuser - handled via lazy loading in controllers

    }
);