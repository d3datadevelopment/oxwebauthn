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

namespace D3\Webauthn\tests\unit\Application\Controller;

use D3\TestingTools\Development\CanAccessRestricted;
use D3\Webauthn\Application\Controller\d3_account_webauthn;
use D3\Webauthn\Application\Model\Credential\PublicKeyCredential;
use D3\Webauthn\Application\Model\Credential\PublicKeyCredentialList;
use D3\Webauthn\Application\Model\Exceptions\WebauthnException;
use D3\Webauthn\Application\Model\Webauthn;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\UtilsView;
use OxidEsales\TestingLibrary\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use ReflectionException;

class d3_account_webauthnTest extends UnitTestCase
{
    use CanAccessRestricted;

    /** @var d3_account_webauthn */
    protected $_oController;

    /**
     * setup basic requirements
     */
    public function setUp(): void
    {
        unset($_POST['error']);
        unset($_POST['credential']);

        parent::setUp();

        $this->_oController = oxNew(d3_account_webauthn::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        unset($this->_oController);
    }

    /**
     * @test
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\d3_account_webauthn::render
     * @covers \D3\Webauthn\Application\Controller\d3_account_webauthn::getViewDataElement
     */
    public function renderReturnsDefaultTemplate()
    {
        $oUser = oxNew(User::class);
        $oUser->setId('foo');
        $oUser->assign(
            [
                'oxpassword'    => 'foo',
            ]
        );

        /** @var Webauthn|MockObject $webAuthnMock */
        $webAuthnMock = $this->getMockBuilder(Webauthn::class)
            ->onlyMethods(['isAvailable'])
            ->getMock();
        $webAuthnMock->expects($this->atLeastOnce())->method('isAvailable')->willReturn(true);

        /** @var d3_account_webauthn|MockObject $oControllerMock */
        $oControllerMock = $this->getMockBuilder(d3_account_webauthn::class)
            ->onlyMethods(['getUser', 'd3GetMockableOxNewObject'])
            ->getMock();
        $oControllerMock->method('getUser')->willReturn($oUser);
        $oControllerMock->method('d3GetMockableOxNewObject')->willReturnCallback(
            function () use ($webAuthnMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case Webauthn::class:
                        return $webAuthnMock;
                    default:
                        return call_user_func_array("oxNew", $args);
                }
            }
        );

        $this->_oController = $oControllerMock;

        $sTpl = $this->callMethod($this->_oController, 'render');
        $tplUser = $this->callMethod($this->_oController, 'getViewDataElement', ['user']);

        $this->assertSame('d3_account_webauthn.tpl', $sTpl);
        $this->assertSame($tplUser, $oUser);
    }

    /**
     * @test
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\d3_account_webauthn::render
     * @covers \D3\Webauthn\Application\Controller\d3_account_webauthn::getViewDataElement
     */
    public function renderReturnsLoginTemplateIfNotLoggedIn()
    {
        $oUser = null;

        /** @var Webauthn|MockObject $webAuthnMock */
        $webAuthnMock = $this->getMockBuilder(Webauthn::class)
            ->onlyMethods(['isAvailable'])
            ->getMock();
        $webAuthnMock->expects($this->atLeastOnce())->method('isAvailable')->willReturn(true);

        /** @var d3_account_webauthn|MockObject $oControllerMock */
        $oControllerMock = $this->getMockBuilder(d3_account_webauthn::class)
            ->onlyMethods(['getUser', 'd3GetMockableOxNewObject'])
            ->getMock();
        $oControllerMock->method('getUser')->willReturn($oUser);
        $oControllerMock->method('d3GetMockableOxNewObject')->willReturnCallback(
            function () use ($webAuthnMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case Webauthn::class:
                        return $webAuthnMock;
                    default:
                        return call_user_func_array("oxNew", $args);
                }
            }
        );

        $this->_oController = $oControllerMock;

        $sTpl = $this->callMethod($this->_oController, 'render');
        $tplUser = $this->callMethod($this->_oController, 'getViewDataElement', ['user']);

