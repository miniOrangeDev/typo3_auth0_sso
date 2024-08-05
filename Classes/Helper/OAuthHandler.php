<?php

namespace Miniorange\Auth0SSO\Helper;

class OAuthHandler
{

    public static $grantTypes = array("Authorization Code" => "authorization_code");

    function getAccessToken($tokenendpoint, $grant_type, $clientid, $clientsecret, $code, $redirect_url, $send_headers, $send_body)
    {
        $response = $this->getToken($tokenendpoint, $grant_type, $clientid, $clientsecret, $code, $redirect_url, $send_headers, $send_body);
        error_log("response received in getAccessToken: " . print_r($response, true));
        $content = is_array($response) ? $response : json_decode($response, true);
        if (isset($content["access_token"])) {
            return $content["access_token"];
        } else {
            echo 'Invalid response received from OAuth Provider. Contact your administrator for more details.<br><br><b>Response : </b><br>' . $response;
            exit;
        }
    }

    function getToken($tokenendpoint, $grant_type, $clientid, $clientsecret, $code, $redirect_url, $send_headers, $send_body)
    {
        $ch = $this->prepareCurlOptions($tokenendpoint);
        if ($send_headers) {
            error_log("in header");
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization: Basic ' . base64_encode($clientid . ":" . $clientsecret),
                'Accept: application/json'
            ));
            curl_setopt($ch, CURLOPT_POSTFIELDS, 'redirect_uri=' . urlencode($redirect_url) . '&grant_type=' . $grant_type . '&code=' . $code);

        } else {
            error_log("in header/body");
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json'
            ));
            curl_setopt($ch, CURLOPT_POSTFIELDS, 'redirect_uri=' . urlencode($redirect_url) . '&grant_type=' . $grant_type . '&client_id=' . $clientid . '&client_secret=' . $clientsecret . '&code=' . $code);
        }

        $response = curl_exec($ch);
        error_log("response getToken: " . print_r($response, true));

        if (curl_error($ch)) {
            echo "<b>Response : </b><br>";
            print_r($response);
            echo "<br><br>";
            exit(curl_error($ch));
        }

        if (!is_array(json_decode($response, true))) {
            echo "<b>Response : </b><br>";
            print_r($response);
            echo "<br><br>";
            exit("Invalid response received.");
        }

        $response = json_decode($response, true);

        if (isset($response["error"])) {
            if (is_array($response["error"])) {
                $response["error"] = $response["error"]["message"];
            }
            exit($response["error"]);
        } else if (isset($response["error_description"])) {
            exit($response["error_description"]);
        }
        error_log("decoded response: " . print_r($response, true));
        return $response;
    }

    function prepareCurlOptions($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        return $ch;
    }

    function getIdToken($tokenendpoint, $grant_type, $clientid, $clientsecret, $code, $redirect_url, $send_headers, $send_body)
    {
        $content = $this->getToken($tokenendpoint, $grant_type, $clientid, $clientsecret, $code, $redirect_url, $send_headers, $send_body);
        error_log("response received in getIdToken: " . print_r($content, true));
        if (isset($content["id_token"]) || isset($content["access_token"])) {
            return $content;
            exit;
        } else {
            echo 'Invalid response received from OpenId Provider. Contact your administrator for more details.<br><br><b>Response : </b><br>' . $content;
            exit;
        }
    }

    function getResourceOwnerFromIdToken($id_token)
    {
        $id_array = explode(".", $id_token);
        error_log('exploded id token: ' . print_r($id_array, true));
        if (isset($id_array[1])) {
            error_log('id_array[1] set: ');
            $id_body = $this->base64url_decode($id_array[1]);
            error_log('id_body set: ' . print_r($id_body, true));
            if(isset($id_body) && !empty($id_body) && is_array(json_decode((string)$id_body, true))){
                return json_decode((string)$id_body,true);
            }
            error_log('id_body is not an array');
        }
        echo 'Invalid response received.<br><b>Id_token : </b>' . $id_token;
        exit;
    }

    function base64url_decode($base64url)
    {
        $base64 = strtr($base64url, '-_', '+/');
        $plainText = base64_decode($base64);
        return ($plainText);
    }


    function getResourceOwner($resourceownerdetailsurl, $access_token)
    {
        $ch = $this->prepareCurlOptions($resourceownerdetailsurl);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $access_token));

        $response = curl_exec($ch);
        $content = json_decode($response, true);
        error_log("userinfo response array: " . print_r($content, true));
        if (isset($content["error_description"])) {
            exit($content["error_description"]);
        } else if (isset($content["error"])) {
            exit($content["error"]);
        }
        return $content;
    }

}

