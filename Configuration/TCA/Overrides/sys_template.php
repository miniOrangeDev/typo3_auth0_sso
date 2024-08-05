<?php

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('auth0_sso', 'Configuration/TypoScript', '');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_oauth_domain_model_feoauth', 'EXT:auth0_sso/Resources/Private/Language/locallang_csh_tx_oauth_domain_model_feoauth.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_oauth_domain_model_feoauth');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_oauth_domain_model_beoauth', 'EXT:auth0_sso/Resources/Private/Language/locallang_csh_tx_oauth_domain_model_beoauth.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_oauth_domain_model_beoauth');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_oauth_domain_model_response', 'EXT:auth0_sso/Resources/Private/Language/locallang_csh_tx_oauth_domain_model_response.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_oauth_domain_model_response');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_oauth_domain_model_logout', 'EXT:auth0_sso/Resources/Private/Language/locallang_csh_tx_oauth_domain_model_logout.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_oauth_domain_model_logout');