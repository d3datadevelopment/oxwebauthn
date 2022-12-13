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

namespace D3\Webauthn\tests\unit\Application\Controller\Admin;

use D3\TestingTools\Development\CanAccessRestricted;
use D3\TestingTools\Production\IsMockable;
use D3\Webauthn\Application\Controller\Admin\d3user_webauthn;
use D3\Webauthn\Application\Model\Credential\PublicKeyCredential;
use D3\Webauthn\Application\Model\Credential\PublicKeyCredentialList;
use D3\Webauthn\Application\Model\Exceptions\WebauthnException;
use D3\Webauthn\Application\Model\Webauthn;
use D3\Webauthn\Modules\Application\Model\d3_User_Webauthn;
use Exception;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Utils;
use OxidEsales\Eshop\Core\UtilsView;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionException;

class d3user_webauthnTest extends TestCase
{
    use IsMockable;
    use CanAccessRestricted;

    public function setUp(): void
    {
        parent::setUp();

        unset($_POST['error']);
        unset($_POST['credential']);
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\Admin\d3user_webauthn::render
     * @dataProvider canRenderDataProvider
     */
    public function canRender($canLoadUser)
    {
        /** @var Webauthn|MockObject $webauthnMock */
        $webauthnMock = $this->getMockBuilder(Webauthn::class)
            ->onlyMethods(['isAvailable'])
            ->getMock();
        $webauthnMock->method('isAvailable')->willReturn(false);

        /** @var d3_User_Webauthn|MockObject $userMock */
        $userMock = $this->getMockBuilder(d3_User_Webauthn::class)
            ->onlyMethods(['load', 'getId'])
            ->getMock();
        $userMock->expects($this->atLeastOnce())->method('load')->with('editObjectId')->willReturn($canLoadUser);
        $userMock->method('getId')->willReturn('editObjectId');

        /** @var d3user_webauthn|MockObject $sutMock */
        $sutMock = $this->getMockBuilder(d3user_webauthn::class)
            ->onlyMethods([
                'd3CallMockableFunction',
                'getEditObjectId',
                'd3GetMockableOxNewObject'
            ])
            ->getMock();
        $sutMock->method('d3CallMockableFunction')->willReturn(true);
        $sutMock->method('getEditObjectId')->willReturn('editObjectId');
        $sutMock->method('d3GetMockableOxNewObject')->willReturnCallback(
            function () use ($userMock, $webauthnMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case User::class:
                        return $userMock;
                    case Webauthn::class:
                        return $webauthnMock;
                    default:
                        return call_user_func_array("oxNew", $args);
                }
            }
        );

        $this->setValue(
            $sutMock,
            '_sSaveError',
            'saveErrorFixture'
        );

        $this->assertIsString(
            $this->callMethod(
                $sutMock,
                'render'
            )
        );

        $this->assertTrue($sutMock->getViewDataElement('readonly'));
        $this->assertSame($canLoadUser ? 'editObjectId' : '-1', $sutMock->getViewDataElement('oxid'));
        $this->assertSame($userMock, $sutMock->getViewDataElement('edit'));
        $this->assertSame('saveErrorFixture', $sutMock->getViewDataElement('sSaveError'));
    }

