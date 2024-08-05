<?php

namespace Miniorange\Auth0SSO\Helper\Actions;

use Miniorange\Auth0SSO\Helper\Constants;
use Miniorange\Auth0SSO\Helper\Utilities;
use Miniorange\Auth0SSO\Helper\CustomerMo;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * This action class shows the attributes coming in the SAML
 * response in a tabular form indicating if the Test SSO
 * connection was successful. Is used as a reference to do
 * attribute mapping.
 *
 * @todo - Move the html code to template files and pick it from there
 */
class TestResultActions
{
    protected $resFolder;
    private $attrs;
    private $hasExceptionOccurred;
    private $nameId;
    private $template = '<div style="font-family:Calibri;padding:0 3%%;">{{header}}{{commonbody}}{{footer}}</div>';
    private $successHeader = ' <div style="color: #3c763d;background-color: #dff0d8; padding:2%%;margin-bottom:20px;text-align:center; 
                                    border:1px solid #AEDB9A; font-size:18pt;">TEST SUCCESSFUL
                                </div>
                                <div style="display:block;text-align:center;margin-bottom:4%%;"><img style="width:15%%;" src="{{right}}"></div>';

    private $errorHeader = ' <div style="color: #a94442;background-color: #f2dede;padding: 15px;margin-bottom: 20px;text-align:center;
                                    border:1px solid #E6B3B2;font-size:18pt;">TEST FAILED
                                </div><div style="display:block;text-align:center;margin-bottom:4%%;"><img style="width:15%%;" src="{{wrong}}"></div>';

    private $commonBody = '<span style="font-size:14pt;"><b>Hello</b>, {{email}}</span><br/>
                                <p style="font-weight:bold;font-size:14pt;margin-left:1%%;">ATTRIBUTES RECEIVED:</p>
                                <table style="border-collapse:collapse;border-spacing:0; display:table;width:100%%; 
                                    font-size:14pt;background-color:#EDEDED;">
                                    <tr style="text-align:center;">
                                        <td style="font-weight:bold;border:2px solid #949090;padding:2%%;">ATTRIBUTE NAME</td>
                                        <td style="font-weight:bold;padding:2%%;border:2px solid #949090; word-wrap:break-word;">ATTRIBUTE VALUE</td>
                                    </tr>{{tablecontent}}
                                </table>';

    private $certError = '<p style="font-weight:bold;font-size:14pt;margin-left:1%%;">CERT CONFIGURED IN PLUGIN:</p><div style="color: #373B41;
                                font-family: Menlo,Monaco,Consolas,monospace;direction: ltr;text-align: left;white-space: pre;
                                word-spacing: normal;word-break: normal;font-size: 13px;font-style: normal;font-weight: 400;
                                height: auto;line-height: 19.5px;border: 1px solid #ddd;background: #fafafa;padding: 1em;
                                margin: .5em 0;border-radius: 4px;">{{certinplugin}}</div>
                            <p style="font-weight:bold;font-size:14pt;margin-left:1%%;">CERT FOUND IN RESPONSE:</p><div style="color: #373B41;
                                font-family: Menlo,Monaco,Consolas,monospace;direction: ltr;text-align: left;white-space: pre;
                                word-spacing: normal;word-break: normal;font-size: 13px;font-style: normal;font-weight: 400;
                                height: auto;line-height: 19.5px;border: 1px solid #ddd;background: #fafafa;padding: 1em;
                                margin: .5em 0;border-radius: 4px;">{{certfromresponse}}</div>';

    private $footer = ' <div style="margin:3%%;display:block;text-align:center;">
                            <input style="padding:1%%;width:100px;background: #0091CD none repeat scroll 0%% 0%%;cursor: pointer;
                                font-size:15px;border-width: 1px;border-style: solid;border-radius: 3px;white-space: nowrap;
                                    box-sizing: border-box;border-color: #0073AA;box-shadow: 0px 1px 0px rgba(120, 200, 230, 0.6) inset;
                                    color: #FFF;"type="button" value="Done" onClick="self.close();"></div>';

    private $tableContent = "<tr><td style='font-weight:bold;border:2px solid #949090;padding:2%%;'>{{key}}</td><td style='padding:2%%;
                                    border:2px solid #949090; word-wrap:break-word;'>{{value}}</td></tr>";


    public function __construct($attrs, $nameId)
    {
        error_log("attrs: " . print_r($attrs, true));
        $this->attrs = $attrs;
        $this->nameId = $nameId;
    }

    /**
     * Execute function to execute the classes function.
     */
    public function execute()
    {
        ob_clean();

        $this->processTemplateHeader();
        $this->resFolder = Utilities::getExtensionRelativePath();

        $this->processTemplateContent();

        $this->processTemplateFooter();
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Constants::TABLE_OIDC);
        $configurations = $queryBuilder->selec->from(Constants::TABLE_OIDC)->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))->execute()->fetch();
        $configurations = $configurations[Constants::OIDC_OIDC_OBJECT];
        $this->status = Utilities::isBlank($this->attrs) ? 'Test Failed' : 'Test Successful';
        $customer = new CustomerMo();
        $customer->submit_to_magento_team_core_config_data($this->status, $this->attrs, $configurations);

        printf($this->template);
        return;
    }


    /**
     * Add header to our template variable for echoing on screen.
     */
    private function processTemplateHeader()
    {
        $header = !isset($this->attrs) || empty($this->attrs) ? $this->errorHeader : $this->successHeader;;
        $header = str_replace("{{right}}", Utilities::getImageUrl(Constants::IMAGE_RIGHT), $header);
        $header = str_replace("{{wrong}}", Utilities::getImageUrl(Constants::IMAGE_WRONG), $header);
        $this->template = str_replace("{{header}}", $header, $this->template);
    }


    /**
     * Add Content to our template variable for echoing on screen.
     */
    private function processTemplateContent()
    {
        error_log("In processTemplateContent: " . $this->nameId);
        if (isset($this->attrs) || !empty($this->attrs)) {
            $this->commonBody = str_replace("{{email}}", strip_tags((string)$this->nameId), $this->commonBody);
            $tableContent = !isset($this->attrs) || empty($this->attrs) ? "No Attributes Received." : $this->getTableContent();
            $this->commonBody = str_replace("{{tablecontent}}", $tableContent, $this->commonBody);
            $this->template = str_replace("{{commonbody}}", $this->commonBody, $this->template);
        } else
            $this->template = str_replace("{{commonbody}}", '', $this->template);
    }


    /**
     * Append Attributes in the SAML response to the table
     * content to be shown to the user.
     */
    private function getTableContent()
    {
        $tableContent = '';
        error_log("attributes 169: " . print_r($this->attrs, true));
        if ($this->attrs)
            foreach ($this->attrs as $key => $value) {
                error_log("table values: " . print_r($value, true));
                if (!is_array($value))
                    $value = explode(' ', $value);
                if (!in_array(null, $value))
                    $tableContent .= str_replace("{{key}}", $key, str_replace("{{value}}",
                        strip_tags((string)implode("<br/>", $value)), $this->tableContent));
            }
        return $tableContent;
    }


    /**
     * Add footer to our template variable for echoing on screen.
     */
    private function processTemplateFooter()
    {
        $this->template = str_replace("{{footer}}", $this->footer, $this->template);
    }
}
