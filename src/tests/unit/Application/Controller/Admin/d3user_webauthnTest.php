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

use Assert\InvalidArgumentException;
use D3\TestingTools\Development\CanAccessRestricted;
use D3\TestingTools\Production\IsMockable;
use D3\Webauthn\Application\Controller\Admin\d3user_webauthn;
use D3\Webauthn\Application\Model\Credential\PublicKeyCredential;
use D3\Webauthn\Application\Model\Credential\PublicKeyCredentialList;
use D3\Webauthn\Application\Model\Exceptions\WebauthnException;
use D3\Webauthn\Application\Model\Webauthn;
use D3\Webauthn\Modules\Application\Model\d3_User_Webauthn;
use D3\Webauthn\tests\unit\WAUnitTestCase;
use Exception;
use Generator;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Utils;
use OxidEsales\Eshop\Core\UtilsView;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use ReflectionException;

class d3user_webauthnTest extends WAUnitTestCase
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
        d3GetOxidDIC()->set(Webauthn::class, $webauthnMock);

        /** @var d3_User_Webauthn|MockObject $userMock */
        $userMock = $this->getMockBuilder(d3_User_Webauthn::class)
            ->onlyMethods(['load', 'getId'])
            ->getMock();
        $userMock->expects($this->atLeastOnce())->method('load')->with('editObjectId')->willReturn($canLoadUser);
        $userMock->method('getId')->willReturn('editObjectId');
        d3GetOxidDIC()->set('d3ox.webauthn.'.User::class, $userMock);

        /** @var d3user_webauthn|MockObject $sutMock */
        $sutMock = $this->getMockBuilder(d3user_webauthn::class)
            ->onlyMethods([
                'd3CallMockableFunction',
                'getEditObjectId',
            ])
            ->getMock();
        $sutMock->method('d3CallMockableFunction')->willReturn(true);
        $sutMock->method('getEditObjectId')->willReturn('editObjectId');

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
     * @return Generator
     */
    public function canRenderDataProvider(): Generator
    {
        yield 'can load user'         => [true];
        yield 'can not load user'     => [false];
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
        d3GetOxidDIC()->set('d3ox.webauthn.'.Utils::class, $utilsMock);

        /** @var LoggerInterface|MockObject $loggerMock */
        $loggerMock = $this->getMockForAbstractClass(LoggerInterface::class, [], '', true, true, true, ['error', 'debug']);
        $loggerMock->expects($this->never())->method('error')->willReturn(true);
        $loggerMock->expects($this->never())->method('debug')->willReturn(true);
        d3GetOxidDIC()->set('d3ox.webauthn.'.LoggerInterface::class, $loggerMock);

        /** @var d3user_webauthn|MockObject $sutMock */
        $sutMock = $this->getMockBuilder(d3user_webauthn::class)
            ->onlyMethods([
                'setPageType',
                'setAuthnRegister',
            ])
            ->getMock();
        $sutMock->expects($this->atLeastOnce())->method('setPageType');
        $sutMock->expects($this->atLeastOnce())->method('setAuthnRegister');

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
        d3GetOxidDIC()->set('d3ox.webauthn.'.Utils::class, $utilsMock);

        /** @var LoggerInterface|MockObject $loggerMock */
        $loggerMock = $this->getMockForAbstractClass(LoggerInterface::class, [], '', true, true, true, ['error', 'debug']);
        $loggerMock->expects($this->atLeastOnce())->method('error')->willReturn(true);
        $loggerMock->expects($this->atLeastOnce())->method('debug')->willReturn(true);
        d3GetOxidDIC()->set('d3ox.webauthn.'.LoggerInterface::class, $loggerMock);

        /** @var d3user_webauthn|MockObject $sutMock */
        $sutMock = $this->getMockBuilder(d3user_webauthn::class)
            ->onlyMethods([
                'setPageType',
                'setAuthnRegister',
            ])
            ->getMock();
        $sutMock->expects($this->atLeastOnce())->method('setPageType');
        $sutMock->expects($this->atLeastOnce())->method('setAuthnRegister')->willThrowException(new InvalidArgumentException('msg', 20));

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
        $_GET['error'] = 'msg';
        $_REQUEST['error'] = 'msg';

        /** @var UtilsView|MockObject $utilsViewMock */
        $utilsViewMock = $this->getMockBuilder(UtilsView::class)
            ->onlyMethods(['addErrorToDisplay'])
            ->getMock();
        $utilsViewMock->expects($this->atLeastOnce())->method('addErrorToDisplay');
        d3GetOxidDIC()->set('d3ox.webauthn.'.UtilsView::class, $utilsViewMock);

        /** @var LoggerInterface|MockObject $loggerMock */
        $loggerMock = $this->getMockForAbstractClass(LoggerInterface::class, [], '', true, true, true, ['error', 'debug']);
        $loggerMock->expects($this->atLeastOnce())->method('error')->willReturn(true);
        $loggerMock->expects($this->atLeastOnce())->method('debug')->willReturn(true);
        d3GetOxidDIC()->set('d3ox.webauthn.'.LoggerInterface::class, $loggerMock);

        /** @var d3user_webauthn $oControllerMock */
        $oControllerMock = oxNew(d3user_webauthn::class);

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
        d3GetOxidDIC()->set(Webauthn::class, $webauthnMock);

        /** @var UtilsView|MockObject $utilsViewMock */
        $utilsViewMock = $this->getMockBuilder(UtilsView::class)
            ->onlyMethods(['addErrorToDisplay'])
            ->getMock();
        $utilsViewMock->expects($this->never())->method('addErrorToDisplay');
        d3GetOxidDIC()->set('d3ox.webauthn.'.UtilsView::class, $utilsViewMock);

        /** @var d3user_webauthn $oControllerMock */
        $oControllerMock = oxNew(d3user_webauthn::class);

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
        d3GetOxidDIC()->set(Webauthn::class, $webauthnMock);

        /** @var UtilsView|MockObject $utilsViewMock */
        $utilsViewMock = $this->getMockBuilder(UtilsView::class)
            ->onlyMethods(['addErrorToDisplay'])
            ->getMock();
        $utilsViewMock->expects($this->atLeastOnce())->method('addErrorToDisplay');
        d3GetOxidDIC()->set('d3ox.webauthn.'.UtilsView::class, $utilsViewMock);

        /** @var LoggerInterface|MockObject $loggerMock */
        $loggerMock = $this->getMockForAbstractClass(LoggerInterface::class, [], '', true, true, true, ['error', 'debug']);
        $loggerMock->expects($this->atLeastOnce())->method('error')->willReturn(true);
        $loggerMock->expects($this->atLeastOnce())->method('debug')->willReturn(true);
        d3GetOxidDIC()->set('d3ox.webauthn.'.LoggerInterface::class, $loggerMock);

        /** @var d3user_webauthn $oControllerMock */
        $oControllerMock = oxNew(d3user_webauthn::class);

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
        d3GetOxidDIC()->set(Webauthn::class, $webAuthnMock);

        /** @var d3user_webauthn|MockObject $oControllerMock */
        $oControllerMock = $this->getMockBuilder(d3user_webauthn::class)
            ->onlyMethods(['addTplParam', 'getUser'])
            ->getMock();
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
        d3GetOxidDIC()->set('d3ox.webauthn.'.User::class, $oUser);

        /** @var PublicKeyCredentialList|MockObject $publicKeyCredentialListMock */
        $publicKeyCredentialListMock = $this->getMockBuilder(PublicKeyCredentialList::class)
            ->onlyMethods(['getAllFromUser'])
            ->getMock();
        $publicKeyCredentialListMock->method('getAllFromUser')->with($oUser)->willReturnSelf();
        d3GetOxidDIC()->set(PublicKeyCredentialList::class, $publicKeyCredentialListMock);

        /** @var d3user_webauthn|MockObject $oControllerMock */
        $oControllerMock = $this->getMockBuilder(d3user_webauthn::class)
            ->onlyMethods([])
            ->getMock();

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
        d3GetOxidDIC()->set(PublicKeyCredential::class, $publicKeyCredentialMock);

        /** @var d3user_webauthn|MockObject $oControllerMock */
        $oControllerMock = $this->getMockBuilder(d3user_webauthn::class)
            ->onlyMethods([])
            ->getMock();

        $this->callMethod($oControllerMock, 'deleteKey');
    }

    /**
     * @return array[]
     */
    public function canDeleteDataProvider(): array
    {
        return [
            'has delete id' => ['deleteId', $this->once()],
        ];
    }
}
