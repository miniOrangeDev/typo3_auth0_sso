<?php

namespace Miniorange\Auth0SSO\Controller;

use Miniorange\Auth0SSO\Helper\Constants;
use Miniorange\Auth0SSO\Helper\MoUtilities;
use Miniorange\Auth0SSO\Helper\OAuthHandler;
use Miniorange\Auth0SSO\Helper\Utilities;
use Miniorange\Auth0SSO\Helper\Actions\TestResultActions;
use PDO;

use ReflectionClass;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Felogin\Controller\FrontendLoginController;
use TYPO3\CMS\Extbase\Domain\Model\FrontendUser;
use TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository;
use TYPO3\CMS\Core\Information\Typo3Version;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;
use Miniorange\Auth0SSO\Helper\CustomerMo;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthenticator;
use TYPO3\CMS\Core\Session\UserSessionManager;
use TYPO3\CMS\Core\Session\SessionManager;
use TYPO3\CMS\Core\Session\UserSession;
use TYPO3\CMS\Core\Http\Response;

/**
 * ResponseController
 */
class ResponseController extends ActionController
{
    protected $persistenceManager = null;
    protected $frontendUserRepository = null;
    private $first_name = null;
    private $last_name = null;
    private $attrsReceived = null;
    private $amObject = null;
    private $ses_id = null;

    private $ssoemail = null;

    private $username = "";

    private $callbackUrl = "";

    private $sesAccessToken = null;
    private $nameId = null;
    private $status = null;