        $this->assertNotSame('d3_account_webauthn.tpl', $sTpl);
        $this->assertNull($tplUser);
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\d3_account_webauthn::getCredentialList()
     */
    public function canGetCredentialList()
    {
        $oUser = oxNew(User::class);
        $oUser->setId('foo');
        $oUser->assign(
            [
                'oxpassword'    => 'foo',
            ]
        );

        /** @var PublicKeyCredentialList|MockObject $publicKeyCredentialListMock */
        $publicKeyCredentialListMock = $this->getMockBuilder(PublicKeyCredentialList::class)
            ->onlyMethods(['getAllFromUser'])
            ->getMock();
        $publicKeyCredentialListMock->method('getAllFromUser')->with($oUser)->willReturnSelf();

        /** @var d3_account_webauthn|MockObject $oControllerMock */
        $oControllerMock = $this->getMockBuilder(d3_account_webauthn::class)
            ->onlyMethods(['getUser', 'd3GetMockableOxNewObject'])
            ->getMock();
        $oControllerMock->method('getUser')->willReturn($oUser);
        $oControllerMock->method('d3GetMockableOxNewObject')->willReturnCallback(
            function () use ($publicKeyCredentialListMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case PublicKeyCredentialList::class:
                        return $publicKeyCredentialListMock;
                    default:
                        return call_user_func_array("oxNew", $args);
                }
            }
        );

        $this->_oController = $oControllerMock;

