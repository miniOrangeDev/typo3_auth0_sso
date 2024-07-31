<?php

namespace Miniorange\Auth0SSO\Helper;

use Exception;
use PDO;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Messaging\Renderer\ListRenderer;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

const SEP = DIRECTORY_SEPARATOR;

class MoUtilities
{
    public static function getHelperDir()
    {
        global $sep;
        $relPath = self::getExtensionRelativePath();
        $sep = substr($relPath, -1);
        $helperFolder = $relPath . 'Helper' . $sep;
        error_log("Relative Resource folder : " . print_r($helperFolder, true));
        return $helperFolder;
    }

    public static function getExtensionRelativePath()
    {
        $extRelativePath = PathUtility::getAbsoluteWebPath(self::getExtensionAbsolutePath());
        return $extRelativePath;
    }

    public static function getExtensionAbsolutePath()
    {
        $extAbsPath = ExtensionManagementUtility::extPath('auth0_sso');
        return $extAbsPath;
    }

    /**
     * Get resource director path
     * rn string
     */
    public static function getResourceDir()
    {
        global $sep;
        $relPath = self::getExtensionRelativePath();
        $sep = substr($relPath, -1);
        $resFolder = $relPath . 'Resources' . $sep;
        error_log("Relative Resource folder : " . print_r($resFolder, true));
        return $resFolder;
    }

    public static function fetchUserFromUsername($username)
    {
        $table = Constants::TABLE_FE_USERS;
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        // Remove all restrictions but add DeletedRestriction again
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $var_uid = $queryBuilder->select('*')->from($table)->where(
            $queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter($username))
        )->execute()->fetch();
        if (null == $var_uid) {
            return false;
        }
        return $var_uid;
    }

    /**
     * --------- UPDATE CUSTOMER DETAILS --------------------------------
     */
    public static function update_cust($column, $value)
    {
        if (self::fetch_cust('id') == null) {
            self::insertValue();
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Constants::TABLE_CUSTOMER);
        $queryBuilder->update(Constants::TABLE_CUSTOMER)->where($queryBuilder->expr()->eq('id', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))->set($column, $value)->execute();
    }

    /**
     *---------FETCH CUSTOMER DETAILS-------------------------
     */
    public static function fetch_cust($col)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Constants::TABLE_CUSTOMER);
        $variable = $queryBuilder->select($col)->from(Constants::TABLE_CUSTOMER)->where($queryBuilder->expr()->eq('id', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))->execute()->fetch();
        return $variable && $variable[$col] ? $variable[$col] : null;
    }

    /**
     *---------INSERT CUSTOMER DETAILS--------------
     */
    public static function insertValue()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Constants::TABLE_CUSTOMER);
        $affectedRows = $queryBuilder->insert(Constants::TABLE_CUSTOMER)->values(['id' => '1'])->execute();
    }

    /**
     * Get Image Resource URL
     */
    public static function getImageUrl($imgFileName)
    {
        error_log("getImageUrl");
        $imageDir = self::getResourceDir() . SEP . 'images' . SEP;
        error_log("resDir : " . $imageDir);
        $iconDir = self::getExtensionRelativePath() . SEP . 'Resources' . SEP . 'Public' . SEP . 'Icons' . SEP;
        error_log("iconDir : " . print_r($iconDir, true));
        return $iconDir . $imgFileName;
    }

    //Check if a value is null or empty
    public static function isEmptyOrNull($t)
    {
        if (!isset($t) || empty($t)) {
            return true;
        }
        return false;
    }

    //------------Fetch UID from Groups
    public static function fetchUidFromGroupName($name, $table = "fe_groups")
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $rows = $queryBuilder->select('uid')
            ->from($table)
            ->where($queryBuilder->expr()->eq('title', $queryBuilder->createNamedParameter($name, \PDO::PARAM_STR)))
            ->execute()
            ->fetch();
        return $rows['uid'];
    }


    // -------------UPDATE TABLE---------------------------------------
    public static function updateTable($col, $val, $table)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->update($table)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))->set($col, $val)
            ->execute();
    }

    //Fetch a value from Database
    public static function fetchFromDb($col, $table)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $columnValue = $queryBuilder->select($col)->from($table)->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))->execute()->fetch();
        return $columnValue && $columnValue[$col] ? $columnValue[$col] : null;
    }

    public static function showErrorFlashMessage($message, $header = "ERROR")
    {
        $message = GeneralUtility::makeInstance(FlashMessage::class, $message, $header, FlashMessage::ERROR);
        $out = GeneralUtility::makeInstance(ListRenderer ::class)->render([$message]);
        echo $out;
    }

    public static function showSuccessFlashMessage($message, $header = "OK")
    {
        $message = GeneralUtility::makeInstance(FlashMessage::class, $message, $header, FlashMessage::OK);
        $out = GeneralUtility::makeInstance(ListRenderer ::class)->render([$message]);
        echo $out;
    }

    public static function generateRandomAlphanumericValue($length)
    {
        $chars = "abcdef0123456789";
        $chars_len = strlen($chars);
        $uniqueID = "";
        for ($i = 0; $i < $length; $i++)
            $uniqueID .= substr($chars, rand(0, 15), 1);
        return 'a' . $uniqueID;
    }

    public static function fetchFromOidc($col)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Constants::TABLE_OIDC);
        $variable = $queryBuilder->select($col)->from(Constants::TABLE_OIDC)->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))->execute()->fetch();
        return is_array($variable) ? $variable[$col] : $variable;
    }

    public static function updateOidc($column, $value)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Constants::TABLE_OIDC);
        if (self::fetchFromOidc('uid') == null) 
        {
            $queryBuilder->insert(Constants::TABLE_OIDC)->values(['uid' => '1'])->execute();
        }
        $queryBuilder->update(Constants::TABLE_OIDC)->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))->set($column, $value)->execute();
    }
}