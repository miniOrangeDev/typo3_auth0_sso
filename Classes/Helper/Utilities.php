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


class Utilities
{

    /**
     * This function checks if a value is set or
     * empty. Returns true if value is empty
     *
     * @param $value - references the variable passed.
     * @return True or False
     */
    public static function isBlank($value)
    {
        if (!isset($value) || empty($value)) return TRUE;
        return FALSE;
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
        return $relPath . 'Helper' . $sep . 'resources' . $sep;
    }

    public static function getExtensionRelativePath()
    {
        $extRelativePath = PathUtility::getAbsoluteWebPath(self::getExtensionAbsolutePath());
        return $extRelativePath;
    }

    public static function getExtensionAbsolutePath()
    {
        $extAbsPath = ExtensionManagementUtility::extPath('miniorange_oidc');
        return $extAbsPath;
    }

// -------------UPDATE TABLE---------------------------------------

    public static function fetchFromTable($col, $table)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $val = $queryBuilder->select($col)->from($table)->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->execute()->fetch();
        return $val ? $val[$col] : $val;
    }

    /**
     * Get Image Resource URL
     */
    public static function getImageUrl($imgFileName)
    {
        $imageDir = self::getResourceDir() . SEP . 'images' . SEP;
        $iconDir = self::getExtensionRelativePath() . SEP . 'Resources' . SEP . 'Public' . SEP . 'Icons' . SEP;
        return $iconDir . $imgFileName;
    }

    public static function testAttrMappingConfig($nestedPrefix, $resourceOwnerDetails)
    {
        foreach ($resourceOwnerDetails as $key => $resource) {
            if (is_array($resource) || is_object($resource)) {
                if (!empty($nestedPrefix))
                    $nestedPrefix .= ".";
                self::testAttrMappingConfig($nestedPrefix . $key, $resource);
                $nestedPrefix = rtrim($nestedPrefix, ".");
            } else {
                echo "<tr><td>";
                if (!empty($nestedPrefix))
                    echo $nestedPrefix . ".";
                echo $key . "</td><td>" . $resource . "</td></tr>";
            }
        }
    }

}
