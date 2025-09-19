<?php

namespace Miniorange\Auth0SSO\Helper;

use Exception;
use PDO;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Messaging\Renderer\ListRenderer;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;


class MoUtilities
{
    /**
     * Get TYPO3 version for compatibility checks
     */
    public static function getTypo3Version()
    {
        $version = new Typo3Version();
        return $version->getVersion();
    }

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
        $typo3Version = self::getTypo3Version();
        $table = Constants::TABLE_FE_USERS;
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        // Remove all restrictions but add DeletedRestriction again
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        if($typo3Version > 12){
        $var_uid = $queryBuilder->select('*')->from($table)->where(
            $queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter($username))
            )->executeQuery()->fetchAssociative();
        }else{
            $var_uid = $queryBuilder->select('*')->from($table)->where(
                $queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter($username))
        )->execute()->fetch();
        }
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
        $typo3Version = self::getTypo3Version();
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Constants::TABLE_CUSTOMER);
        if($typo3Version > 12){
            $queryBuilder->update(Constants::TABLE_CUSTOMER)->where($queryBuilder->expr()->eq('id', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)))->set($column, $value)->executeStatement();
        }else{
            $queryBuilder->update(Constants::TABLE_CUSTOMER)->where($queryBuilder->expr()->eq('id', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)))->set($column, $value)->execute();
        }
    }

    /**
     *---------FETCH CUSTOMER DETAILS-------------------------
     */
    public static function fetch_cust($col)
    {
        $typo3Version = self::getTypo3Version();
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Constants::TABLE_CUSTOMER);
        if($typo3Version > 12){
            $variable = $queryBuilder->select($col)->from(Constants::TABLE_CUSTOMER)->where($queryBuilder->expr()->eq('id', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)))->executeQuery()->fetchAssociative();
        }else{
            $variable = $queryBuilder->select($col)->from(Constants::TABLE_CUSTOMER)->where($queryBuilder->expr()->eq('id', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)))->execute()->fetch();
        }
        return is_array($variable) ? $variable[$col] : $variable;
    }

    /**
     *---------INSERT CUSTOMER DETAILS--------------
     */
    public static function insertValue()
    {
        $typo3Version = self::getTypo3Version();
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Constants::TABLE_CUSTOMER);
        if($typo3Version > 12){
            $affectedRows = $queryBuilder->insert(Constants::TABLE_CUSTOMER)->values(['id' => '1'])->executeStatement();
        }else{
        $affectedRows = $queryBuilder->insert(Constants::TABLE_CUSTOMER)->values(['id' => '1'])->execute();
        }
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
        $typo3Version = self::getTypo3Version();
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        if($typo3Version > 12){
        $rows = $queryBuilder->select('uid')
            ->from($table)
                ->where($queryBuilder->expr()->eq('title', $queryBuilder->createNamedParameter($name, Connection::PARAM_STR)))->executeQuery()->fetchAssociative();
        }else{
            $rows = $queryBuilder->select('uid')->from($table)->where($queryBuilder->expr()->eq('title', $queryBuilder->createNamedParameter($name, Connection::PARAM_STR)))
            ->execute()
            ->fetch();
        }
        return $rows['uid'];
    }


    // -------------UPDATE TABLE---------------------------------------
    public static function updateTable($col, $val, $table)
    {
        $typo3Version = self::getTypo3Version();
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        if($typo3Version > 12){
        $queryBuilder->update($table)
                ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)))->set($col, $val)->executeStatement();
        }else{
            $queryBuilder->update($table)->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)))->set($col, $val)
            ->execute();
        }
    }

    //Fetch a value from Database
    public static function fetchFromDb($col, $table)
    {
        $typo3Version = self::getTypo3Version();
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        if($typo3Version > 12){
            $columnValue = $queryBuilder->select($col)->from($table)->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)))->executeQuery()->fetchAssociative();
        }else{
            $columnValue = $queryBuilder->select($col)->from($table)->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)))->execute()->fetch();
        }
        return is_array($columnValue) ? $columnValue[$col] : $columnValue;
    }

    public static function showErrorFlashMessage($message, $header = "ERROR")
    {
        $typo3Version = self::getTypo3Version();
        if($typo3Version > 12){
            $message = GeneralUtility::makeInstance(FlashMessage::class, $message, $header, ContextualFeedbackSeverity::ERROR);
        }else{
            $message = GeneralUtility::makeInstance(FlashMessage::class, $message, $header, FlashMessage::ERROR);
        }
        $out = GeneralUtility::makeInstance(ListRenderer ::class)->render([$message]);
        echo $out;
    }

    public static function showSuccessFlashMessage($message, $header = "OK")
    {
        $typo3Version = self::getTypo3Version();
        if($typo3Version > 12){
            $message = GeneralUtility::makeInstance(FlashMessage::class, $message, $header, ContextualFeedbackSeverity::OK);
        }else{
            $message = GeneralUtility::makeInstance(FlashMessage::class, $message, $header, FlashMessage::OK);
        }
        error_log(print_r($message, true) . "\n\n");
        $messageArray = array($message);
        $out = GeneralUtility::makeInstance(ListRenderer ::class)->render($messageArray);
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

    /**
     * Encrypt user count value
     * @param int $value The value to encrypt
     * @return string Encrypted value
     */
    public static function encryptUserCount($value)
    {
        return EncryptionHelper::encryptUserCount($value);
    }

    /**
     * Decrypt user count value
     * @param string $encryptedValue The encrypted value to decrypt
     * @return int Decrypted value
     */
    public static function decryptUserCount($encryptedValue)
    {
        return EncryptionHelper::decryptUserCount($encryptedValue);
    }

    public static function fetchFromOidc($col)
    {
        $typo3Version = self::getTypo3Version();
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Constants::TABLE_OIDC);
        if($typo3Version > 12){
            $variable = $queryBuilder->select($col)->from(Constants::TABLE_OIDC)->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)))->executeQuery()->fetchAssociative();
        }else{
            $variable = $queryBuilder->select($col)->from(Constants::TABLE_OIDC)->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)))->execute()->fetch();
        }
        return is_array($variable) ? $variable[$col] : $variable;
    }

    public static function updateOidc($column, $value)
    {
        $typo3Version = self::getTypo3Version();
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Constants::TABLE_OIDC);
        if (self::fetchFromOidc('uid') == null) 
        {
            if($typo3Version > 12){
                $queryBuilder->insert(Constants::TABLE_OIDC)->values(['uid' => '1'])->executeStatement();
            }else{
            $queryBuilder->insert(Constants::TABLE_OIDC)->values(['uid' => '1'])->execute();
            }
        }
        if($typo3Version > 12){
            $queryBuilder->update(Constants::TABLE_OIDC)->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)))->set($column, $value)->executeStatement();
        }else{
            $queryBuilder->update(Constants::TABLE_OIDC)->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)))->set($column, $value)->execute();
        }
    }
    public static function fetchOidcObject()
    {
        $typo3Version = self::getTypo3Version();
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Constants::TABLE_OIDC);
        if($typo3Version > 12){
            $oidcObject = $queryBuilder->select('*')->from(Constants::TABLE_OIDC)->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)))->executeQuery()->fetchAssociative();
        }else{
            $oidcObject = $queryBuilder->select('*')->from(Constants::TABLE_OIDC)->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)))->execute()->fetch();
        }
        return $oidcObject;
    }

    /**
     * Update encrypted user count in database
     * @param int $count The count value to store
     * @return bool Success status
     */
    public static function updateEncryptedUserCount($count)
    {
        return EncryptionHelper::updateEncryptedUserCount($count);
    }

    /**
     * Fetch encrypted user count from database
     * @return int Decrypted user count
     */
    public static function fetchEncryptedUserCount()
    {
        return EncryptionHelper::fetchEncryptedUserCount();
    }

    /**
     * Decrement encrypted user count
     * @return int New count value
     */
    public static function decrementEncryptedUserCount()
    {
        return EncryptionHelper::decrementEncryptedUserCount();
    }

    /**
     * Migrate existing unencrypted user count to encrypted format
     * @return bool Success status
     */
    public static function migrateUserCountToEncrypted()
    {
        return EncryptionHelper::migrateUserCountToEncrypted();
    }

    /**
     * Perform lazy migration when database is available
     * @return bool Success status
     */
    public static function performLazyMigration()
    {
        return EncryptionHelper::performLazyMigration();
    }
}