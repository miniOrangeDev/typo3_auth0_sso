<?php

use Miniorange\Auth0SSO\Controller\BeoidcController;

/**
 * Definitions for modules provided by EXT:examples
 */
return [
    'tools_auth0_sso' => [
        'parent' => 'tools',
        'position' => [],
        'access' => 'user,group',
        'workspaces' => 'live',
        'iconIdentifier' => 'auth0_sso-plugin-bekey',
        'path' => 'module/tools/beoidckey',
        'labels' => 'LLL:EXT:auth0_sso/Resources/Private/Language/locallang_bekey.xlf',
        'extensionName' => 'auth0_sso',
        'controllerActions' => [
            BeoidcController::class => 'request',
        ],
    ]
];