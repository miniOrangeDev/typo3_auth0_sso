<?php

namespace Miniorange\Auth0SSO\Helper;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * EncryptionHelper class for handling encrypted storage of sensitive data
 * Implements XOR encryption with key rotation and base64 encoding
 */
class EncryptionHelper
{
    /**
     * Get the encryption key from TYPO3 configuration or use fallback
     * @return string Encryption key
     */
    private static function getEncryptionKey()
    {
        // Primary: TYPO3's encryption key
        if (isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']) && 
            !empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'])) {
            return $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'];
        }
        
        // Fallback: Default key
        return 'auth0_sso_default_encryption_key_2024';
    }

    /**
     * Encrypt data using XOR encryption with key rotation
     * @param string $data Data to encrypt
     * @return string Encrypted and base64 encoded data
     */
    public static function encrypt($data)
    {
        if (empty($data)) {
            return '';
        }

        $encryptionKey = self::getEncryptionKey();
        $encrypted = '';
        $keyLength = strlen($encryptionKey);

        // XOR encryption with key rotation
        for ($i = 0; $i < strlen($data); $i++) {
            $encrypted .= chr(ord($data[$i]) ^ ord($encryptionKey[$i % $keyLength]));
        }

        // Base64 encoding for database safety
        return base64_encode($encrypted);
    }

    /**
     * Decrypt data using XOR decryption with key rotation
     * @param string $encryptedData Base64 encoded encrypted data
     * @return string Decrypted data
     */
    public static function decrypt($encryptedData)
    {
        if (empty($encryptedData)) {
            return '';
        }

        try {
            $encryptionKey = self::getEncryptionKey();
            
            // Base64 decode first
            $encrypted = base64_decode($encryptedData);
            if ($encrypted === false) {
                return '';
            }

            $decrypted = '';
            $keyLength = strlen($encryptionKey);

            // XOR decryption with key rotation
            for ($i = 0; $i < strlen($encrypted); $i++) {
                $decrypted .= chr(ord($encrypted[$i]) ^ ord($encryptionKey[$i % $keyLength]));
            }

            return $decrypted;
        } catch (\Exception $e) {
            error_log('Error decrypting data: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Check if data appears to be encrypted (base64 encoded)
     * @param string $data Data to check
     * @return bool True if data appears encrypted
     */
    public static function isEncrypted($data)
    {
        if (empty($data)) {
            return false;
        }

        // Check if it's base64 encoded and not a simple number
        if (base64_encode(base64_decode($data, true)) === $data && !is_numeric($data)) {
            return true;
        }

        return false;
    }

    /**
     * Encrypt user count value
     * @param int $count The count value to encrypt
     * @return string Encrypted count
     */
    public static function encryptUserCount($count)
    {
        return self::encrypt((string)$count);
    }

    /**
     * Decrypt user count value
     * @param string $encryptedCount The encrypted count to decrypt
     * @return int Decrypted count
     */
    public static function decryptUserCount($encryptedCount)
    {
        if (empty($encryptedCount)) {
            return 10; // Default value
        }

        // Check if encrypted
        if (self::isEncrypted($encryptedCount)) {
            $decrypted = self::decrypt($encryptedCount);
            return is_numeric($decrypted) ? (int)$decrypted : 10;
        }

        // Backward compatibility for unencrypted values
        return is_numeric($encryptedCount) ? (int)$encryptedCount : 10;
    }

    /**
     * Update encrypted user count in database
     * @param int $count The count value to store
     * @return bool Success status
     */
    public static function updateEncryptedUserCount($count)
    {
        try {
            $encryptedCount = self::encryptUserCount($count);
            $typo3Version = MoUtilities::getTypo3Version();
            $queryBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
                ->getQueryBuilderForTable(Constants::TABLE_OIDC);
            
            if ($typo3Version > 12) {
                $queryBuilder->update(Constants::TABLE_OIDC)
                    ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \TYPO3\CMS\Core\Database\Connection::PARAM_INT)))
                    ->set(Constants::COUNTUSER, $encryptedCount)
                    ->executeStatement();
            } else {
                $queryBuilder->update(Constants::TABLE_OIDC)
                    ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \TYPO3\CMS\Core\Database\Connection::PARAM_INT)))
                    ->set(Constants::COUNTUSER, $encryptedCount)
                    ->execute();
            }
            
            return true;
        } catch (\Exception $e) {
            error_log('Error updating encrypted user count: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch encrypted user count from database
     * @return int Decrypted user count
     */
    public static function fetchEncryptedUserCount()
    {
        try {
            $encryptedCount = MoUtilities::fetchFromOidc(Constants::COUNTUSER);
            
            // Check if encrypted
            if (self::isEncrypted($encryptedCount)) {
                return self::decryptUserCount($encryptedCount);
            }
            
            // Backward compatibility for unencrypted values
            return is_numeric($encryptedCount) ? (int)$encryptedCount : 10;
        } catch (\Exception $e) {
            error_log('Error fetching encrypted user count: ' . $e->getMessage());
            return 10; // Default value
        }
    }

    /**
     * Decrement encrypted user count
     * @return int New count value
     */
    public static function decrementEncryptedUserCount()
    {
        $currentCount = self::fetchEncryptedUserCount();
        $newCount = max(0, $currentCount - 1);
        self::updateEncryptedUserCount($newCount);
        return $newCount;
    }

    /**
     * Migrate existing unencrypted user count to encrypted format
     * @return bool Success status
     */
    public static function migrateUserCountToEncrypted()
    {
        try {
            $currentCount = MoUtilities::fetchFromOidc(Constants::COUNTUSER);
            
            // Only migrate if not already encrypted
            if (!self::isEncrypted($currentCount) && is_numeric($currentCount)) {
                return self::updateEncryptedUserCount((int)$currentCount);
            }
            
            return true; // Already encrypted or no migration needed
        } catch (\Exception $e) {
            error_log('Error migrating user count to encrypted: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if migration is needed and perform it lazily
     * This method should be called from controllers when database is available
     * @return bool True if migration was performed or not needed
     */
    public static function performLazyMigration()
    {
        // Check if migration has already been completed
        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['auth0_sso']['countuser_migrated'])) {
            return true;
        }

        try {
            $migrationResult = self::migrateUserCountToEncrypted();
            if ($migrationResult) {
                $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['auth0_sso']['countuser_migrated'] = true;
                error_log('Auth0 SSO: Successfully migrated countuser to encrypted format (lazy loading)');
                return true;
            }
            return false;
        } catch (\Exception $e) {
            error_log('Auth0 SSO: Error during countuser migration (lazy loading): ' . $e->getMessage());
            return false;
        }
    }
}
