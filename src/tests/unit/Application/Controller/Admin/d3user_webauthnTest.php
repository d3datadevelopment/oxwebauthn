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

namespace D3\Totp\tests\unit\Application\Controller;

use D3\TestingTools\Development\CanAccessRestricted;
use D3\Webauthn\Application\Controller\Admin\d3user_webauthn;
use D3\Webauthn\Application\Model\Credential\PublicKeyCredential;
use D3\Webauthn\Application\Model\Credential\PublicKeyCredentialList;
use D3\Webauthn\Application\Model\Exceptions\WebauthnException;
use D3\Webauthn\Application\Model\Webauthn;
use D3\Webauthn\Modules\Application\Model\d3_User_Webauthn;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Utils;
use OxidEsales\Eshop\Core\UtilsView;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionException;

class d3user_webauthnTest extends TestCase
{
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
                'getWebauthnObject',
                'd3CallMockableParent',
                'getEditObjectId',
                'getUserObject'
            ])
            ->getMock();
        $sutMock->method('getWebauthnObject')->willReturn($webauthnMock);
        $sutMock->method('d3CallMockableParent')->willReturn(true);
        $sutMock->method('getEditObjectId')->willReturn('editObjectId');
        $sutMock->method('getUserObject')->willReturn($userMock);

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
                'getLoggerObject',
                'getUtilsObject'
            ])
            ->getMock();
        $sutMock->expects($this->atLeastOnce())->method('setPageType');
        $sutMock->expects($this->atLeastOnce())->method('setAuthnRegister');
        $sutMock->expects($this->never())->method('getLoggerObject')->willReturn($loggerMock);
        $sutMock->expects($this->never())->method('getUtilsObject')->willReturn($utilsMock);

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
                'getLoggerObject',
                'getUtilsObject'
            ])
            ->getMock();
        $sutMock->expects($this->atLeastOnce())->method('setPageType');
        $sutMock->expects($this->atLeastOnce())->method('setAuthnRegister')->willThrowException(oxNew(WebauthnException::class));
        $sutMock->expects($this->atLeastOnce())->method('getLoggerObject')->willReturn($loggerMock);
        $sutMock->expects($this->atLeastOnce())->method('getUtilsObject')->willReturn($utilsMock);

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
            ->onlyMethods(['getUtilsViewObject', 'getLoggerObject'])
            ->getMock();
        $oControllerMock->method('getUtilsViewObject')->willReturn($utilsViewMock);
        $oControllerMock->expects($this->atLeastOnce())->method('getLoggerObject')->willReturn($loggerMock);

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
            ->onlyMethods(['getWebauthnObject', 'getUtilsViewObject'])
            ->getMock();
        $oControllerMock->method('getWebauthnObject')->willReturn($webauthnMock);
        $oControllerMock->method('getUtilsViewObject')->willReturn($utilsViewMock);

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

        /** @var LoggerInterface|MockObject $loggerMock */
        $loggerMock = $this->getMockForAbstractClass(LoggerInterface::class, [], '', true, true, true, ['error', 'debug']);
        $loggerMock->expects($this->atLeastOnce())->method('error')->willReturn(true);
        $loggerMock->expects($this->atLeastOnce())->method('debug')->willReturn(true);

        /** @var d3user_webauthn|MockObject $oControllerMock */
        $oControllerMock = $this->getMockBuilder(d3user_webauthn::class)
            ->onlyMethods(['getWebauthnObject', 'getUtilsViewObject', 'getLoggerObject'])
            ->getMock();
        $oControllerMock->method('getWebauthnObject')->willReturn($webauthnMock);
        $oControllerMock->method('getUtilsViewObject')->willReturn($utilsViewMock);
        $oControllerMock->method('getLoggerObject')->willReturn($loggerMock);

        $this->callMethod(
            $oControllerMock,
            'saveAuthn'
        );
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
            ->onlyMethods(['getWebauthnObject', 'addTplParam', 'getUser'])
            ->getMock();
        $oControllerMock->method('getWebauthnObject')->willReturn($webAuthnMock);
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
            ->onlyMethods(['getUserObject', 'getPublicKeyCredentialListObject'])
            ->getMock();
        $oControllerMock->method('getUserObject')->willReturn($oUser);
        $oControllerMock->method('getPublicKeyCredentialListObject')->willReturn($publicKeyCredentialListMock);

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
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\Admin\d3user_webauthn::getUserObject
     */
    public function getUserObjectReturnsRightInstance()
    {
        /** @var d3user_webauthn $sut */
        $sut = oxNew(d3user_webauthn::class);

        $this->assertInstanceOf(
            User::class,
            $this->callMethod(
                $sut,
                'getUserObject'
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
            ->onlyMethods(['getPublicKeyCredentialObject'])
            ->getMock();
        $oControllerMock->method('getPublicKeyCredentialObject')->willReturn($publicKeyCredentialMock);

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

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\Admin\d3user_webauthn::getUtilsObject
     */
    public function getUtilsObjectReturnsRightInstance()
    {
        /** @var d3user_webauthn $sut */
        $sut = oxNew(d3user_webauthn::class);
        $this->assertInstanceOf(
            Utils::class,
            $this->callMethod(
                $sut,
                'getUtilsObject'
            )
        );
    }
}