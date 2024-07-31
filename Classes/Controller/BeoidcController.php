<?php

namespace Miniorange\Auth0SSO\Controller;

use Exception;
use Miniorange\Auth0SSO\Helper\MoUtilities;
use Miniorange\Auth0SSO\Helper\CustomerMo;
use Miniorange\Auth0SSO\Helper\Constants;
use Miniorange\Auth0SSO\Helper\Utilities;
use Miniorange\Auth0SSO\Helper\OAuthHandler;
use PDO;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Core\Information\Typo3Version;
use Miniorange\Auth0SSO\Domain\Repository\UserGroup\FrontendUserGroupRepository;
use Miniorange\Auth0SSO\Domain\Repository\UserGroup\BackendUserGroupRepository;

/**
 *BeoidcController
 */
class BeoidcController extends ActionController
{

    protected $response = null;
    protected $tab = "";
    private $myjson = null;

    /**
     * @throws Exception
     */
    public function requestAction()
    {
        $customer = new CustomerMo();
        $version = new Typo3Version();
        $typo3Version = $version->getVersion();
        $send_email = MoUtilities::fetchFromOidc(Constants::EMAIL_SENT);

        if ($send_email == NULL) {
            $site = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST');
            $values = array($site);
            $email = !empty($GLOBALS['BE_USER']->user['email']) ? $GLOBALS['BE_USER']->user['email'] : $GLOBALS['BE_USER']->user['username'];
            $customer->submit_to_magento_team($email, 'Installed Successfully', $values, $typo3Version);
            MoUtilities::updateOidc(Constants::COUNTUSER, 10);
            MoUtilities::updateOidc(Constants::EMAIL_SENT, 1);
            GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->flushCaches();
        }

        error_log("BeoidcController.php : In requestAction");
        if (isset($_POST['option'])) {
            if (empty($_POST['app_type'])) {
                $_POST['app_type'] = Constants::TYPE_OPENID_CONNECT;
            }
        }

//------------ OPENID CONNECT SETTINGS---------------
        if (isset($_POST['option']) and $_POST['option'] == "oidc_settings") {
            if (!empty($_POST['redirect_url']) && !empty($_POST['feoidc']) && !empty($_POST['app_name']) && !empty($_POST['client_id']) && !empty($_POST['client_secret']) && !empty($_POST['scope']) && !empty($_POST['auth_endpoint']) && !empty($_POST['token_endpoint'])) {
                if (empty($_POST['set_body_credentials'])) {
                    $_POST['set_body_credentials'] = 'false';
                }
                if (empty($_POST['set_header_credentials'])) {
                    $_POST['set_header_credentials'] = 'false';
                }
                $value1 = $this->validateURL($_POST['feoidc']);
                $value2 = $this->validateURL($_POST['redirect_url']);
                $value3 = $this->validateURL($_POST['auth_endpoint']);
                $value4 = $this->validateURL($_POST['token_endpoint']);
                if ($value1 == 1 && $value2 == 1 && $value3 == 1 && $value4 == 1) {
                    $this->defaultSettings($_POST);
                    MoUtilities::showSuccessFlashMessage('Settings saved successfully.');
                } else {
                    MoUtilities::showErrorFlashMessage('Incorrect configurations!');
                }
            } else {
                MoUtilities::showErrorFlashMessage('Please fill all the required fields!');
            }
        } //------------ HANDLING SUPPORT QUERY---------------
        elseif (isset($_POST['option']) and $_POST['option'] == "mo_auth0_sso_contact_us_query_option") {
            $this->support();
        } //------------ ATTRIBUTE MAPPING---------------
        elseif (isset($_POST['option']) and $_POST['option'] == "attribute_mapping") {
            $username = $_POST['oidc_am_username'];

            if (!MoUtilities::isEmptyOrNull($username)) {
                if (MoUtilities::fetchFromOidc('uid') == null) {
                    MoUtilities::showErrorFlashMessage('Please configure OAuth / OpenID Connect client first.');
                } else {
                    MoUtilities::updateOidc(Constants::OIDC_ATTRIBUTE_USERNAME, $username);
                    MoUtilities::showSuccessFlashMessage('Attribute Mapping saved successfully.');
                }
            } else {
                MoUtilities::showErrorFlashMessage('Please provide valid input.');
            }
        } //------------GROUP MAPPING SETTINGS---------------
        elseif (isset($_POST['option']) and $_POST['option'] == "group_mapping") {

            MoUtilities::updateOidc(Constants::COLUMN_GROUP_DEFAULT, $_POST['defaultUserGroup']);
            MoUtilities::showSuccessFlashMessage('Group Mapping saved successfully!');
        } //------------ VERIFY CUSTOMER---------------
        elseif (isset($_POST['option']) and $_POST['option'] == "mo_oidc_verify_customer") {
            $this->account($_POST);
        } //------------ HANDLE LOG OUT ACTION---------------
        elseif (isset($_POST['option']) and $_POST['option'] == 'logout') {
            $this->removeCustomer();
            MoUtilities::showSuccessFlashMessage('You account is removed successfully.');
        }

//------------ CHANGING TABS---------------

        if (!empty($_POST['option'])) {
            if ($_POST['option'] == 'oidc_settings' || $_POST['option'] == '') {
                $this->tab = "OIDC_Settings";
            } elseif ($_POST['option'] == 'attribute_mapping') {
                $this->tab = "Attribute_Mapping";
            } elseif ($_POST['option'] == 'group_mapping') {
                $this->tab = "Group_Mapping";
            } else {
                $this->tab = "Account";
            }
        }

        //UserGroups
        $allUserGroups = GeneralUtility::makeInstance('Miniorange\\Auth0SSO\\Domain\\Repository\\UserGroup\\FrontendUserGroupRepository')->findAll();
        $this->view->assign('allUserGroups', $allUserGroups);

        //BackendUserGroups
        $beallUserGroups = GeneralUtility::makeInstance('Miniorange\\Auth0SSO\\Domain\\Repository\\UserGroup\\BackendUserGroupRepository')->findAll();
        $this->view->assign('beallUserGroups', $beallUserGroups);

        $oidc_object = Utilities::fetchFromTable(Constants::OIDC_OIDC_OBJECT, Constants::TABLE_OIDC);
        $oidc_object = is_array($oidc_object) ? $oidc_object : json_decode($oidc_object, true);
        $this->view->assign('conf', $oidc_object);
        $this->view->assign('defaultGroup', Utilities::fetchFromTable(Constants::COLUMN_GROUP_DEFAULT, Constants::TABLE_OIDC));
        $this->view->assign('am_username', MoUtilities::fetchFromOidc(Constants::OIDC_ATTRIBUTE_USERNAME));
        $this->view->assign('tab', $this->tab);

        if (isset($oidc_object) && !empty($oidc_object)) {
            $feoidc_validated = $this->validateURL($oidc_object['feoidc']);
            $redirect_validated = $this->validateURL($oidc_object['redirect_url']);
            $authorize_validated = $this->validateURL($oidc_object['auth_endpoint']);
            $token_validated = $this->validateURL($oidc_object['token_endpoint']);
            if ($oidc_object['app_type'] == 'OAuth') {
                $value = $this->validateURL($oidc_object['user_info_endpoint']);

                if ($value == 1 && $feoidc_validated == 1 && $redirect_validated == 1 && $authorize_validated == 1 && $token_validated == 1) {
                    $this->view->assign('test_enabled', '');
                } else {
                    $this->view->assign('test_enabled', 'disabled');
                }
                $this->view->assign('userinfo', 'display:block');
            } else {
                if ($feoidc_validated == 1 && $redirect_validated == 1 && $authorize_validated == 1 && $token_validated == 1) {
                    $this->view->assign('test_enabled', '');
                } else {
                    $this->view->assign('test_enabled', 'disabled');
                }
                $this->view->assign('userinfo', 'display:none');
            }
        } else {
            $this->view->assign('test_enabled', 'disabled');
            $this->view->assign('userinfo', 'display:none');
        }

//------------ LOADING VARIABLES TO BE USED IN VIEW---------------

        if (MoUtilities::fetch_cust(Constants::CUSTOMER_REGSTATUS) == 'logged') {
            $this->view->assign('status', 'logged');
            $this->view->assign('log', '');
            $this->view->assign('nolog', 'display:none');
            $this->view->assign('email', MoUtilities::fetch_cust('cust_email'));
            $this->view->assign('key', MoUtilities::fetch_cust('cust_key'));
            $this->view->assign('token', MoUtilities::fetch_cust('cust_token'));
            $this->view->assign('api_key', MoUtilities::fetch_cust('cust_api_key'));
        } else {
            $this->view->assign('log', 'disabled');
            $this->view->assign('nolog', 'display:block');
            $this->view->assign('status', 'not_logged');
        }
        GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->flushCaches();
        if ($typo3Version >= 11.5) {
            return $this->responseFactory->createResponse()
                ->withAddedHeader('Content-Type', 'text/html; charset=utf-8')
                ->withBody($this->streamFactory->createStream($this->view->render()));
        }
    }

