<?php

namespace Miniorange\Auth0SSO\Helper;

class Constants
{

    //images
    const IMAGE_RIGHT = 'right.png';
    const IMAGE_WRONG = 'wrong.png';
    const HOSTNAME = "https://login.xecurify.com";
    const TYPE_OPENID_CONNECT = 'OpenID Connect';

    //TABLE NAMES
    const TABLE_CUSTOMER = 'mo_customer';
    const TABLE_OIDC = 'mo_oidc';
    const TABLE_FE_USERS = 'fe_users';

    // COLUMNS IN CUSTOMER_TABLE
    const CUSTOMER_EMAIL = "cust_email";
    const CUSTOMER_KEY = "cust_key";
    const CUSTOMER_API_KEY = "cust_api_key";
    const CUSTOMER_TOKEN = "cust_token";
    const CUSTOMER_REGSTATUS = "cust_reg_status";
    const CUSTOMER_CODE = "cust_code";
    const CUSTOMER_OBJECT = "cust_object";
    const LICENSE_KEY = "license_key";

    //DATABASE COLUMN IN OIDC_TABLE
    const OIDC_APP_TYPE = 'app_type';
    const OIDC_APP_NAME = 'app_name';// varchar (100) DEFAULT '' NOT NULL ,
    const OIDC_FEOIDC_URL = 'feoidc_url'; //varchar (100) DEFAULT '' NOT NULL,
    const OIDC_REDIRECT_URL = 'redirect_url'; //varchar (100) DEFAULT '' NOT NULL,
    const OIDC_CLIENT_ID = 'client_id'; //varchar (100) DEFAULT '' NOT NULL,
    const OIDC_CLIENT_SECRET = 'client_secret'; //varchar (1500) DEFAULT '',
    const OIDC_SCOPE = 'scope'; //varchar (100) DEFAULT '' ,
    const OIDC_AUTH_URL = 'auth_endpoint';// varchar (100) DEFAULT '' NOT NULL,
    const OIDC_ATTRIBUTE_USERNAME = 'oidc_am_username';//  varchar (100) DEFAULT '' ,
    const OIDC_OIDC_OBJECT = 'oidc_object';// text DEFAULT '',
    const OIDC_USER_OBJECT = 'user_object';// text DEFAULT '',
    const COLUMN_GROUP_DEFAULT = 'defaultGroup';
    const COUNTUSER = 'countuser';
    const EMAIL_SENT = 'isEmailSent';
    const TEST_EMAIL_SENT = 'isTestEmailSent';
    const USER_LIMIT_EXCEED_EMAIL_SENT = 'isUserLimitExceedEmailSent';

    //ATTRIBUTE TABLE COLUMNS
    const ATTRIBUTE_USERNAME = 'am_username';
    const EXISTING_USERS_ONLY = 'existing_users_only';

    //ACCOUNT CONSTANTS
    const AUTH_CODE_GRANT = "authorization code";
    const DEFAULT_CUSTOMER_KEY = "16555";
    const DEFAULT_API_KEY = "fFd2XcvTGDemZvbw1bcUesNJWEqKbbUq";

    const AREA_OF_INTEREST = "TYPO3 OpenID Connect Client";

}