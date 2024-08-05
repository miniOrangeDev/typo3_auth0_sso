<?php


namespace Miniorange\Auth0SSO\Helper;

class Messages
{
//General Flow Messages
    const ERROR_OCCURRED = 'An error occured while processing your request. Please try again.';

    //Licensing Messages
    const INVALID_LICENSE = 'Invalid domain or credentials.';

    //cURL Error
    const CURL_ERROR = 'ERROR: <a href="http://php.net/manual/en/curl.installation.php" target="_blank">PHP cURL extension</a> 
                                            is not installed or disabled. Query submit failed.';

    //Save Settings Error
    const ISSUER_EXISTS = 'You seem to already have an Identity Provider for that issuer configured under : <i>{{name}}</i>';
    const NO_IDP_CONFIG = 'Please check and make sure your Plugin Settings are configured properly.';

    const SETTINGS_SAVED = 'Settings saved successfully.';


    /**
     * Parse the message
     * @param $message
     * @param array $data
     * @return mixed
     */
    public static function parse($message, $data = array())
    {
        $message = constant("self::" . $message);
        foreach ($data as $key => $value) {
            $message = str_replace("{{" . $key . "}}", $value, $message);
        }
        return $message;
    }
}