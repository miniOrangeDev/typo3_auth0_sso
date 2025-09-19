<?php

namespace Miniorange\Auth0SSO\Controller;

use Miniorange\Auth0SSO\Helper\Constants;
use Miniorange\Auth0SSO\Helper\MoUtilities;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use TYPO3\CMS\Core\Information\Typo3Version;

/**
 * FeoidcController
 */
class FeoidcController extends ActionController
{

    /**
     * requestAction
     * @return void
     */
    public function requestAction(): ResponseInterface
    {
        error_log("Feoidc Controller, inside printAction: ");

    	// Handle Test Configuration
    	if (isset($_REQUEST['RelayState']) && $_REQUEST['RelayState'] === 'testconfig') {
        	if (session_id() == '' || !isset($_SESSION)) {
            	session_start();
        	}
        	$_SESSION['mo_oauth_test'] = true;
        	return $this->redirectAction();
    	}

	// Handle actual SSO login (with ?app=XYZ)
	if (isset($_REQUEST['app']) && !empty($_REQUEST['app'])) {
	        if (session_id() == '' || !isset($_SESSION)) {
	            session_start();
	        }
	        $_SESSION['mo_oauth_app'] = $_REQUEST['app'];   // store app name if needed
	        return $this->redirectAction();
    	}

    	// Handle SSO login without app parameter - use default configuration
    	// Check if we have a valid OIDC configuration in the database
    	$json_object = MoUtilities::fetchFromDb(Constants::OIDC_OIDC_OBJECT, Constants::TABLE_OIDC);
    	if (!empty($json_object)) {
    	    $app = json_decode($json_object, true);
    	    if ($app && isset($app[Constants::OIDC_APP_NAME]) && !empty($app[Constants::OIDC_APP_NAME])) {
    	        // Valid configuration found, proceed with SSO using default app name
    	        if (session_id() == '' || !isset($_SESSION)) {
    	            session_start();
    	        }
    	        $_SESSION['mo_oauth_app'] = $app[Constants::OIDC_APP_NAME];
    	        return $this->redirectAction();
    	    }
    	}

    	// No valid configuration found, show error
    	$responseFactory = GeneralUtility::makeInstance(ResponseFactoryInterface::class);
    	$streamFactory = GeneralUtility::makeInstance(StreamFactoryInterface::class);
    	
    	$errorMessage = 'No valid OIDC configuration found. Please configure the application first.';
    	error_log('FeoidcController: ' . $errorMessage);
    	
    	return $responseFactory->createResponse()
    	    ->withHeader('Content-Type', 'text/html; charset=utf-8')
    	    ->withBody($streamFactory->createStream($errorMessage));
    }

    /**
     * Redirect to Authorization URL
     */
    public function redirectAction(): ResponseInterface
    {
        $json_object = MoUtilities::fetchFromDb(Constants::OIDC_OIDC_OBJECT, Constants::TABLE_OIDC);
        $app = json_decode($json_object, true);
        
        // Validate OIDC configuration
        if (!$app || !isset($app[Constants::OIDC_APP_NAME]) || empty($app[Constants::OIDC_APP_NAME])) {
            $errorMessage = 'OIDC configuration is missing or invalid. Please configure the application first.';
            error_log('FeoidcController redirectAction: ' . $errorMessage);
            
            $responseFactory = GeneralUtility::makeInstance(ResponseFactoryInterface::class);
            $streamFactory = GeneralUtility::makeInstance(StreamFactoryInterface::class);
            return $responseFactory->createResponse()
                ->withHeader('Content-Type', 'text/html; charset=utf-8')
                ->withBody($streamFactory->createStream($errorMessage));
        }
        
        // Use app name from URL parameter if available, otherwise use database config
        $appName = $app[Constants::OIDC_APP_NAME];
        if (session_id() == '' || !isset($_SESSION))
            session_start();
            
        // Check if app name was passed via URL parameter
        if (isset($_SESSION['mo_oauth_app']) && !empty($_SESSION['mo_oauth_app'])) {
            $appName = $_SESSION['mo_oauth_app'];
        }
        
        // Validate required OIDC parameters
        if (!isset($app[Constants::OIDC_AUTH_URL]) || empty($app[Constants::OIDC_AUTH_URL]) ||
            !isset($app[Constants::OIDC_CLIENT_ID]) || empty($app[Constants::OIDC_CLIENT_ID]) ||
            !isset($app[Constants::OIDC_SCOPE]) || empty($app[Constants::OIDC_SCOPE]) ||
            !isset($app[Constants::OIDC_REDIRECT_URL]) || empty($app[Constants::OIDC_REDIRECT_URL])) {
            
            $errorMessage = 'OIDC configuration is incomplete. Missing required parameters (auth_url, client_id, scope, or redirect_url).';
            error_log('FeoidcController redirectAction: ' . $errorMessage);
            
            $responseFactory = GeneralUtility::makeInstance(ResponseFactoryInterface::class);
            $streamFactory = GeneralUtility::makeInstance(StreamFactoryInterface::class);
            return $responseFactory->createResponse()
                ->withHeader('Content-Type', 'text/html; charset=utf-8')
                ->withBody($streamFactory->createStream($errorMessage));
        }
        
        $state = base64_encode($appName);
        $authorizationUrl = $app[Constants::OIDC_AUTH_URL];

        if (strpos($authorizationUrl, "google") !== false) {
            $authorizationUrl = "https://accounts.google.com/o/oauth2/auth";
        }

        $authorizationUrl .= (strpos($authorizationUrl, '?') !== false ? "&" : "?")
            . "client_id=" . $app[Constants::OIDC_CLIENT_ID]
            . "&scope=" . $app[Constants::OIDC_SCOPE]
            . "&redirect_uri=" . $app[Constants::OIDC_REDIRECT_URL]
            . "&response_type=code&state=" . $state;

        $_SESSION['oauth2state'] = $state;
        $_SESSION['appname'] = $appName;


        $version = new Typo3Version();
        $typo3Version = $version->getVersion();

        if ($typo3Version >= 12) {
            return $this->responseFactory->createResponse()
                ->withAddedHeader('Location', $authorizationUrl)
                ->withStatus(302);
        } else {
            header('Location: ' . $authorizationUrl);
            return $this->responseFactory->createResponse(302, 'Redirecting to authorization URL');
        }

    }

}
