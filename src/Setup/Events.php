<?php

/**
 * This Software is the property of Data Development and is protected
 * by copyright law - it is NOT Freeware.
 *
 * Any unauthorized use of this software without a valid license
 * is a violation of the license agreement and will be prosecuted by
 * civil and criminal law.
 *
 * http://www.shopmodule.com
 *
 * @copyright (C) D3 Data Development (Inh. Thomas Dartsch)
 * @author    D3 Data Development - Daniel Seifert <support@shopmodule.com>
 * @link      http://www.oxidmodule.com
 */

namespace D3\Webauthn\Setup;

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\DbMetaDataHandler;
use OxidEsales\Eshop\Core\Registry;

class Events
{
    /**
     * SQL statement, that will be executed only at the first time of module installation.
     *
     * @var array
     */
    private static $_createCredentialSql =
        "CREATE TABLE `d3wa_usercredentials` (
            `OXID` char(32) NOT NULL,
            `OXUSERID` char(32) NOT NULL,
            `OXSHOPID` int(11) NOT NULL,
            `NAME` varchar(100) NOT NULL,
            `CREDENTIALID` char(100) NOT NULL,
            `CREDENTIAL` varchar(2000) NOT NULL,
            `OXTIMESTAMP` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`OXID`),
            KEY `CREDENTIALID_IDX` (`CREDENTIALID`),
            KEY `SHOPUSER_IDX` (`OXUSERID`,`OXSHOPID`) USING BTREE
        ) ENGINE=InnoDB COMMENT='WebAuthn Credentials';";

    /**
     * Execute action on activate event
     */
    public static function onActivate()
    {
        self::setupModule();

        self::regenerateViews();

        self::clearCache();
    }

    public static function onDeactivate()
    {
    }

    /**
     * Execute the sql at the first time of the module installation.
     */
    private static function setupModule()
    {
        if (!self::tableExists('d3wa_usercredentials')) {
            self::executeSQL(self::$_createCredentialSql);
        }
    }

    /**
     * Check if table exists
     *
     * @param string $sTableName table name
     *
     * @return bool
     */
    protected static function tableExists($sTableName)
    {
        $oDbMetaDataHandler = oxNew(DbMetaDataHandler::class );

        return $oDbMetaDataHandler->tableExists($sTableName);
    }

    /**
     * Executes given sql statement.
     *
     * @param string $sSQL Sql to execute.
     */
    private static function executeSQL($sSQL)
    {
        DatabaseProvider::getDb()->execute($sSQL);
    }

    /**
     * Check if field exists in table
     *
     * @param string $sFieldName field name
     * @param string $sTableName table name
     *
     * @return bool
     */
    protected static function fieldExists($sFieldName, $sTableName)
    {
        $oDbMetaDataHandler = oxNew(DbMetaDataHandler::class );

        return $oDbMetaDataHandler->fieldExists($sFieldName, $sTableName);
    }

    /**
     * Regenerate views for changed tables
     */
    protected static function regenerateViews()
    {
        $oDbMetaDataHandler = oxNew(DbMetaDataHandler::class );
        $oDbMetaDataHandler->updateViews();
    }

    /**
     * Empty cache
     */
    private static function clearCache()
    {
        /** @var \OxidEsales\Eshop\Core\UtilsView $oUtilsView */
        $oUtilsView = Registry::getUtilsView();
        $sSmartyDir = $oUtilsView->getSmartyDir();

        if ($sSmartyDir && is_readable($sSmartyDir)) {
            foreach (glob($sSmartyDir . '*') as $sFile) {
                if (!is_dir($sFile)) {
                    @unlink($sFile);
                }
            }
        }
    }
}