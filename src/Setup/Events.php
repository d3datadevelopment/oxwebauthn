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

declare(strict_types=1);

namespace D3\Webauthn\Setup;

use Doctrine\DBAL\Driver\Exception as DoctrineDriverException;
use Doctrine\DBAL\Exception as DoctrineException;
use Doctrine\DBAL\Query\QueryBuilder;
use Exception;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\DbMetaDataHandler;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\UtilsView;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

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
            `CREDENTIALID` char(128) NOT NULL,
            `CREDENTIAL` varchar(2000) NOT NULL,
            `OXTIMESTAMP` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`OXID`),
            KEY `CREDENTIALID_IDX` (`CREDENTIALID`),
            KEY `SHOPUSER_IDX` (`OXUSERID`,`OXSHOPID`) USING BTREE
        ) ENGINE=InnoDB COMMENT='WebAuthn Credentials';";

    /**
     * Execute action on activate event
     * @return void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public static function onActivate()
    {
        self::setupModule();

        self::regenerateViews();

        self::clearCache();

        self::seoUrl();
    }

    public static function onDeactivate()
    {
    }

    /**
     * Execute the sql at the first time of the module installation.
     * @return void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
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
    protected static function tableExists(string $sTableName): bool
    {
        $oDbMetaDataHandler = oxNew(DbMetaDataHandler::class );

        return $oDbMetaDataHandler->tableExists($sTableName);
    }

    /**
     * Executes given sql statement.
     *
     * @param string $sSQL Sql to execute.
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
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
    protected static function fieldExists(string $sFieldName, string $sTableName): bool
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
     * clear cache
     */
    private static function clearCache()
    {
        /** @var UtilsView $oUtilsView */
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

    /**
     * @return void
     */
    private static function seoUrl()
    {
        try {
            if (!self::hasSeoUrl()) {
                self::createSeoUrl();
            }
        } catch (Exception|NotFoundExceptionInterface|DoctrineDriverException|ContainerExceptionInterface $e) {
            Registry::getUtilsView()->addErrorToDisplay('error wile creating SEO URLs: '.$e->getMessage());
        }
    }

    /**
     * @return bool
     * @throws DoctrineDriverException
     * @throws DoctrineException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private static function hasSeoUrl(): bool
    {
        /** @var QueryBuilder $qb */
        $qb = ContainerFactory::getInstance()->getContainer()->get(QueryBuilderFactoryInterface::class)->create();
        $qb->select('1')
            ->from('oxseo')
            ->where(
                $qb->expr()->and(
                    $qb->expr()->eq(
                        'oxstdurl',
                        $qb->createNamedParameter('index.php?cl=d3_account_webauthn')
                    ),
                    $qb->expr()->eq(
                        'oxshopid',
                        $qb->createNamedParameter(Registry::getConfig()->getShopId())
                    ),
                    $qb->expr()->eq(
                        'oxlang',
                        $qb->createNamedParameter('1')
                    )
                )
            )
            ->setMaxResults(1);
        return $qb->execute()->fetchOne();
    }

    /**
     * @return void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    private static function createSeoUrl()
    {
        $query = "INSERT INTO `oxseo` (`OXOBJECTID`, `OXIDENT`, `OXSHOPID`, `OXLANG`, `OXSTDURL`, `OXSEOURL`, `OXTYPE`, `OXFIXED`, `OXEXPIRED`, `OXPARAMS`, `OXTIMESTAMP`) VALUES
            ('ff57646b47249ee33c6b672741ac371a', 'bd3b6183c9a2f94682f4c62e714e4d6b', 1, 1, 'index.php?cl=d3_account_webauthn', 'en/key-authentication/', 'static', 0, 0, '', NOW()),
            ('ff57646b47249ee33c6b672741ac371a', '94d0d3ec07f10e8838a71e54084be885', 1, 0, 'index.php?cl=d3_account_webauthn', 'sicherheitsschluessel/', 'static', 0, 0, '', NOW());";

        DatabaseProvider::getDb()->execute($query);
    }
}