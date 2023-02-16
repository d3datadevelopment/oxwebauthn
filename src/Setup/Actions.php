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
use OxidEsales\DoctrineMigrationWrapper\MigrationsBuilder;
use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\DbMetaDataHandler;
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

    public $seo_de = 'anmeldeschluessel';
    public $seo_en = 'en/login-keys';
    public $stdClassName = 'd3_account_webauthn';

    /**
     * @throws Exception
     */
    public function runModuleMigrations()
    {
        /** @var MigrationsBuilder $migrationsBuilder */
        $migrationsBuilder = d3GetOxidDIC()->get('d3ox.webauthn.'.MigrationsBuilder::class);
        $migrations = $migrationsBuilder->build();
        $migrations->execute('migrations:migrate', 'd3webauthn');
    }

    /**
     * Regenerate views for changed tables
     * @throws Exception
     */
    public function regenerateViews()
    {
        $oDbMetaDataHandler = d3GetOxidDIC()->get('d3ox.webauthn.'.DbMetaDataHandler::class);
        $oDbMetaDataHandler->updateViews();
    }

    /**
     * clear cache
     * @throws Exception
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
     * @throws Exception
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
     * @throws Exception
     */
    public function hasSeoUrl(): bool
    {
        /** @var SeoEncoder $seoEncoder */
        $seoEncoder = d3GetOxidDIC()->get('d3ox.webauthn.'.SeoEncoder::class);
        $seoUrl = $seoEncoder->getStaticUrl(
            d3GetOxidDIC()->get('d3ox.webauthn.'.FrontendController::class)->getViewConfig()->getSelfLink() .
            "cl=".$this->stdClassName
        );

        return (bool) strlen($seoUrl);
    }

    /**
     * @return void
     * @throws Exception
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
