Configuration
=============

Pre-requirements Before Configuring Extension
*********************************************

You must have atleast two pages before configuring the extension.

.. tip:: Pages can be created by the following steps:-

* Navigate to the Pages section and create new standard page with name feoidc and add feoidc plugin to it.
* Similarly create a page named response and and response plugin to it.
* You can refer to the below screenshots for the same.
.. image:: Images/feoidc.png
    :alt: Feoidc Page
.. image:: Images/response.png
    :alt: Response Page
* Create SSO Login button in your Typo3 Frontend Site and embed the feoidc page URL to it in order to initiate the SSO.


Extension Configurations
************************

* Once the extension is installed successfully, navigate to the OpenID Connect Client tab of the plugin and fill in all the required fields as below:
	- OAuth/ OpenID Provider Name: {Name of your provider (e.g., Auth0)}
	- Application type: OAuth/OpenID Connect
	- Frontend Redirect/Callback Url : {Response Plugin Page URL which you created in earlier steps} (You will need to provide this URL to your
	  OAuth/OIDC provider i.e., auth0)
	- feoidc page URL: {feoidc Plugin Page URL which you created in earlier steps}
	- Client ID : {You will get it from your provider, i.e., auth0}
	- Client Secret : {You will get it from your provider, i.e., auth0}
	- Scope : openid profile email
	- Authorization Endpoint : https:// < your Auth0 domain > /authorize
	- Token Endpoint : https:// < your Auth0 domain > /oauth/token
	- User Info Endpoint : 	https:// < your Auth0 domain > /userinfo
	- Set client credentials in : Header/Body

.. figure:: Images/configurations.png
   :alt: OpenID Connect Client Configurations

* Provide the redirect/callback URL in your Auth0 application by copying it from Frontend Redirect/Callback Url field in OpenID Connect Client tab.
.. figure:: Images/callback.png
   :alt: Auth0 Callback/Redirect URL

* Once you are done with the configurations on both ends (i.e., Typo3 and Auth0), click on the Test Configuration button in the OpenID Connect Client tab of the plugin and check if you are able to test it successfully.
.. figure:: Images/TestConfiguration.png
   :alt: Test Configuration Window

* Navigate to the Attribute Mapping tab and map the Username attribute to the Auth0 attribute using which you want to identify the users in Typo3 (you can find all the attributes received from Auth0 in the test configuration).
.. figure:: Images/AttributeMapping.png
   :alt: Attribute Mapping Settings

* Navigate to the Group Mapping tab of the plugin and save the Group Mapping for Frontend Users by selecting the Default Usergroup.
.. figure:: Images/RoleMapping.png
   :alt: Role Mapping Settings

* Once you have done all the above steps, you are ready to test the SSO. You can use your Feoidc Page URL in order to initiate the SSO.

You can refer to the below guide link for more information:
https://plugins.miniorange.com/auth0-single-sign-on-typo3-sso-oauth-openid-connect