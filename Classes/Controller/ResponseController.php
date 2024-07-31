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
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Felogin\Controller\FrontendLoginController;
use TYPO3\CMS\Extbase\Domain\Model\FrontendUser;
use TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository;
use TYPO3\CMS\Core\Information\Typo3Version;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;
use Psr\Http\Message\ResponseFactoryInterface;
use Miniorange\Auth0SSO\Helper\CustomerMo;

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
            
                    if (!(isset($_GET['state']) && !empty($_GET['state']) && isset($_SESSION['oauth2state']) && $_SESSION['oauth2state'] == base64_decode($_GET['state']))) {
                        exit('No request found for this application.');
                    }

                    $username_attr = "";
                    $oidc_object = MoUtilities::fetchFromOidc(Constants::OIDC_OIDC_OBJECT);
                    $currentapp = isset($oidc_object) ? json_decode((string)$oidc_object, true) : array();
                    if (!$currentapp)
                        exit('Application not configured.');

                    $mo_auth0_sso_handler = new OAuthHandler();

                    if (!isset($currentapp['set_header_credentials']))
                        $currentapp['set_header_credentials'] = false;
                    if (!isset($currentapp['set_body_credentials']))
                        $currentapp['set_body_credentials'] = false;

                    if (isset($currentapp['app_type']) && $currentapp['app_type'] == Constants::TYPE_OPENID_CONNECT) {
                        // OpenId connect
                        $relayStateUrl = array_key_exists('RelayState', $_REQUEST) ? $_REQUEST['RelayState'] : '/';
                        error_log("relaystate in response: " . $relayStateUrl);
                        $tokenResponse = $mo_auth0_sso_handler->getIdToken($currentapp['token_endpoint'],
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
                            $resourceOwner = $mo_auth0_sso_handler->getResourceOwnerFromIdToken($idToken);

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
                        $accessToken = $mo_auth0_sso_handler->getAccessToken($accessTokenUrl,
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
                        $resourceOwner = $mo_auth0_sso_handler->getResourceOwner($resourceownerdetailsurl, $accessToken);
                    }

                    $username = "";

                    $nameId = $this->findUserEmail($resourceOwner);
                    $this->nameId = $nameId;
                    //TEST Configuration
                    if (isset($_SESSION['mo_auth0_sso_test']) && $_SESSION['mo_auth0_sso_test']) {
                        echo '<div style="font-family:Calibri;padding:0 3%;">';
                        echo '<style>table{border-collapse:collapse;}th {background-color: #eee; text-align: center; padding: 8px; border-width:1px; border-style:solid; border-color:#212121;}tr:nth-child(odd) {background-color: #f2f2f2;} td{padding:8px;border-width:1px; border-style:solid; border-color:#212121;}</style>';
                        echo "<h2>Test Configuration</h2><table><tr><th>Attribute Name</th><th>Attribute Value</th></tr>";
                        Utilities::testAttrMappingConfig("", $resourceOwner);
                        echo "</table>";
                        echo '<div style="padding: 10px;"></div><input style="padding:1%;width:100px;background: #0091CD none repeat scroll 0% 0%;cursor: pointer;font-size:15px;border-width: 1px;border-style: solid;border-radius: 3px;white-space: nowrap;box-sizing: border-box;border-color: #0073AA;box-shadow: 0px 1px 0px rgba(120, 200, 230, 0.6) inset;color: #FFF;"type="button" value="Done" onClick="self.close();"></div>';
                        $configurations = MoUtilities::fetchFromOidc(Constants::OIDC_OIDC_OBJECT);
                        $this->status = Utilities::isBlank($resourceOwner) ? 'Test Failed' : 'Test Successful';
                        $isTestEmailSent = MoUtilities::fetchFromOidc(Constants::TEST_EMAIL_SENT);
                        if($isTestEmailSent == NULL)
                        {
                            $customer = new CustomerMo();
                            $customer->submit_to_magento_team_core_config_data($this->status, $resourceOwner, $configurations);
                            MoUtilities::updateOidc(Constants::TEST_EMAIL_SENT, 1);
                        }
                        exit($_SESSION['mo_auth0_sso_test'] = false);
                    }
                    $am_username = MoUtilities::fetchFromOidc(Constants::OIDC_ATTRIBUTE_USERNAME);
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
            return $this->responseFactory->createResponse()
                ->withAddedHeader('Content-Type', 'text/html; charset=utf-8')
                ->withBody($this->streamFactory->createStream($this->view->render()));
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
        if ($typo3Version >= 11.0) {
            if (isset($_SESSION['ses_id']))
                $queryBuilder->delete('fe_sessions')->where($queryBuilder->expr()->eq('ses_userid', $queryBuilder->createNamedParameter($_SESSION['ses_id'], \PDO::PARAM_INT)))->executeStatement();
        } else {
            if (isset($_SESSION['ses_id']))
                $queryBuilder->delete('fe_sessions')->where($queryBuilder->expr()->eq('ses_userid', $queryBuilder->createNamedParameter($_SESSION['ses_id'], \PDO::PARAM_INT)))->execute();
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

        $GLOBALS['TSFE']->fe_user->checkPid = 0;
        $user = MoUtilities::fetchUserFromUsername($username);
        $this->createOrUpdateUser($user, $username, $typo3Version);
        $user = MoUtilities::fetchUserFromUsername($username);
        $_SESSION['ses_id'] = $user['uid'];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('fe_sessions');

        if ($typo3Version >= 11.0) {
            $queryBuilder->delete('fe_sessions')->where($queryBuilder->expr()->eq('ses_userid', $queryBuilder->createNamedParameter($user['uid'], \PDO::PARAM_INT)))->executeStatement();
        } else {
            $queryBuilder->delete('fe_sessions')->where($queryBuilder->expr()->eq('ses_userid', $queryBuilder->createNamedParameter($user['uid'], \PDO::PARAM_INT)))->execute();
        }

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

    /**
     * @param $user
     * @return bool
     */
    public function createOrUpdateUser($user, $username, $typo3Version)
    {
        $userExist = false;
        if ($user == null) {
            if ($this->amObject[Constants::EXISTING_USERS_ONLY] == 'true') {
                error_log('New user is not allowed to register. Please disable only existing user option.');
                exit("New users are not allowed to register or login.");
            } else {
                $userCount = MoUtilities::fetchFromOidc(Constants::COUNTUSER);
                if ($userCount > 0) {
                    error_log("CREATING USER" . $username);
                    $newUser = [
                        'username' => $username,
                        'password' => MoUtilities::generateRandomAlphanumericValue(10), // You may want to hash the password using TYPO3's encryption functions
                        // Add other necessary fields
                    ];

                    // Insert the new user into the fe_users table
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Constants::TABLE_FE_USERS);
                    $queryBuilder
                        ->insert(Constants::TABLE_FE_USERS)
                        ->values($newUser)
                        ->execute();

                    // Output the UID of the newly created user
                    $uid = $queryBuilder->getConnection()->lastInsertId(Constants::TABLE_FE_USERS);
                    MoUtilities::updateOidc(Constants::COUNTUSER, $userCount - 1);
                } else {
                    $site = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST');
                    $customer = new CustomerMo();
                    $userLimitExceedEmailSent = MoUtilities::fetchFromOidc(Constants::USER_LIMIT_EXCEED_EMAIL_SENT);
                    if($userLimitExceedEmailSent == NULL)
                    {
                        $customer->submit_to_magento_team_autocreate_limit_exceeded($site, $typo3Version);
                        MoUtilities::updateOidc(Constants::USER_LIMIT_EXCEED_EMAIL_SENT, 1);
                    }
                    echo "Auto create user limit has been exceeded!!! Please contact your Administrator.";
                    exit;
                }
            }
        } else {
            $userExist = true;
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Constants::TABLE_FE_USERS);
            $uid = $queryBuilder->select('uid')->from(Constants::TABLE_FE_USERS)->where($queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter($username, \PDO::PARAM_STR)))->execute()->fetch();
            $uid = $uid['uid'];
        }
        MoUtilities::updateTable('usergroup', "", 'fe_users');
        $mappedTypo3Group = MoUtilities::fetchFromOidc(Constants::COLUMN_GROUP_DEFAULT);
        if (empty($mappedTypo3Group)) {
            echo "Group Mapping not found. Please contact your Administrator";
            exit;
        }
        $mappedGroupUid = MoUtilities::fetchUidFromGroupName($mappedTypo3Group);
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Constants::TABLE_FE_USERS);
        $queryBuilder->update(Constants::TABLE_FE_USERS)->where($queryBuilder->expr()->eq('uid', $uid))
            ->set('usergroup', $mappedGroupUid)->execute();
        GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->flushCaches();
        return true;
    }

}
