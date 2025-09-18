<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Auth0 SSO',
    'description' => 'TYPO3 Auth0 SSO extension by miniOrange allows both TYPO3 backend and frontend users to log in securely using their Auth0 credentials. By integrating TYPO3 with Auth0, this extension eliminates the need for separate TYPO3 accounts, streamlines authentication, and enhances user experience.This extension is fully compatible with TYPO3 v13. ',
    'author' => 'miniOrange',
    'constraints' => [
        'depends' => [
            'typo3' => '8.7.30-13.4.99',
        ],
    ],
    'version' => '2.0.2',
    'icon' => 'EXT:auth0_sso/Resources/Public/Icons/Extension.svg',
    'state' => 'stable',
    'autoload' => [
        'psr-4' => [
            'Miniorange\\Auth0SSO\\' => 'Classes/',
        ],
    ]
];
