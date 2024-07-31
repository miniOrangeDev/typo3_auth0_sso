<?php

defined('TYPO3') or die();

use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Core\Information\Typo3Version;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['security.backend.enforceContentSecurityPolicy'] = false;
$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['enforceValidation'] = false;
$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'] = ['idp_name', 'RelayState', 'option', 'SAMLRequest', 'SAMLResponse', 'SigAlg', 'Signature', 'type', 'app', 'code', 'state'];

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


        // wizards
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
            'mod {
            wizards.newContentElement.wizardItems.plugins {
                elements {
                    Feoidckey {
                        iconIdentifier = auth0_sso-plugin-feoidc
                        title = LLL:EXT:auth0_sso/Resources/Private/Language/locallang_db.xlf:tx_auth0_sso_feoidc.name
                        description = LLL:EXT:auth0_sso/Resources/Private/Language/locallang_db.xlf:tx_auth0_sso_feoidc.description
                        tt_content_defValues {
                            CType = list
                            list_type = Feoidc
                        }
                    }
                    Responsekey {
                        iconIdentifier = auth0_sso-plugin-response
                        title = LLL:EXT:auth0_sso/Resources/Private/Language/locallang_db.xlf:tx_auth0_sso_response.name
                        description = LLL:EXT:auth0_sso/Resources/Private/Language/locallang_db.xlf:tx_auth0_sso_response.description
                        tt_content_defValues {
                            CType = list
                            list_type = Response
                        }
                    }
                }
                show = *
            }
       }'
        );

        $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
        $iconRegistry->registerIcon(
            'auth0_sso-plugin-feoidc',
            \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            ['source' => 'EXT:auth0_sso/Resources/Public/Icons/miniorange.png']
        );
        $iconRegistry->registerIcon(
            'auth0_sso-plugin-response',
            \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            ['source' => 'EXT:auth0_sso/Resources/Public/Icons/miniorange.png']
        );
        $iconRegistry->registerIcon(
            'auth0_sso-plugin-logout',
            \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            ['source' => 'EXT:auth0_sso/Resources/Public/Icons/miniorange.png']
        );
        $iconRegistry->registerIcon(
            'auth0_sso-plugin-bekey',
            \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            ['source' => 'EXT:auth0_sso/Resources/Public/Icons/miniorange.png']
        );

    }
);