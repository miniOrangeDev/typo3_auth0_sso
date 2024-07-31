This is a Typo3 extension from miniOrange Inc. for Auth0 Single-Sign-On (SSO).
Feel free to point out any bugs or issues.
For any query or to enable premium features, contact us through the support form in the extension itself.
Also, you can email magentosupport@xecurify.com or visit https://www.miniorange.com/contact.

Installation Instructions:

1. Composer Installation:
    Run the below command to install the extension:
    - composer require miniorange/auth0_sso

            OR

2. Manual Installation:
    - Unzip the plugin zip into the typo3conf/ext folder and activate the extension from the Extensions section in Typo3.

3. After installing the extension, apply the database changes, if not applied automatically.

4. Create the two standard pages as feoidc and response and add the feoidc and response pages, respectively, to them.

5. Once the extension is installed successfully, navigate to the OpenID Connect Client tab of the plugin and fill in all the required fields as below:
    - OAuth/ OpenID Provider Name: {Name of your provider (e.g., Auth0)}
    - Application type: OAuth/OpenID Connect
    - Frontend Redirect/Callback Url : {Response Plugin Page URL which you created in step 4} (You will need to provide this URL to your
      OAuth/OIDC provider i.e., Auth0)
    - feoidc page URL: {feoidc Plugin Page URL which you created in step 4}
    - Client ID : {You will get it from your provider, i.e., auth0}
    - Client Secret : {You will get it from your provider, i.e., auth0}
    - Scope : openid profile email
    - Authorization Endpoint : https:// < your Auth0 domain > /authorize
    - Token Endpoint : https:// < your Auth0 domain > /oauth/token
    - User Info Endpoint : https:// < your Auth0 domain > /userinfo
    - Set client credentials in : Header/Body

6. Provide the redirect/callback URL in your Keyclaok application by copying it from Frontend Redirect/Callback Url field in OpenID Connect Client tab.

7. Once you are done with the configurations on both ends (i.e., Typo3 and Auth0), click on the Test Configuration button in the OpenID Connect Client tab of the plugin and check if you are able to test it successfully.

8. Navigate to the Attribute Mapping tab and map the Username attribute to the auth0 attribute using which you want to identify the users in Typo3 (you can find all the attributes received from auth0 in the test configuration).

9. Navigate to the Group Mapping tab of the plugin and save the Group Mapping for Frontend Users by selecting the Default Usergroup.

10. Once you have done all the above steps, you are ready to test the SSO. You can use your Feoidc Page URL in order to initiate the SSO.

You can refer to the below guide link for more information:
https://plugins.miniorange.com/auth0-single-sign-on-typo3-sso-oauth-openid-connect