    /**
     * @return array
     */
    public function canRenderDataProvider(): array
    {
        return [
            'can load user'         => [true],
            'can not load user'     => [false],
        ];
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\Admin\d3user_webauthn::requestNewCredential
     */
    public function canRequestNewCredentialPassed()
    {
        /** @var Utils|MockObject $utilsMock */
        $utilsMock = $this->getMockBuilder(Utils::class)
            ->onlyMethods(['redirect'])
            ->getMock();
        $utilsMock->expects($this->never())->method('redirect')->willReturn(true);

        /** @var LoggerInterface|MockObject $loggerMock */
        $loggerMock = $this->getMockForAbstractClass(LoggerInterface::class, [], '', true, true, true, ['error', 'debug']);
        $loggerMock->expects($this->never())->method('error')->willReturn(true);
        $loggerMock->expects($this->never())->method('debug')->willReturn(true);

        /** @var d3user_webauthn|MockObject $sutMock */
        $sutMock = $this->getMockBuilder(d3user_webauthn::class)
            ->onlyMethods([
                'setPageType',
                'setAuthnRegister',
                'd3GetMockableLogger',
                'd3GetMockableRegistryObject'
            ])
            ->getMock();
        $sutMock->expects($this->atLeastOnce())->method('setPageType');
        $sutMock->expects($this->atLeastOnce())->method('setAuthnRegister');
        $sutMock->expects($this->never())->method('d3GetMockableLogger')->willReturn($loggerMock);
        $sutMock->expects($this->never())->method('d3GetMockableRegistryObject')->willReturnCallback(
            function () use ($utilsMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case Utils::class:
                        return $utilsMock;
                    default:
                        return Registry::get($args[0]);
                }
            }
        );

        $this->callMethod(
            $sutMock,
            'requestNewCredential'
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\Admin\d3user_webauthn::requestNewCredential
     */
    public function canRequestNewCredentialFailed()
    {
        /** @var Utils|MockObject $utilsMock */
        $utilsMock = $this->getMockBuilder(Utils::class)
            ->onlyMethods(['redirect'])
            ->getMock();
        $utilsMock->expects($this->once())->method('redirect')->willReturn(true);

        /** @var LoggerInterface|MockObject $loggerMock */
        $loggerMock = $this->getMockForAbstractClass(LoggerInterface::class, [], '', true, true, true, ['error', 'debug']);
        $loggerMock->expects($this->atLeastOnce())->method('error')->willReturn(true);
        $loggerMock->expects($this->atLeastOnce())->method('debug')->willReturn(true);

        /** @var d3user_webauthn|MockObject $sutMock */
        $sutMock = $this->getMockBuilder(d3user_webauthn::class)
            ->onlyMethods([
                'setPageType',
                'setAuthnRegister',
                'd3GetMockableLogger',
                'd3GetMockableRegistryObject'
            ])
            ->getMock();
        $sutMock->expects($this->atLeastOnce())->method('setPageType');
        $sutMock->expects($this->atLeastOnce())->method('setAuthnRegister')->willThrowException(oxNew(WebauthnException::class));
        $sutMock->expects($this->atLeastOnce())->method('d3GetMockableLogger')->willReturn($loggerMock);
        $sutMock->expects($this->atLeastOnce())->method('d3GetMockableRegistryObject')->willReturnCallback(
            function () use ($utilsMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case Utils::class:
                        return $utilsMock;
                    default:
                        return Registry::get($args[0]);
                }
            }
        );

        $this->callMethod(
            $sutMock,
            'requestNewCredential'
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\Admin\d3user_webauthn::saveAuthn
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
        $loggerMock->expects($this->atLeastOnce())->method('error')->willReturn(true);
        $loggerMock->expects($this->atLeastOnce())->method('debug')->willReturn(true);

        /** @var d3user_webauthn|MockObject $oControllerMock */
        $oControllerMock = $this->getMockBuilder(d3user_webauthn::class)
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
        $oControllerMock->expects($this->atLeastOnce())->method('d3GetMockableLogger')->willReturn($loggerMock);

        $this->callMethod(
            $oControllerMock,
            'saveAuthn'
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\Admin\d3user_webauthn::saveAuthn
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

        /** @var d3user_webauthn|MockObject $oControllerMock */
        $oControllerMock = $this->getMockBuilder(d3user_webauthn::class)
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

        $this->callMethod(
            $oControllerMock,
            'saveAuthn'
        );
    }

    /**
     * @test
     * @param string $excClass
     * @return void
     * @throws ReflectionException
     * @dataProvider canSaveAuthnFailedDataProvider
     * @covers \D3\Webauthn\Application\Controller\Admin\d3user_webauthn::saveAuthn
     */
    public function canSaveAuthnFailed(string $excClass)
    {
        $_POST['credential'] = 'msg';
        $_POST['keyname'] = 'key_name';

        /** @var Webauthn|MockObject $webauthnMock */
        $webauthnMock = $this->getMockBuilder(Webauthn::class)
            ->onlyMethods(['saveAuthn'])
            ->getMock();
        $webauthnMock->expects($this->once())->method('saveAuthn')
            ->willThrowException(oxNew($excClass));

        /** @var UtilsView|MockObject $utilsViewMock */
        $utilsViewMock = $this->getMockBuilder(UtilsView::class)
            ->onlyMethods(['addErrorToDisplay'])
            ->getMock();
        $utilsViewMock->expects($this->atLeastOnce())->method('addErrorToDisplay');

        /** @var LoggerInterface|MockObject $loggerMock */
        $loggerMock = $this->getMockForAbstractClass(LoggerInterface::class, [], '', true, true, true, ['error', 'debug']);
        $loggerMock->expects($this->atLeastOnce())->method('error')->willReturn(true);
        $loggerMock->expects($this->atLeastOnce())->method('debug')->willReturn(true);

        /** @var d3user_webauthn|MockObject $oControllerMock */
        $oControllerMock = $this->getMockBuilder(d3user_webauthn::class)
            ->onlyMethods(['d3GetMockableOxNewObject', 'd3GetMockableRegistryObject', 'd3GetMockableLogger'])
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
        $oControllerMock->method('d3GetMockableLogger')->willReturn($loggerMock);

        $this->callMethod(
            $oControllerMock,
            'saveAuthn'
        );
    }

    /**
     * @return array[]
     */
    public function canSaveAuthnFailedDataProvider(): array
    {
        return [
            'webauthn exception'    => [WebauthnException::class],
            'common exception'    => [Exception::class],
        ];
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\Admin\d3user_webauthn::setPageType
     */
    public function canSetPageType()
    {
        $sut = $this->getMockBuilder(d3user_webauthn::class)
            ->onlyMethods(['addTplParam'])
            ->getMock();
        $sut->expects($this->atLeastOnce())->method('addTplParam');

        $this->callMethod(
            $sut,
            'setPageType',
            ['pageTypeFixture']
        );
    }

    /**
     * @test
     * @param $throwExc
     * @return void
     * @throws ReflectionException
     * @dataProvider canSetAuthnRegisterDataProvider
     * @covers \D3\Webauthn\Application\Controller\Admin\d3user_webauthn::setAuthnRegister
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

        /** @var d3user_webauthn|MockObject $oControllerMock */
        $oControllerMock = $this->getMockBuilder(d3user_webauthn::class)
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

        if ($throwExc) {
            $this->expectException(WebauthnException::class);
        }

        $this->callMethod(
            $oControllerMock,
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
     * @covers \D3\Webauthn\Application\Controller\Admin\d3user_webauthn::getCredentialList
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

        /** @var d3user_webauthn|MockObject $oControllerMock */
        $oControllerMock = $this->getMockBuilder(d3user_webauthn::class)
            ->onlyMethods(['d3GetMockableOxNewObject'])
            ->getMock();
        $oControllerMock->method('d3GetMockableOxNewObject')->willReturnCallback(
            function () use ($oUser, $publicKeyCredentialListMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case User::class:
                        return $oUser;
                    case PublicKeyCredentialList::class:
                        return $publicKeyCredentialListMock;
                    default:
                        return call_user_func_array("oxNew", $args);
                }
            }
        );

        $this->assertIsArray(
            $this->callMethod(
                $oControllerMock,
                'getCredentialList',
                ['myUserId']
            )
        );
    }

    /**
     * @test
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\Admin\d3user_webauthn::deleteKey
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

        /** @var d3user_webauthn|MockObject $oControllerMock */
        $oControllerMock = $this->getMockBuilder(d3user_webauthn::class)
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

        $this->callMethod($oControllerMock, 'deleteKey');
    }

    /**
     * @return array[]
     */
    public function canDeleteDataProvider(): array
    {
        return [
            'has delete id' => ['deleteId', $this->once()]
        ];
    }
}