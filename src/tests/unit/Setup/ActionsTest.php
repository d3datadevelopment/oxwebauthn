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

namespace D3\Webauthn\tests\unit\Setup;

use D3\TestingTools\Development\CanAccessRestricted;
use D3\Webauthn\Setup\Actions;
use D3\Webauthn\tests\unit\WAUnitTestCase;
use Exception;
use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Core\Database\Adapter\DatabaseInterface;
use OxidEsales\Eshop\Core\Database\Adapter\Doctrine\Database;
use OxidEsales\Eshop\Core\DbMetaDataHandler;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\SeoEncoder;
use OxidEsales\Eshop\Core\Utils;
use OxidEsales\Eshop\Core\UtilsView;
use OxidEsales\Eshop\Core\ViewConfig;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ShopConfigurationDaoBridge;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ShopConfigurationDaoBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Exception\ModuleConfigurationNotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use ReflectionException;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ActionsTest extends WAUnitTestCase
{
    use CanAccessRestricted;

    /**
     * @test
     * @param $tableExist
     * @param $expectedInvocation
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Setup\Actions::setupModule
     * @dataProvider canSetupModuleDataProvider
     */
    public function canSetupModule($tableExist, $expectedInvocation)
    {
        /** @var Actions|MockObject $sut */
        $sut = $this->getMockBuilder(Actions::class)
            ->onlyMethods(['tableExists', 'executeSQL'])
            ->getMock();
        $sut->method('tableExists')->willReturn($tableExist);
        $sut->expects($expectedInvocation)->method('executeSQL')->willReturn(true);

        $this->callMethod(
            $sut,
            'setupModule'
        );
    }

    /**
     * @return array[]
     */
    public function canSetupModuleDataProvider(): array
    {
        return [
            'table exist'       => [true, $this->never()],
            'table not exist'   => [false, $this->once()],
        ];
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Setup\Actions::tableExists
     */
    public function canCheckTableExists()
    {
        $expected = true;

        /** @var DbMetaDataHandler|MockObject $DbMetaDataMock */
        $DbMetaDataMock = $this->getMockBuilder(DbMetaDataHandler::class)
            ->onlyMethods(['tableExists'])
            ->getMock();
        $DbMetaDataMock->expects($this->once())->method('tableExists')->willReturn($expected);
        d3GetOxidDIC()->set('d3ox.webauthn.'.DbMetaDataHandler::class, $DbMetaDataMock);

        /** @var Actions $sut */
        $sut = oxNew(Actions::class);

        $this->assertSame(
            $expected,
            $this->callMethod(
                $sut,
                'tableExists',
                ['testTable']
            )
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Setup\Actions::d3GetDb
     */
    public function d3GetDbReturnsRightInstance()
    {
        $sut = oxNew(Actions::class);

        $this->assertInstanceOf(
            DatabaseInterface::class,
            $this->callMethod(
                $sut,
                'd3GetDb'
            )
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Setup\Actions::executeSQL
     */
    public function canExecuteSQL()
    {
        /** @var Database|MockObject $dbMock */
        $dbMock = $this->getMockBuilder(Database::class)
            ->onlyMethods(['execute'])
            ->getMock();
        $dbMock->expects($this->once())->method('execute');

        $sut = $this->getMockBuilder(Actions::class)
            ->onlyMethods(['d3GetDb'])
            ->getMock();
        $sut->method('d3GetDb')->willReturn($dbMock);

        $this->callMethod(
            $sut,
            'executeSQL',
            ['query']
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Setup\Actions::fieldExists
     */
    public function canCheckFieldExists()
    {
        $expected = true;

        /** @var DbMetaDataHandler|MockObject $DbMetaDataMock */
        $DbMetaDataMock = $this->getMockBuilder(DbMetaDataHandler::class)
            ->onlyMethods(['fieldExists'])
            ->getMock();
        $DbMetaDataMock->expects($this->once())->method('fieldExists')->willReturn($expected);
        d3GetOxidDIC()->set('d3ox.webauthn.'.DbMetaDataHandler::class, $DbMetaDataMock);

        /** @var Actions $sut */
        $sut = oxNew(Actions::class);

        $this->assertSame(
            $expected,
            $this->callMethod(
                $sut,
                'fieldExists',
                ['testField', 'testTable']
            )
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Setup\Actions::regenerateViews
     */
    public function canRegenerateViews()
    {
        /** @var DbMetaDataHandler|MockObject $DbMetaDataMock */
        $DbMetaDataMock = $this->getMockBuilder(DbMetaDataHandler::class)
            ->onlyMethods(['updateViews'])
            ->getMock();
        $DbMetaDataMock->expects($this->once())->method('updateViews');
        d3GetOxidDIC()->set('d3ox.webauthn.'.DbMetaDataHandler::class, $DbMetaDataMock);

        /** @var Actions $sut */
        $sut = oxNew(Actions::class);

        $this->callMethod(
            $sut,
            'regenerateViews'
        );
    }

    /**
     * @test
     * @throws ReflectionException
     * @dataProvider canClearCacheDataProvider
     * @covers \D3\Webauthn\Setup\Actions::clearCache
     */
    public function canClearCache($throwException)
    {
        /** @var LoggerInterface|MockObject $loggerMock */
        $loggerMock = $this->getMockForAbstractClass(LoggerInterface::class, [], '', true, true, true, ['error', 'debug']);
        $loggerMock->expects($throwException ? $this->atLeastOnce() : $this->never())
            ->method('error')->willReturn(true);
        d3GetOxidDIC()->set('d3ox.webauthn.'.LoggerInterface::class, $loggerMock);

        /** @var UtilsView|MockObject $utilsViewMock */
        $utilsViewMock = $this->getMockBuilder(UtilsView::class)
            ->onlyMethods(['addErrorToDisplay'])
            ->getMock();
        $utilsViewMock->expects($throwException ? $this->atLeastOnce() : $this->never())
            ->method('addErrorToDisplay');
        d3GetOxidDIC()->set('d3ox.webauthn.'.UtilsView::class, $utilsViewMock);

        /** @var Utils|MockObject $utilsMock */
        $utilsMock = $this->getMockBuilder(Utils::class)
            ->onlyMethods(['resetTemplateCache', 'resetLanguageCache'])
            ->getMock();
        $utilsMock->expects($throwException ? $this->never() : $this->once())
            ->method('resetTemplateCache');
        $utilsMock->expects($throwException ? $this->never() : $this->once())
            ->method('resetLanguageCache');
        d3GetOxidDIC()->set('d3ox.webauthn.'.Utils::class, $utilsMock);

        /** @var Actions|MockObject $sut */
        $sut = $this->getMockBuilder(Actions::class)
            ->onlyMethods(['getModuleTemplates'])
            ->getMock();
        $sut->method('getModuleTemplates')->will(
            $throwException ?
                $this->throwException(oxNew(ModuleConfigurationNotFoundException::class)) :
                $this->returnValue([])
        );

        $this->callMethod(
            $sut,
            'clearCache'
        );
    }

    /**
     * @return array
     */
    public function canClearCacheDataProvider(): array
    {
        return [
            'throws exception'      => [true],
            'dont throws exception' => [false],
        ];
    }

    /**
     * @test
     * @throws ReflectionException
     * @covers \D3\Webauthn\Setup\Actions::getModuleTemplates
     */
    public function canGetModuleTemplates()
    {
        /** @var ModuleConfiguration|MockObject $moduleConfigurationMock */
        $moduleConfigurationMock = $this->getMockBuilder(ModuleConfiguration::class)
            ->getMock();

        /** @var ShopConfiguration|MockObject $shopConfigurationMock */
        $shopConfigurationMock = $this->getMockBuilder(ShopConfiguration::class)
            ->onlyMethods(['getModuleConfiguration'])
            ->getMock();
        $shopConfigurationMock->method('getModuleConfiguration')->willReturn($moduleConfigurationMock);

        /** @var ShopConfigurationDaoBridge|MockObject $shopConfigurationDaoBridgeMock */
        $shopConfigurationDaoBridgeMock = $this->getMockBuilder(ShopConfigurationDaoBridge::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMock();
        $shopConfigurationDaoBridgeMock->method('get')->willReturn($shopConfigurationMock);

        /** @var Container|MockObject $dicMock */
        $dicMock = $this->getMockBuilder(Container::class)
            ->onlyMethods(['get'])
            ->getMock();
        $dicMock->method('get')->willReturnCallback(
            function () use ($shopConfigurationDaoBridgeMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case ShopConfigurationDaoBridgeInterface::class:
                        return $shopConfigurationDaoBridgeMock;
                    default:
                        return Registry::get($args[0]);
                }
            }
        );

        /** @var Actions|MockObject $sut */
        $sut = $this->getMockBuilder(Actions::class)
            ->onlyMethods(['getDIContainer', 'getModuleTemplatesFromTemplates', 'getModuleTemplatesFromBlocks'])
            ->getMock();
        $sut->method('getDIContainer')->willReturn($dicMock);
        $sut->expects($this->once())->method('getModuleTemplatesFromTemplates')->willReturn([1, 2]);
        $sut->expects($this->once())->method('getModuleTemplatesFromBlocks')->willReturn([2, 3]);

        $this->assertSame(
            [0  => 1, 1 => 2, 3 => 3],
            $this->callMethod(
                $sut,
                'getModuleTemplates'
            )
        );
    }

    /**
     * @test
     * @throws ReflectionException
     * @covers \D3\Webauthn\Setup\Actions::getModuleTemplatesFromTemplates
     */
    public function canGetModuleTemplatesFromTemplates()
    {
        $expected = "templateKeyFixture";

        /** @var ModuleConfiguration\Template|MockObject $templateMock */
        $templateMock = $this->getMockBuilder(ModuleConfiguration\Template::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTemplateKey'])
            ->getMock();
        $templateMock->method('getTemplateKey')->willReturn($expected);

        /** @var ModuleConfiguration|MockObject $moduleConfigurationMock */
        $moduleConfigurationMock = $this->getMockBuilder(ModuleConfiguration::class)
            ->onlyMethods(['getTemplates'])
            ->getMock();
        $moduleConfigurationMock->method('getTemplates')->willReturn([$templateMock]);

        $sut = oxNew(Actions::class);

        $this->assertSame(
            [$expected],
            $this->callMethod(
                $sut,
                'getModuleTemplatesFromTemplates',
                [$moduleConfigurationMock]
            )
        );
    }

    /**
     * @test
     * @throws ReflectionException
     * @covers \D3\Webauthn\Setup\Actions::getModuleTemplatesFromBlocks
     */
    public function canGetModuleTemplatesFromBlocks()
    {
        /** @var ModuleConfiguration\TemplateBlock|MockObject $templateBlockMock */
        $templateBlockMock = $this->getMockBuilder(ModuleConfiguration\TemplateBlock::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getShopTemplatePath'])
            ->getMock();
        $templateBlockMock->method('getShopTemplatePath')->willReturn('mypath/myFile.tpl');

        /** @var ModuleConfiguration|MockObject $moduleConfigurationMock */
        $moduleConfigurationMock = $this->getMockBuilder(ModuleConfiguration::class)
            ->onlyMethods(['getTemplateBlocks'])
            ->getMock();
        $moduleConfigurationMock->method('getTemplateBlocks')->willReturn([$templateBlockMock]);

        $sut = oxNew(Actions::class);

        $this->assertSame(
            ['myFile.tpl'],
            $this->callMethod(
                $sut,
                'getModuleTemplatesFromBlocks',
                [$moduleConfigurationMock]
            )
        );
    }

    /**
     * @test
     *
     * @param $hasSeoUrl
     * @param $throwException
     *
     * @throws ReflectionException
     * @dataProvider canCheckSeoUrlDataProvider
     * @covers       \D3\Webauthn\Setup\Actions::seoUrl
     */
    public function canCheckSeoUrl($hasSeoUrl, $throwException)
    {
        /** @var UtilsView|MockObject $utilsViewMock */
        $utilsViewMock = $this->getMockBuilder(UtilsView::class)
                              ->onlyMethods(['addErrorToDisplay'])
                              ->getMock();
        $utilsViewMock->expects($throwException ? $this->atLeastOnce() : $this->never())
                      ->method('addErrorToDisplay');
        d3GetOxidDIC()->set('d3ox.webauthn.'.UtilsView::class, $utilsViewMock);

        /** @var LoggerInterface|MockObject $loggerMock */
        $loggerMock = $this->getMockForAbstractClass(LoggerInterface::class, [], '', true, true, true, ['error', 'debug']);
        $loggerMock->expects($throwException ? $this->atLeastOnce() : $this->never())
                   ->method('error')->willReturn(true);
        d3GetOxidDIC()->set('d3ox.webauthn.'.LoggerInterface::class, $loggerMock);

        /** @var Actions|MockObject $sut */
        $sut = $this->getMockBuilder(Actions::class)
            ->onlyMethods(['hasSeoUrl', 'createSeoUrl'])
            ->getMock();
        $sut->method('hasSeoUrl')->willReturn($hasSeoUrl);
        $sut->expects($hasSeoUrl ? $this->never() : $this->once())->method('createSeoUrl')->will(
            $throwException ?
                $this->throwException(oxNew(Exception::class)) :
                $this->returnValue(true)
        );

        $this->callMethod(
            $sut,
            'seoUrl'
        );
    }

    /**
     * @return array
     */
    public function canCheckSeoUrlDataProvider(): array
    {
        return [
            'already has SEO url'               => [true, false],
            'has no SEO url'                    => [false, false],
            'has no SEO url throw exception'    => [false, true],
        ];
    }

    /**
     * @test
     * @throws ReflectionException
     * @dataProvider canCheckHasSeoUrlDataProvider
     * @covers \D3\Webauthn\Setup\Actions::hasSeoUrl
     */
    public function canCheckHasSeoUrl($staticUrl, $expected)
    {
        /** @var SeoEncoder|MockObject $seoEncoderMock */
        $seoEncoderMock = $this->getMockBuilder(SeoEncoder::class)
            ->onlyMethods(['getStaticUrl'])
            ->getMock();
        $seoEncoderMock->method('getStaticUrl')->willReturn($staticUrl);
        d3GetOxidDIC()->set('d3ox.webauthn.'.SeoEncoder::class, $seoEncoderMock);

        /** @var ViewConfig|MockObject $viewConfigMock */
        $viewConfigMock = $this->getMockBuilder(ViewConfig::class)
            ->onlyMethods(['getSelfLink'])
            ->getMock();
        $viewConfigMock->method('getSelfLink')->willReturn('https://testshop.dev/');

        /** @var FrontendController|MockObject $controllerMock */
        $controllerMock = $this->getMockBuilder(FrontendController::class)
            ->onlyMethods(['getViewConfig'])
            ->getMock();
        $controllerMock->method('getViewConfig')->willReturn($viewConfigMock);
        d3GetOxidDIC()->set('d3ox.webauthn.'.FrontendController::class, $controllerMock);

        /** @var Actions $sut */
        $sut = oxNew(Actions::class);

        $this->assertSame(
            $expected,
            $this->callMethod(
                $sut,
                'hasSeoUrl'
            )
        );
    }

    /**
     * @return array[]
     */
    public function canCheckHasSeoUrlDataProvider(): array
    {
        return [
            'has SEO url'   => ['https://testshop.dev/securitykeys', true],
            'has no SEO url'=> ['', false],
        ];
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Setup\Actions::createSeoUrl
     */
    public function canCreateSeoUrl()
    {
        /** @var SeoEncoder|MockObject $seoEncoderMock */
        $seoEncoderMock = $this->getMockBuilder(SeoEncoder::class)
            ->onlyMethods(['addSeoEntry'])
            ->getMock();
        $seoEncoderMock->expects($this->exactly(2))->method('addSeoEntry');
        d3GetOxidDIC()->set('d3ox.webauthn.'.SeoEncoder::class, $seoEncoderMock);

        /** @var Actions $sut */
        $sut = oxNew(Actions::class);

        $this->callMethod(
            $sut,
            'createSeoUrl'
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Setup\Actions::getDIContainer
     */
    public function canGetDIContainer()
    {
        $this->assertInstanceOf(
            ContainerBuilder::class,
            $this->callMethod(
                oxNew(Actions::class),
                'getDIContainer'
            )
        );
    }
}
