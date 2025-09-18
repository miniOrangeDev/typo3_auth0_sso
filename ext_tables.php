<?php

defined('TYPO3') or die();

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

call_user_func(
    function () {
    $version = GeneralUtility::makeInstance(Typo3Version::class);
    $isV13OrHigher = version_compare($version, '13.0.0', '>=');
    $extensionName = $isV13OrHigher || version_compare($version, '10.0.0', '>=') ? 'auth0_sso' : 'Miniorange.auth0_sso';
    $cache_actions_beoidc = $isV13OrHigher || version_compare($version, '10.0.0', '>=') ? [Miniorange\Auth0SSO\Controller\BeoidcController::class => 'request'] : ['Beoidc' => 'request'];
    
    if ($isV13OrHigher) {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['auth0_sso']['BeoidcModule'] = [
            'extensionName' => $extensionName,
            'mainModuleName' => 'tools',
            'subModuleName' => 'beoidckey',
            'controllerActions' => $cache_actions_beoidc,
            'access' => 'admin,user,group',
            'iconIdentifier' => 'auth0_sso-plugin-feoidc',
            'labels' => 'LLL:EXT:auth0_sso/Resources/Private/Language/locallang_bekey.xlf',
            'position' => 'top',
        ];
    } else {
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
            $extensionName,
            'tools', // Make module a submodule of 'tools'
            'beoidckey', // Submodule key
            '4', // Position
            $cache_actions_beoidc,
            [
                'access' => 'admin,user,group',
                'icon'   => 'EXT:auth0_sso/Resources/Public/Icons/Extension.svg',
                'labels' => 'LLL:EXT:auth0_sso/Resources/Private/Language/locallang_bekey.xlf'
            ]
        );
    }

        // Register plugins with proper labels
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            $extensionName,
            'Feoidc',
            'LLL:EXT:auth0_sso/Resources/Private/Language/locallang_db.xlf:tx_auth0_sso_feoidc.name',
            'auth0_sso-plugin-feoidc'
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            $extensionName,
            'Response',
            'LLL:EXT:auth0_sso/Resources/Private/Language/locallang_db.xlf:tx_auth0_sso_response.name',
            'auth0_sso-plugin-feoidc'
        );

    }
);