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
            $extensionName = 'miniorange.auth0_sso';
            $cache_actions_beoidc = ['Beoidc' => 'request'];
        }

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
            $extensionName,
            'tools', // Make module a submodule of 'tools'
            'beoidckey', // Submodule key
            '4', // Position
            $cache_actions_beoidc,
            [
                'access' => 'user,group',
                'icon'   => 'EXT:auth0_sso/Resources/Public/Icons/Extension.png',
                'labels' => 'LLL:EXT:auth0_sso/Resources/Private/Language/locallang_bekey.xlf'
            ]
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            $extensionName,
            'Feoidc',
            'feoidc',
            'EXT:auth0_sso/Resources/Public/Icons/Extension.svg'
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            $extensionName,
            'Response',
            'response',
            'EXT:auth0_sso/Resources/Public/Icons/Extension.svg'
        );

    }
);