    /**
     * action check
     *
     * @return void
     */
    public function responseAction()
    {
        error_log("In reponseController: checkAction() ");
        $version = new Typo3Version();
        $typo3Version = $version->getVersion();

        // Perform lazy migration when database is available
        MoUtilities::performLazyMigration();

        GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->flushCaches();
        if (array_key_exists('logintype', $_REQUEST) && $_REQUEST['logintype'] == 'logout') {
            error_log("Logout intercepted.");
            $this->logout($typo3Version);
            $logoutUrl = $this->request->getBaseUri();
            header('Location: ' . $logoutUrl);
        } else if (strpos($_SERVER['REQUEST_URI'], "/oauthcallback") !== false || isset($_GET['code'])) {
            if (session_id() == '' || !isset($_SESSION))
                session_start();

            error_log("session started" . print_r($_SERVER, true));

            error_log("code: " . print_r($_GET, true));
            if (!isset($_GET['code'])) {
                if (isset($_GET['error_description']))
                    exit($_GET['error_description']);
                else if (isset($_GET['error']))
                    exit($_GET['error']);
                exit('Invalid response');
            } else {

                try {

                    $currentappname = "";

                    // Enhanced app name resolution with multiple fallback options
                    if (isset($_SESSION['appname']) && !empty($_SESSION['appname'])) {
                        $currentappname = $_SESSION['appname'];
                    } else if (isset($_GET['state']) && !empty($_GET['state'])) {
                        $currentappname = base64_decode($_GET['state']);
                    } else if (isset($_SESSION['mo_oauth_app']) && !empty($_SESSION['mo_oauth_app'])) {
                        $currentappname = $_SESSION['mo_oauth_app'];
                    } else {
                        // Final fallback: try to get app name from stored configuration
                        $oidc_object = MoUtilities::fetchFromDb(Constants::OIDC_OIDC_OBJECT, Constants::TABLE_OIDC);
                        if (!empty($oidc_object)) {
                            $app = json_decode($oidc_object, true);
                            if ($app && isset($app[Constants::OIDC_APP_NAME]) && !empty($app[Constants::OIDC_APP_NAME])) {
                                $currentappname = $app[Constants::OIDC_APP_NAME];
                            }
                        }
                    }
                    
                    if (empty($currentappname)) {
                        exit('No request found for this application.');
                    }

                    $username_attr = "";
                    $oidc_object = MoUtilities::fetchFromDb(Constants::OIDC_OIDC_OBJECT, Constants::TABLE_OIDC);
                    $currentapp = isset($oidc_object) ? json_decode((string)$oidc_object, true) : array();
                    $this->amObject = $currentapp;
                    if (!$currentapp)
                        exit('Application not configured.');

                    $mo_oauth_handler = new OAuthHandler();

                    if (!isset($currentapp['set_header_credentials']))
                        $currentapp['set_header_credentials'] = false;
                    if (!isset($currentapp['set_body_credentials']))
                        $currentapp['set_body_credentials'] = false;

                    if (isset($currentapp['app_type']) && $currentapp['app_type'] == Constants::TYPE_OPENID_CONNECT) {
                        // OpenId connect
                        $relayStateUrl = array_key_exists('RelayState', $_REQUEST) ? $_REQUEST['RelayState'] : '/';
                        error_log("relaystate in response: " . $relayStateUrl);
                        $tokenResponse = $mo_oauth_handler->getIdToken($currentapp['token_endpoint'],
                            'authorization_code',
                            $currentapp['client_id'],
                            $currentapp['client_secret'],
                            $_GET['code'],
                            $currentapp['redirect_url'],
                            $currentapp['set_header_credentials'],
                            $currentapp['set_body_credentials']
                        );

                        $idToken = isset($tokenResponse["id_token"]) ? $tokenResponse["id_token"] : $tokenResponse["access_token"];

                        if (!$idToken)
                            exit('Invalid token received.');
                        else
                            $resourceOwner = $mo_oauth_handler->getResourceOwnerFromIdToken($idToken);

                        if (isset($resourceOwner['email']))
                            $resourceOwner['NameID'] = ['0' => $resourceOwner['email']];
                        else
                            $resourceOwner['NameID'] = ['0' => $this->findUserEmail($resourceOwner)];

                    } else {
                        //OAuth Flow
                        $accessTokenUrl = $currentapp['token_endpoint'];
                        if (strpos($accessTokenUrl, "google") !== false) {
                            $accessTokenUrl = "https://www.googleapis.com/oauth2/v4/token";
                        }
                        $accessToken = $mo_oauth_handler->getAccessToken($accessTokenUrl,
                            'authorization_code',
                            $currentapp['client_id'],
                            $currentapp['client_secret'],
                            $_GET['code'],
                            $currentapp['redirect_url'],
                            $currentapp['set_header_credentials'],
                            $currentapp['set_body_credentials']
                        );
                        if (!$accessToken)
                            exit('Invalid token received.');

                        $resourceownerdetailsurl = $currentapp['user_info_endpoint'];
                        if (substr($resourceownerdetailsurl, -1) == "=") {
                            $resourceownerdetailsurl .= $accessToken;
                        }
                        if (strpos($resourceownerdetailsurl, "google") !== false) {
                            $resourceownerdetailsurl = "https://www.googleapis.com/oauth2/v1/userinfo";
                        }
                        $resourceOwner = $mo_oauth_handler->getResourceOwner($resourceownerdetailsurl, $accessToken);
                    }

                    $username = "";

                    $nameId = $this->findUserEmail($resourceOwner);
                    $this->nameId = $nameId;
                    //TEST Configuration
                    if (isset($_SESSION['mo_oauth_test']) && $_SESSION['mo_oauth_test']) {
                        echo '<div style="font-family:Calibri;padding:0 3%;">';
                        echo '<style>table{border-collapse:collapse;}th {background-color: #eee; text-align: center; padding: 8px; border-width:1px; border-style:solid; border-color:#212121;}tr:nth-child(odd) {background-color: #f2f2f2;} td{padding:8px;border-width:1px; border-style:solid; border-color:#212121;}</style>';
                        echo "<h2>Test Configuration</h2><table><tr><th>Attribute Name</th><th>Attribute Value</th></tr>";
                        Utilities::testAttrMappingConfig("", $resourceOwner);
                        echo "</table>";
                        echo '<div style="padding: 10px;"></div><input style="padding:1%;width:100px;background: #0091CD none repeat scroll 0% 0%;cursor: pointer;font-size:15px;border-width: 1px;border-style: solid;border-radius: 3px;white-space: nowrap;box-sizing: border-box;border-color: #0073AA;box-shadow: 0px 1px 0px rgba(120, 200, 230, 0.6) inset;color: #FFF;"type="button" value="Done" onClick="self.close();"></div>';
                        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Constants::TABLE_OIDC);
                        if($typo3Version > 12){
                            $configurations = $queryBuilder->select(Constants::OIDC_OIDC_OBJECT)->from(Constants::TABLE_OIDC)->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)))->executeQuery()->fetchAssociative();
                        }else{
                            $configurations = $queryBuilder->select(Constants::OIDC_OIDC_OBJECT)->from(Constants::TABLE_OIDC)->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)))->execute()->fetch();
                        }
                        $configurations = $configurations[Constants::OIDC_OIDC_OBJECT];
                        $this->status = Utilities::isBlank($resourceOwner) ? 'Test Failed' : 'Test SuccessFull';
                        $isTestEmailSent = MoUtilities::fetchFromOidc(Constants::TEST_EMAIL_SENT);
                        $isTestEmailSent = null;
                        if($isTestEmailSent == NULL)
                        {
                            // Handle test tracking directly
                            $customer = new CustomerMo();
                            $timestamp = MoUtilities::fetch_cust(Constants::TIMESTAMP);
                            $decoded_idp_object = json_decode($configurations, true);
                            $idp_name = isset($decoded_idp_object['app_name']) ? $decoded_idp_object['app_name'] : '';
                            if($this->status == 'Test SuccessFull')
                            {
                                $data = [
                                    'timeStamp' => $timestamp,
                                    'IdentityProvider' => $idp_name,
                                    'testSuccessful' => $resourceOwner
                                ];
                            } else {
                                $data = [
                                    'timeStamp' => $timestamp,
                                    'IdentityProvider' => $idp_name,
                                    'testFailed' => $resourceOwner
                                ];
                            }
                            $customer->syncPluginMetrics($data);
                            MoUtilities::updateOidc(Constants::TEST_EMAIL_SENT, 1);
                        }
                        exit($_SESSION['mo_oauth_test'] = false);
                    }
                    $am_username = MoUtilities::fetchFromDb(Constants::OIDC_ATTRIBUTE_USERNAME, Constants::TABLE_OIDC);
                    if (isset($am_username) && $am_username != "") {
                        $username_attr = $am_username;
                    } else {
                        exit("Attribute Mapping not configured. Please contact your Administrator!");
                    }

                    if (!empty($username_attr))
                        $username = $this->getnestedattribute($resourceOwner, $username_attr);

                    if (empty($username) || "" === $username)
                        exit('Username not received. Check your <b>Attribute Mapping</b> configuration.');
                    if (!is_string($username['0'])) {
                        exit('Username is not a string. It is ' . gettype($username));
                    }
                } catch (Exception $e) {
                    // Failed to get the access token or user details.
                    exit($e->getMessage());
                }
            }
            if (is_array($username))
                $this->login_user($username['0'], $typo3Version);
            else
                $this->login_user($username, $typo3Version);
        } else if (isset($_REQUEST['option']) and strpos($_REQUEST['option'], 'mooauth') !== false) {
            //do stuff after returning from oAuth processing
            $access_token = $_POST['access_token'];
            $token_type = $_POST['token_type'];
            $user_email = '';
            if (array_key_exists('email', $_POST))
                $user_email = $_POST['email'];

            $this->login_user($user_email, $typo3Version);
        }

        if ($typo3Version >= 11.5) {
            $responseFactory = GeneralUtility::makeInstance(\Psr\Http\Message\ResponseFactoryInterface::class);
            $streamFactory = GeneralUtility::makeInstance(\Psr\Http\Message\StreamFactoryInterface::class);
            $response = $responseFactory->createResponse()
                ->withAddedHeader('Content-Type', 'text/html; charset=utf-8')
                ->withBody($streamFactory->createStream($this->view->render()));
            return $response;
        }

    }

    /**
     * @param $ses_id
     * @param $ssoemail
     * @return string
     * @throws \Exception
     */
    public function logout($typo3Version)
    {
        error_log("Responsecontroller: inside logout");
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('fe_sessions');
        if($typo3Version > 12){
            if (isset($_SESSION['ses_id']))
                $queryBuilder->delete('fe_sessions')->where($queryBuilder->expr()->eq('ses_userid', $queryBuilder->createNamedParameter($_SESSION['ses_id'], Connection::PARAM_INT)))->executeStatement();
        } else {
            if (isset($_SESSION['ses_id']))
                $queryBuilder->delete('fe_sessions')->where($queryBuilder->expr()->eq('ses_userid', $queryBuilder->createNamedParameter($_SESSION['ses_id'], Connection::PARAM_INT)))->execute();
        }

    }

    function findUserEmail($arr)
    {
        error_log("ProcessResponseAction: In findUserEmail");
        if ($arr) {
            foreach ($arr as $value) {
                if (is_array($value) && !empty($value)) {
                    return $this->findUserEmail($value);
                } elseif (isset($value) && !empty($value) && $value != null) {
                    if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        return $value;
                    }
                } else {
                    error_log("null parameter");
                }
            }
        }
    }

    function getNestedAttribute($resource, $key)
    {
        if ($key === "")
            return "";

        $keys = explode(".", $key);
        if (sizeof($keys) > 1) {
            $current_key = $keys[0];
            if (isset($resource[$current_key]))
                return $this->getNestedAttribute($resource[$current_key], str_replace($current_key . ".", "", $key));
        } else {
            $current_key = $keys[0];
            if (isset($resource[$current_key])) {
                return $resource[$current_key];
            }
        }
    }

    function login_user($username, $typo3Version)
    {

        $user = MoUtilities::fetchUserFromUsername($username);
        $this->createOrUpdateUser($user, $username, $typo3Version);
        $user = MoUtilities::fetchUserFromUsername($username);
        $_SESSION['ses_id'] = $user['uid'];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('fe_sessions');

        $queryBuilder->delete('fe_sessions')->where($queryBuilder->expr()->eq('ses_userid',$queryBuilder->createNamedParameter($user['uid'], \Doctrine\DBAL\ParameterType::INTEGER)));
    	if ($typo3Version > 12) {
        	$queryBuilder->executeStatement();
        } else {
        	$queryBuilder->execute();
    	}
    if ($typo3Version > 12) {
        // TYPO3 v13+ flow
        $frontendUser = GeneralUtility::makeInstance(FrontendUserAuthentication::class);
        $frontendUser->start($this->request);

        // Create the session for this FE user
        $session = $frontendUser->createUserSession($user);
        $sessionId = $session->getIdentifier();

        // Add additional session data if needed
        $data = [
            'app_name' => 'myApp',   // replace with your provider/app
            'nameId'   => $username
        ];

        $sessionManager = GeneralUtility::makeInstance(SessionManager::class);
        $sessionBackend = $sessionManager->getSessionBackend('FE');

        $currentSessionData = $session->getData();
        $currentSessionData['ses_data'] = serialize($data);

        $sessionBackend->update($sessionId, $currentSessionData);

        // Store session data
        $frontendUser->storeSessionData();

        // Set session cookie
        setcookie('fe_typo_user', $session->getJwt(), 0, '/');

        // Auth globals
        $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['FE_alwaysFetchUser'] = true;
        $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['FE_alwaysAuthUser'] = true;
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['login_confirmed'] = true;

        // Redirect (adjust as needed)
        $redirectUrl = '/';
        if (isset($_SESSION['post_login_redirect_url'])) {
            $redirectUrl = $_SESSION['post_login_redirect_url'];
            unset($_SESSION['post_login_redirect_url']);
        }

        $response = new Response();
        header('Location: ' . $redirectUrl);
        return $response->withHeader('Location', $redirectUrl)->withStatus(302);

    } else {
        // TYPO3 v12 and below flow (your existing code)
        $GLOBALS['TSFE']->fe_user->forceSetCookie = TRUE;

        $GLOBALS['TSFE']->fe_user->createUserSession($user);
        $GLOBALS['TSFE']->initUserGroups();
        $GLOBALS['TSFE']->fe_user->loginSessionStarted = TRUE;
        $GLOBALS['TSFE']->fe_user->user = $user;
        $GLOBALS['TSFE']->fe_user->loginSessionStarted = true;
        $reflection = new ReflectionClass($GLOBALS['TSFE']->fe_user);
        $setSessionCookieMethod = $reflection->getMethod('setSessionCookie');
        $setSessionCookieMethod->setAccessible(TRUE);
        $setSessionCookieMethod->invoke($GLOBALS['TSFE']->fe_user);
        $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['FE_alwaysFetchUser'] = true;
        $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['FE_alwaysAuthUser'] = true;
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['login_confirmed'] = true;
        $test = $GLOBALS['TSFE']->fe_user->user;
        if (!isset($_SESSION)) {
            session_id('email');
            session_start();
        }
    }
    }

    /**
     * @param $user
     * @return bool
     */
    public function createOrUpdateUser($user, $username, $typo3Version)
    {
        $userExist = false;
        if ($user == null) {
            if (isset($this->amObject[Constants::EXISTING_USERS_ONLY]) && $this->amObject[Constants::EXISTING_USERS_ONLY] == 'true') {
                error_log('New user is not allowed to register. Please disable only existing user option.');
                exit("New users are not allowed to register or login.");
            } else {
                $userCount = MoUtilities::fetchEncryptedUserCount();
                if ($userCount > 0) {
                    error_log("CREATING USER" . $username);
                    $newUser = [
                        'username' => $username,
                        'password' => MoUtilities::generateRandomAlphanumericValue(10), // You may want to hash the password using TYPO3's encryption functions
                        // Add other necessary fields
                    ];

                    // Insert the new user into the fe_users table
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Constants::TABLE_FE_USERS);
                    if($typo3Version > 12){
                    $queryBuilder->insert(Constants::TABLE_FE_USERS)->values($newUser)->executeStatement();
                    }else{
                    $queryBuilder
                        ->insert(Constants::TABLE_FE_USERS)
                        ->values($newUser)
                        ->execute();
                    }

                    // Output the UID of the newly created user
                    $uid = $queryBuilder->getConnection()->lastInsertId(Constants::TABLE_FE_USERS);
                    
                    // Decrement the encrypted user count
                    $newUserCount = MoUtilities::decrementEncryptedUserCount();
                } else {
                    $site = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST');
                    $customer = new CustomerMo();
                    $userLimitExceedEmailSent = MoUtilities::fetchFromOidc(Constants::USER_LIMIT_EXCEED_EMAIL_SENT);
                    if($userLimitExceedEmailSent == NULL)
                    {
                        $timestamp = MoUtilities::fetch_cust(Constants::TIMESTAMP);
                        $data = [
                            'timeStamp' => $timestamp,
                            'autoCreateLimit' => 'Yes'
                        ];
                        $customer->syncPluginMetrics($data);
                        MoUtilities::updateOidc(Constants::USER_LIMIT_EXCEED_EMAIL_SENT, 1);
                    }
                    echo "Auto create user limit has been exceeded!!! Please contact your Administrator.";
                    exit;
                }
            }
        } else {
            $userExist = true;
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Constants::TABLE_FE_USERS);
            if($typo3Version > 12){
                $uid = $queryBuilder->select('uid')->from(Constants::TABLE_FE_USERS)->where($queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter($username, Connection::PARAM_STR)))->executeQuery()->fetchAssociative();
            }else{
                $uid = $queryBuilder->select('uid')->from(Constants::TABLE_FE_USERS)->where($queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter($username, Connection::PARAM_STR)))->execute()->fetch();
            }
            $uid = $uid['uid'];
        }
        MoUtilities::updateTable('usergroup', "", 'fe_users');
        $mappedTypo3Group = Utilities::fetchFromTable(Constants::COLUMN_GROUP_DEFAULT, Constants::TABLE_OIDC);
        if (empty($mappedTypo3Group)) {
            echo "Group Mapping not found. Please contact your Administrator";
            exit;
        }
        $mappedGroupUid = MoUtilities::fetchUidFromGroupName($mappedTypo3Group);
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Constants::TABLE_FE_USERS);
        if($typo3Version > 12){
        $queryBuilder->update(Constants::TABLE_FE_USERS)->where($queryBuilder->expr()->eq('uid', $uid))
                ->set('usergroup', $mappedGroupUid)->executeStatement();
        }else{
            $queryBuilder->update(Constants::TABLE_FE_USERS)->where($queryBuilder->expr()->eq('uid', $uid))
            ->set('usergroup', $mappedGroupUid)->execute();
        }
        GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->flushCaches();
        return true;
    }

}
