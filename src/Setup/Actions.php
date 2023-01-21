<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * https://www.d3data.de
 *
 * @copyright (C) D3 Data Development (Inh. Thomas Dartsch)
 * @author    D3 Data Development - Daniel Seifert <info@shopmodule.com>
 * @link      https://www.oxidmodule.com
 */

declare(strict_types=1);

namespace D3\Webauthn\Setup;

use D3\TestingTools\Production\IsMockable;
use Doctrine\DBAL\Driver\Exception as DoctrineDriverException;
use Exception;
use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Database\Adapter\DatabaseInterface;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\DbMetaDataHandler;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\SeoEncoder;
use OxidEsales\Eshop\Core\Utils;
use OxidEsales\Eshop\Core\UtilsView;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ShopConfigurationDaoBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Exception\ModuleConfigurationNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;

class Actions
{
    use IsMockable;

    public $seo_de = 'sicherheitsschluessel';
    public $seo_en = 'en/key-authentication';
    public $stdClassName = 'd3_account_webauthn';

    /**
     * SQL statement, that will be executed only at the first time of module installation.
     *
     * @var string
     */
    protected $createCredentialSql =
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
     * Execute the sql at the first time of the module installation.
     * @return void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function setupModule()
    {
        if (!$this->tableExists('d3wa_usercredentials')) {
            $this->executeSQL($this->createCredentialSql);
        }
    }

    /**
     * Check if table exists
     *
     * @param string $sTableName table name
     *
     * @return bool
     */
    public function tableExists(string $sTableName): bool
    {
        $oDbMetaDataHandler = d3GetOxidDIC()->get('d3ox.webauthn.'.DbMetaDataHandler::class);
        return $oDbMetaDataHandler->tableExists($sTableName);
    }

    /**
     * @return DatabaseInterface|null
     * @throws DatabaseConnectionException
     */
    protected function d3GetDb(): ?DatabaseInterface
    {
        /** @var DatabaseInterface $db */
        $db = d3GetOxidDIC()->get('d3ox.webauthn.'.DatabaseInterface::class.'.assoc');
        return $db;
    }

    /**
     * Executes given sql statement.
     *
     * @param string $sSQL Sql to execute.
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function executeSQL(string $sSQL)
    {
        $this->d3GetDb()->execute($sSQL);
    }

    /**
     * Check if field exists in table
     *
     * @param string $sFieldName field name
     * @param string $sTableName table name
     *
     * @return bool
     */
    public function fieldExists(string $sFieldName, string $sTableName): bool
    {
        $oDbMetaDataHandler = d3GetOxidDIC()->get('d3ox.webauthn.'.DbMetaDataHandler::class);
        return $oDbMetaDataHandler->fieldExists($sFieldName, $sTableName);
    }

    /**
     * Regenerate views for changed tables
     */
    public function regenerateViews()
    {
        $oDbMetaDataHandler = d3GetOxidDIC()->get('d3ox.webauthn.'.DbMetaDataHandler::class);
        $oDbMetaDataHandler->updateViews();
    }

    /**
     * clear cache
     */
    public function clearCache()
    {
        try {
            $oUtils = d3GetOxidDIC()->get('d3ox.webauthn.'.Utils::class);
            $oUtils->resetTemplateCache($this->getModuleTemplates());
            $oUtils->resetLanguageCache();
        } catch (ContainerExceptionInterface|NotFoundExceptionInterface|ModuleConfigurationNotFoundException $e) {
            d3GetOxidDIC()->get('d3ox.webauthn.'.LoggerInterface::class)->error($e->getMessage(), [$this]);
            d3GetOxidDIC()->get('d3ox.webauthn.'.UtilsView::class)->addErrorToDisplay($e->getMessage());
        }
    }

    /**
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ModuleConfigurationNotFoundException
     */
    protected function getModuleTemplates(): array
    {
        $container = $this->getDIContainer();
        $shopConfiguration = $container->get(ShopConfigurationDaoBridgeInterface::class)->get();
        $moduleConfiguration = $shopConfiguration->getModuleConfiguration('d3webauthn');

        return array_unique(array_merge(
            $this->getModuleTemplatesFromTemplates($moduleConfiguration),
            $this->getModuleTemplatesFromBlocks($moduleConfiguration)
        ));
    }

    /**
     * @param ModuleConfiguration $moduleConfiguration
     *
     * @return array
     */
    protected function getModuleTemplatesFromTemplates(ModuleConfiguration $moduleConfiguration): array
    {
        /** @var $template ModuleConfiguration\Template */
        return array_map(
            function ($template) {
                return $template->getTemplateKey();
            },
            $moduleConfiguration->getTemplates()
        );
    }

    /**
     * @param ModuleConfiguration $moduleConfiguration
     *
     * @return array
     */
    protected function getModuleTemplatesFromBlocks(ModuleConfiguration $moduleConfiguration): array
    {
        /** @var $templateBlock ModuleConfiguration\TemplateBlock */
        return array_map(
            function ($templateBlock) {
                return basename($templateBlock->getShopTemplatePath());
            },
            $moduleConfiguration->getTemplateBlocks()
        );
    }

    /**
     * @return void
     */
    public function seoUrl()
    {
        try {
            if (!$this->hasSeoUrl()) {
                $this->createSeoUrl();
            }
        } catch (Exception|NotFoundExceptionInterface|DoctrineDriverException|ContainerExceptionInterface $e) {
            d3GetOxidDIC()->get('d3ox.webauthn.'.LoggerInterface::class)->error($e->getMessage(), [$this]);
            d3GetOxidDIC()->get('d3ox.webauthn.'.UtilsView::class)
                 ->addErrorToDisplay('error wile creating SEO URLs: '.$e->getMessage());
        }
    }

    /**
     * @return bool
     */
    public function hasSeoUrl(): bool
    {
        $seoEncoder = d3GetOxidDIC()->get('d3ox.webauthn.'.SeoEncoder::class);
        $seoUrl = $seoEncoder->getStaticUrl(
            d3GetOxidDIC()->get('d3ox.webauthn.'.FrontendController::class)->getViewConfig()->getSelfLink() .
            "cl=".$this->stdClassName
        );

        return (bool) strlen($seoUrl);
    }

    /**
     * @return void
     */
    public function createSeoUrl()
    {
        $seoEncoder = d3GetOxidDIC()->get('d3ox.webauthn.'.SeoEncoder::class);
        $seoEncoder->addSeoEntry(
            'ff57646b47249ee33c6b672741ac371a',
            d3GetOxidDIC()->get('d3ox.webauthn.'.Config::class)->getShopId(),
            0,
            'index.php?cl='.$this->stdClassName,
            $this->seo_de,
            'static',
            false
        );
        $seoEncoder->addSeoEntry(
            'ff57646b47249ee33c6b672741ac371a',
            d3GetOxidDIC()->get('d3ox.webauthn.'.Config::class)->getShopId(),
            1,
            'index.php?cl='.$this->stdClassName,
            $this->seo_en,
            'static',
            false
        );
    }

    /**
     * @return ContainerInterface|null
     */
    protected function getDIContainer(): ?ContainerInterface
    {
        return ContainerFactory::getInstance()->getContainer();
    }
}