    //  LOGOUT CUSTOMER

    public function validateURL($url)
    {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * @param $postArray
     */
    public function defaultSettings($postArray)
    {

        error_log("In BeoidcController : defaultSettings: ");
        $this->oidc_object = json_encode($postArray);
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Constants::TABLE_OIDC);
        $uid = $queryBuilder->select('uid')->from(Constants::TABLE_OIDC)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))
            ->execute()->fetch();
        if ($uid == null) {
            $affectedRows = $queryBuilder
                ->insert(Constants::TABLE_OIDC)
                ->values([
                    Constants::OIDC_OIDC_OBJECT => $this->oidc_object])
                ->execute();
        } else {
            $queryBuilder->update(Constants::TABLE_OIDC)->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))->set(Constants::OIDC_OIDC_OBJECT, $this->oidc_object)->execute();
        }
    }

    public function support()
    {
        if (!$this->mo_is_curl_installed()) {
            MoUtilities::showErrorFlashMessage('ERROR: <a href="http://php.net/manual/en/curl.installation.php" 
                       target="_blank">PHP cURL extension</a> is not installed or disabled. Query submit failed.');
            return;
        }
        // Contact Us query
        $email = $_POST['mo_auth0_sso_contact_us_email'];
        $phone = $_POST['mo_auth0_sso_contact_us_phone'];
        $query = $_POST['mo_auth0_sso_contact_us_query'];

        $customer = new CustomerMo();

        if ($this->mo_check_empty_or_null($email) || $this->mo_check_empty_or_null($query)) {
            MoUtilities::showErrorFlashMessage('Please enter a valid Email address. ');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            MoUtilities::showErrorFlashMessage('Please enter a valid Email address. ');
        } else {
            $submitted = $customer->submit_contact($email, $phone, $query) ? json_decode($customer->submit_contact($email, $phone, $query), true) : $customer->submit_contact($email, $phone, $query);
            if ($submitted['status'] == 'SUCCESS') {
                MoUtilities::showSuccessFlashMessage('Support query sent ! We will get in touch with you shortly.');
            } else {
                MoUtilities::showErrorFlashMessage('Could not send query. Please try again later or mail us at info@xecurify.com');
            }
        }
    }

    //   HANDLE LOGIN FORM

    public function mo_is_curl_installed()
    {
        if (in_array('curl', get_loaded_extensions())) {
            return 1;
        } else {
            return 0;
        }
    }

//  SAVE CUSTOMER

    /**
     * @param $value
     * @return bool|string
     */

    public function mo_check_empty_or_null($value)
    {
        if (!isset($value) || empty($value)) {
            return true;
        }
        return false;
    }

// ---- UPDATE CUSTOMER Details

    public function account($post)
    {
        $email = $post['email'];
        $password = $post['password'];
        $customer = new CustomerMo();
        $customer->email = $email;
        MoUtilities::update_cust('cust_email', $email);
        $check_customer = $customer->check_customer($email, $password);
        $check_content = isset($check_customer) && !empty($check_customer) ? json_decode((string)$check_customer, true) : array();
        if ($check_content['status'] == 'CUSTOMER_NOT_FOUND') {
            error_log("CUSTOMER_NOT_FOUND.. Creating ...");
            $create_customer = $customer->create_customer($email, $password);
            $result = isset($create_customer) && !empty($create_customer) ? json_decode((string)$create_customer, true) : array();
            if ($result['status'] == 'SUCCESS') {
                $get_customer_key = $customer->get_customer_key($email, $password);
                $key_content = isset($get_customer_key) && !empty($get_customer_key) ? json_decode((string)$get_customer_key, true) : array();
                if ($key_content['status'] == 'SUCCESS') {
                    $this->saveCustomer($key_content, $email);
                    MoUtilities::showSuccessFlashMessage('User created successfully.');
                } else {
                    MoUtilities::showErrorFlashMessage('It seems like you have entered the incorrect password');
                }
            } else {
                MoUtilities::showErrorFlashMessage('Transaction Limit Exceeded!!!');
            }
        } elseif ($check_content['status'] == 'SUCCESS') {
            $get_customer_key = $customer->get_customer_key($email, $password);
            $key_content = isset($get_customer_key) && !empty($get_customer_key) ? json_decode((string)$get_customer_key, true) : array();

            if ($key_content['status']) {
                $this->saveCustomer($key_content, $email);
                MoUtilities::showSuccessFlashMessage('User retrieved successfully.');
            } else {
                MoUtilities::showErrorFlashMessage('It seems like you have entered the incorrect password');
            }
        }
    }

    // FETCH OIDC VALUES

    public function saveCustomer($content, $email)
    {
        $this->updateCustomer(Constants::CUSTOMER_KEY, $content['id']);
        $this->updateCustomer(Constants::CUSTOMER_API_KEY, $content['apiKey']);
        $this->updateCustomer(Constants::CUSTOMER_TOKEN, $content['token']);
        $this->updateCustomer(Constants::CUSTOMER_REGSTATUS, 'logged');
        $this->updateCustomer(Constants::CUSTOMER_EMAIL, $email);
    }

// ---- UPDATE OIDC Settings

    public function updateCustomer($column, $value)
    {
        error_log("In BeoidcController : updateCustomer()");
        if ($this->fetchFromCustomer('id') == null) {
            $this->insertCustomerRow();
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Constants::TABLE_CUSTOMER);
        $queryBuilder->update(Constants::TABLE_CUSTOMER)->where($queryBuilder->expr()->eq('id', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))->set($column, $value)->execute();
    }

    public function fetchFromCustomer($col)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Constants::TABLE_CUSTOMER);
        $variable = $queryBuilder->select($col)->from(Constants::TABLE_CUSTOMER)->where($queryBuilder->expr()->eq('id', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))->execute()->fetch();
        return $variable && $variable[$col] ? $variable[$col] : null;
    }

// --------------------SUPPORT QUERY---------------------

    public function insertCustomerRow()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Constants::TABLE_CUSTOMER);
        $affectedRows = $queryBuilder->insert(Constants::TABLE_CUSTOMER)->values(['id' => '1'])->execute();
    }

    public function removeCustomer()
    {
        $this->updateCustomer(Constants::CUSTOMER_KEY, '');
        $this->updateCustomer(Constants::CUSTOMER_EMAIL, '');
        $this->updateCustomer(Constants::CUSTOMER_TOKEN, '');
        $this->updateCustomer(Constants::CUSTOMER_API_KEY, '');
        $this->updateCustomer(Constants::CUSTOMER_REGSTATUS, '');
    }
}