        $this->assertSame(
            $publicKeyCredentialListMock,
            $this->callMethod(
                $this->_oController,
                'getCredentialList'
            )
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\d3_account_webauthn::requestNewCredential()
     */
    public function canRequestNewCredentialCanGetCreationOptions()
    {
        $oUser = oxNew(User::class);
        $oUser->setId('foo');
        $oUser->assign(
            [
                'oxpassword'    => 'foo',
            ]
        );

        /** @var LoggerInterface|MockObject $loggerMock */
        $loggerMock = $this->getMockForAbstractClass(LoggerInterface::class, [], '', true, true, true, ['error', 'debug']);
        $loggerMock->expects($this->never())->method('error')->willReturn(true);
        $loggerMock->expects($this->never())->method('debug')->willReturn(true);

        /** @var d3_account_webauthn|MockObject $oControllerMock */
        $oControllerMock = $this->getMockBuilder(d3_account_webauthn::class)
            ->onlyMethods(['setAuthnRegister', 'setPageType', 'getUser', 'd3GetMockableLogger'])
            ->getMock();
        $oControllerMock->expects($this->atLeastOnce())->method('setAuthnRegister');
        $oControllerMock->expects($this->atLeastOnce())->method('setPageType');
        $oControllerMock->method('getUser')->willReturn($oUser);
        $oControllerMock->method('d3GetMockableLogger')->willReturn($loggerMock);

        $this->_oController = $oControllerMock;

        $this->callMethod(
            $this->_oController,
            'requestNewCredential'
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\d3_account_webauthn::requestNewCredential()
     */
    public function canRequestNewCredentialCantGetCreationOptions()
    {
        $oUser = oxNew(User::class);
        $oUser->setId('foo');
        $oUser->assign(
            [
                'oxpassword'    => 'foo',
            ]
        );

        /** @var LoggerInterface|MockObject $loggerMock */
        $loggerMock = $this->getMockForAbstractClass(LoggerInterface::class, [], '', true, true, true, ['error', 'debug']);
        $loggerMock->expects($this->atLeastOnce())->method('error')->willReturn(true);
        $loggerMock->expects($this->atLeastOnce())->method('debug')->willReturn(true);

        /** @var d3_account_webauthn|MockObject $oControllerMock */
        $oControllerMock = $this->getMockBuilder(d3_account_webauthn::class)
            ->onlyMethods(['setAuthnRegister', 'setPageType', 'getUser', 'd3GetMockableLogger'])
            ->getMock();
        $oControllerMock->expects($this->atLeastOnce())->method('setAuthnRegister')
            ->willThrowException(oxNew(WebauthnException::class));
        $oControllerMock->expects($this->never())->method('setPageType');
        $oControllerMock->method('getUser')->willReturn($oUser);
        $oControllerMock->method('d3GetMockableLogger')->willReturn($loggerMock);

        $this->_oController = $oControllerMock;

        $this->callMethod(
            $this->_oController,
            'requestNewCredential'
        );
    }

    /**
     * @test
     * @param $throwExc
     * @return void
     * @throws ReflectionException
     * @dataProvider canSetAuthnRegisterDataProvider
     * @covers \D3\Webauthn\Application\Controller\d3_account_webauthn::setAuthnRegister()
     */
    public function canSetAuthnRegister($throwExc)
    {
        /** @var Webauthn|MockObject $webAuthnMock */
        $webAuthnMock = $this->getMockBuilder(Webauthn::class)
            ->onlyMethods(['getCreationOptions'])
            ->getMock();
        if ($throwExc) {
            $webAuthnMock->method('getCreationOptions')->willThrowException(oxNew(WebauthnException::class));
        } else {
            $webAuthnMock->method('getCreationOptions')->willReturn('options');
        }

        /** @var d3_account_webauthn|MockObject $oControllerMock */
        $oControllerMock = $this->getMockBuilder(d3_account_webauthn::class)
            ->onlyMethods(['d3GetMockableOxNewObject', 'addTplParam', 'getUser'])
            ->getMock();
        $oControllerMock->method('d3GetMockableOxNewObject')->willReturnCallback(
            function () use ($webAuthnMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case Webauthn::class:
                        return $webAuthnMock;
                    default:
                        return call_user_func_array("oxNew", $args);
                }
            }
        );
        $oControllerMock->expects($throwExc ? $this->never() : $this->atLeast(3))
            ->method('addTplParam');
        $oControllerMock->method('getUser')->willReturn(oxNew(User::class));

        $this->_oController = $oControllerMock;

        if ($throwExc) {
            $this->expectException(WebauthnException::class);
        }

        $this->callMethod(
            $this->_oController,
            'setAuthnRegister'
        );
    }

    /**
     * @return array
     */
    public function canSetAuthnRegisterDataProvider(): array
    {
        return [
            'dont throw exception'  => [false],
            'throw exception'  => [true],
        ];
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\d3_account_webauthn::setPageType()
     */
    public function canSetPageType()
    {
        $fixture = 'argFixture';

        /** @var d3_account_webauthn|MockObject $oControllerMock */
        $oControllerMock = $this->getMockBuilder(d3_account_webauthn::class)
            ->onlyMethods(['addTplParam'])
            ->getMock();
        $oControllerMock->expects($this->atLeastOnce())->method('addTplParam')
            ->with($this->anything(), $this->identicalTo($fixture));

        $this->_oController = $oControllerMock;

        $this->callMethod(
            $this->_oController,
            'setPageType',
            [$fixture]
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\d3_account_webauthn::saveAuthn
     */
    public function canSaveAuthnHasError()
    {
        $_POST['error'] = 'msg';

        /** @var UtilsView|MockObject $utilsViewMock */
        $utilsViewMock = $this->getMockBuilder(UtilsView::class)
            ->onlyMethods(['addErrorToDisplay'])
            ->getMock();
        $utilsViewMock->expects($this->atLeastOnce())->method('addErrorToDisplay');

        /** @var LoggerInterface|MockObject $loggerMock */
        $loggerMock = $this->getMockForAbstractClass(LoggerInterface::class, [], '', true, true, true, ['error', 'debug']);
        $loggerMock->expects($this->never())->method('error')->willReturn(true);
        $loggerMock->expects($this->never())->method('debug')->willReturn(true);

        /** @var d3_account_webauthn|MockObject $oControllerMock */
        $oControllerMock = $this->getMockBuilder(d3_account_webauthn::class)
            ->onlyMethods(['d3GetMockableRegistryObject', 'd3GetMockableLogger'])
            ->getMock();
        $oControllerMock->method('d3GetMockableRegistryObject')->willReturnCallback(
            function () use ($utilsViewMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case UtilsView::class:
                        return $utilsViewMock;
                    default:
                        return Registry::get($args[0]);
                }
            }
        );
        $oControllerMock->method('d3GetMockableLogger')->willReturn($loggerMock);

        $this->_oController = $oControllerMock;

        $this->callMethod(
            $this->_oController,
            'saveAuthn'
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\d3_account_webauthn::saveAuthn
     */
    public function canSaveAuthnSuccess()
    {
        $_POST['credential'] = 'msg';
        $_POST['keyname'] = 'key_name';

        /** @var Webauthn|MockObject $webauthnMock */
        $webauthnMock = $this->getMockBuilder(Webauthn::class)
            ->onlyMethods(['saveAuthn'])
            ->getMock();
        $webauthnMock->expects($this->once())->method('saveAuthn');

        /** @var UtilsView|MockObject $utilsViewMock */
        $utilsViewMock = $this->getMockBuilder(UtilsView::class)
            ->onlyMethods(['addErrorToDisplay'])
            ->getMock();
        $utilsViewMock->expects($this->never())->method('addErrorToDisplay');

        /** @var d3_account_webauthn|MockObject $oControllerMock */
        $oControllerMock = $this->getMockBuilder(d3_account_webauthn::class)
            ->onlyMethods(['d3GetMockableOxNewObject', 'd3GetMockableRegistryObject'])
            ->getMock();
        $oControllerMock->method('d3GetMockableOxNewObject')->willReturnCallback(
            function () use ($webauthnMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case Webauthn::class:
                        return $webauthnMock;
                    default:
                        return call_user_func_array("oxNew", $args);
                }
            }
        );
        $oControllerMock->method('d3GetMockableRegistryObject')->willReturnCallback(
            function () use ($utilsViewMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case UtilsView::class:
                        return $utilsViewMock;
                    default:
                        return Registry::get($args[0]);
                }
            }
        );

        $this->_oController = $oControllerMock;

        $this->callMethod(
            $this->_oController,
            'saveAuthn'
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\d3_account_webauthn::saveAuthn
     */
    public function canSaveAuthnFailed()
    {
        $_POST['credential'] = 'msg';
        $_POST['keyname'] = 'key_name';

        /** @var Webauthn|MockObject $webauthnMock */
        $webauthnMock = $this->getMockBuilder(Webauthn::class)
            ->onlyMethods(['saveAuthn'])
            ->getMock();
        $webauthnMock->expects($this->once())->method('saveAuthn')
            ->willThrowException(oxNew(WebauthnException::class));

        /** @var UtilsView|MockObject $utilsViewMock */
        $utilsViewMock = $this->getMockBuilder(UtilsView::class)
            ->onlyMethods(['addErrorToDisplay'])
            ->getMock();
        $utilsViewMock->expects($this->atLeastOnce())->method('addErrorToDisplay');

        /** @var d3_account_webauthn|MockObject $oControllerMock */
        $oControllerMock = $this->getMockBuilder(d3_account_webauthn::class)
            ->onlyMethods(['d3GetMockableOxNewObject', 'd3GetMockableRegistryObject'])
            ->getMock();
        $oControllerMock->method('d3GetMockableOxNewObject')->willReturnCallback(
            function () use ($webauthnMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case Webauthn::class:
                        return $webauthnMock;
                    default:
                        return call_user_func_array("oxNew", $args);
                }
            }
        );
        $oControllerMock->method('d3GetMockableRegistryObject')->willReturnCallback(
            function () use ($utilsViewMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case UtilsView::class:
                        return $utilsViewMock;
                    default:
                        return Registry::get($args[0]);
                }
            }
        );

        $this->_oController = $oControllerMock;

        $this->callMethod(
            $this->_oController,
            'saveAuthn'
        );
    }

    /**
     * @test
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\d3_account_webauthn::deleteKey
     * @dataProvider canDeleteDataProvider
     */
    public function canDelete($deleteId, $expected)
    {
        $_GET['deleteoxid'] = $deleteId;

        /** @var PublicKeyCredential|MockObject $publicKeyCredentialMock */
        $publicKeyCredentialMock = $this->getMockBuilder(PublicKeyCredential::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['delete'])
            ->getMock();
        $publicKeyCredentialMock->expects($expected)->method('delete')->with($this->identicalTo($deleteId))
            ->willReturn(true);

        /** @var d3_account_webauthn|MockObject $oControllerMock */
        $oControllerMock = $this->getMockBuilder(d3_account_webauthn::class)
            ->onlyMethods(['d3GetMockableOxNewObject'])
            ->getMock();
        $oControllerMock->method('d3GetMockableOxNewObject')->willReturnCallback(
            function () use ($publicKeyCredentialMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case PublicKeyCredential::class:
                        return $publicKeyCredentialMock;
                    default:
                        return call_user_func_array("oxNew", $args);
                }
            }
        );

        $this->_oController = $oControllerMock;

        $this->callMethod($this->_oController, 'deleteKey');
    }

    /**
     * @return array[]
     */
    public function canDeleteDataProvider(): array
    {
        return [
            'has delete id' => ['deleteId', $this->once()],
            'has no delete id' => [null, $this->never()],
        ];
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\d3_account_webauthn::getBreadCrumb
     */
    public function canGetBreadCrumb()
    {
        $this->assertIsArray(
            $this->callMethod(
                $this->_oController,
                'getBreadCrumb'
            )
        );
    }
}
