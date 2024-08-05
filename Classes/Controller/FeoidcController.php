<?php

namespace Miniorange\Auth0SSO\Controller;

use Miniorange\Auth0SSO\Helper\Constants;
use Miniorange\Auth0SSO\Helper\MoUtilities;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;
use Psr\Http\Message\ResponseFactoryInterface;
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
    public function requestAction()
    {
        error_log("Feoidc Controller, inside printAction: ");
        GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->flushCaches();

        $json_object = MoUtilities::fetchFromDb(Constants::OIDC_OIDC_OBJECT, Constants::TABLE_OIDC);
        $app = json_decode($json_object, true);
        $state = MoUtilities::generateRandomAlphanumericValue(10);
        if (session_id() == '' || !isset($_SESSION))
            session_start();
        $_SESSION['oauth2state'] = $state;
        $_SESSION['appname'] = $app[Constants::OIDC_APP_NAME];

        $state = base64_encode($state);
        $authorizationUrl = $app[Constants::OIDC_AUTH_URL];

        if (strpos($authorizationUrl, "google") !== false) {
            $authorizationUrl = "https://accounts.google.com/o/oauth2/auth";
        }

        if (strpos($authorizationUrl, '?') !== false)
            $authorizationUrl = $authorizationUrl . "&client_id=" . $app[Constants::OIDC_CLIENT_ID] . "&scope=" . $app[Constants::OIDC_SCOPE] . "&redirect_uri=" . $app[Constants::OIDC_REDIRECT_URL] . "&response_type=code&state=" . $state;
        else
            $authorizationUrl = $authorizationUrl . "?client_id=" . $app[Constants::OIDC_CLIENT_ID] . "&scope=" . $app[Constants::OIDC_SCOPE] . "&redirect_uri=" . $app[Constants::OIDC_REDIRECT_URL] . "&response_type=code&state=" . $state;


        if (isset($_REQUEST['RelayState']) and $_REQUEST['RelayState'] == 'testconfig') {
            $_SESSION['mo_auth0_sso_test'] = true;
        } else {
            $_SESSION['mo_auth0_sso_test'] = false;
        }
        $version = new Typo3Version();
        $typo3Version = $version->getVersion();
        header('Location: ' . $authorizationUrl);
        if ($typo3Version >= 11.5) {
            return $this->responseFactory->createResponse()
                ->withAddedHeader('Content-Type', 'text/html; charset=utf-8')
                ->withBody($this->streamFactory->createStream($this->view->render()));
        }

    }

}
