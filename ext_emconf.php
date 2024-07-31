<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Auth0 SSO',
    'description' => 'Auth0 extension for Typo3 allows your backend and frontend users to log into TYPO3 using Auth0 credentials.',
    'constraints' => [
        'depends' => [
            'typo3' => '10.0.0-12.4.99',
        ],
    ],
    'version' => '1.0.0',
    'icon' => 'EXT:auth0_sso/Resources/Public/Icons/miniorange.svg',
    'state' => 'stable',
    'autoload' => [
        'psr-4' => [
            'Miniorange\\Auth0SSO\\' => 'Classes/',
        ],
    ]
];
