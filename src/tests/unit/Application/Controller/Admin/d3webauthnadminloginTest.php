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

namespace D3\Webauthn\tests\unit\Application\Controller\Admin;

use D3\TestingTools\Development\CanAccessRestricted;
use D3\Webauthn\Application\Controller\Admin\d3webauthnadminlogin;
use D3\Webauthn\Application\Controller\d3webauthnlogin;
use D3\Webauthn\Application\Model\Exceptions\WebauthnException;
use D3\Webauthn\Application\Model\Webauthn;
use D3\Webauthn\Application\Model\WebauthnConf;
use D3\Webauthn\tests\unit\Application\Controller\d3webauthnloginTest;
use OxidEsales\Eshop\Application\Controller\Admin\LoginController;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\Eshop\Core\SystemEventHandler;
use OxidEsales\Eshop\Core\Utils;
use OxidEsales\Eshop\Core\UtilsServer;
use OxidEsales\Eshop\Core\UtilsView;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use ReflectionException;

class d3webauthnadminloginTest extends d3webauthnloginTest
{
    use CanAccessRestricted;

    protected $sutClassName = d3webauthnadminlogin::class;

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\Admin\d3webauthnadminlogin::_authorize
     */
    public function canAuthorize()
    {
        $sut = oxNew(d3webauthnadminlogin::class);

        $this->assertTrue(
            $this->callMethod(
                $sut,
                '_authorize'
            )
        );
    }

    /**
     * @return void
     */
    public function canGetNavigationParams()
    {}

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\Admin\d3webauthnadminlogin::render
     * @dataProvider canRenderDataProvider
     */
    public function canRender($auth, $userFromLogin, $startRedirect, $redirectController)
    {
        /** @var LoginController|MockObject $loginControllerMock */
        $loginControllerMock = $this->getMockBuilder(LoginController::class)
            ->onlyMethods(['d3WebauthnAfterLoginChangeLanguage'])
            ->getMock();
        $loginControllerMock->expects($this->once())->method('d3WebauthnAfterLoginChangeLanguage')->willReturn(true);

        /** @var Session|MockObject $sessionMock */
        $sessionMock = $this->getMockBuilder(Session::class)
            ->onlyMethods(['hasVariable'])
            ->getMock();
        $sessionMock->method('hasVariable')->willReturnMap([
            [WebauthnConf::WEBAUTHN_ADMIN_SESSION_AUTH, $auth],
            [WebauthnConf::WEBAUTHN_ADMIN_SESSION_CURRENTUSER, $userFromLogin]
        ]);

        /** @var Utils|MockObject $utilsMock */
        $utilsMock = $this->getMockBuilder(Utils::class)
            ->onlyMethods(['redirect'])
            ->getMock();
        $utilsMock->expects($startRedirect ? $this->once() : $this->never())
            ->method('redirect')->with('index.php?cl='.$redirectController)->willReturn(true);

        /** @var d3webauthnlogin|MockObject $sut */
        $sut = $this->getMockBuilder($this->sutClassName)
            ->onlyMethods(['d3GetSession', 'getUtils', 'd3CallMockableParent',
                'generateCredentialRequest', 'addTplParam', 'd3WebauthnGetLoginController'])
            ->getMock();
        $sut->method('d3GetSession')->willReturn($sessionMock);
        $sut->method('getUtils')->willReturn($utilsMock);
        $sut->method('d3CallMockableParent')->willReturn('myTemplate.tpl');
        // "any" because redirect doesn't stop execution
        $sut->expects($startRedirect ? $this->any() : $this->atLeastOnce())
            ->method('generateCredentialRequest');
        $sut->expects($startRedirect ? $this->any() : $this->atLeastOnce())
            ->method('addTplParam')->willReturn(true);
        $sut->method('d3WebauthnGetLoginController')->willReturn($loginControllerMock);

        $this->assertSame(
            'myTemplate.tpl',
            $this->callMethod(
                $sut,
                'render'
            )
        );
    }

    /**
     * @return array
     */
    public function canRenderDataProvider(): array
    {
        return [
            'has request'   => [false, true, false, 'start'],
            'has auth'   => [true, true, true, 'admin_start'],
            'missing user'   => [false, false, true, 'login'],
        ];
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\Admin\d3webauthnadminlogin::generateCredentialRequest
     */
    public function canGenerateCredentialRequest($userSessionVarName = WebauthnConf::WEBAUTHN_ADMIN_SESSION_CURRENTUSER)
    {
        parent::canGenerateCredentialRequest($userSessionVarName);
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\Admin\d3webauthnadminlogin::generateCredentialRequest
     */
    public function generateCredentialRequestFailed($redirectClass = 'login', $userVarName = WebauthnConf::WEBAUTHN_ADMIN_SESSION_CURRENTUSER)
    {
        parent::generateCredentialRequestFailed($redirectClass, $userVarName);
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\Admin\d3webauthnadminlogin::getUtils
     */
    public function getUtilsReturnsRightInstance()
    {
        parent::getUtilsReturnsRightInstance();
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\Admin\d3webauthnadminlogin::d3GetPreviousClass
     */
    public function canGetPreviousClass($sessionVarName = WebauthnConf::WEBAUTHN_ADMIN_SESSION_CURRENTCLASS)
    {
        parent::canGetPreviousClass($sessionVarName);
    }

    /**
     * @test
     * @param $currClass
     * @param $isOrderStep
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\Admin\d3webauthnadminlogin::previousClassIsOrderStep
     * @dataProvider canPreviousClassIsOrderStepDataProvider
     */
    public function canPreviousClassIsOrderStep($currClass, $isOrderStep)
    {
        parent::canPreviousClassIsOrderStep($currClass, $isOrderStep);
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\Admin\d3webauthnadminlogin::getIsOrderStep
     * @dataProvider canGetIsOrderStepDataProvider
     */
    public function canGetIsOrderStep($boolean)
    {
        parent::canGetIsOrderStep($boolean);
    }

    public function canGetBreadCrumb()
    {
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\Admin\d3webauthnadminlogin::d3WebauthnGetLoginController
     */
    public function canGetLoginController()
    {
        $sut = oxNew(d3webauthnadminlogin::class);

        $this->assertInstanceOf(
            LoginController::class,
            $this->callMethod(
                $sut,
                'd3WebauthnGetLoginController'
            )
        );
    }

    /**
     * @test
     * @param $error
     * @param $credential
     * @param $canAssert
     * @param $return
     * @param $showErrorMsg
     * @return void
     * @throws ReflectionException
     * @dataProvider canAssertAuthnDataProvider
     * @covers       \D3\Webauthn\Application\Controller\Admin\d3webauthnadminlogin::d3AssertAuthn
     */
    public function canAssertAuthn($error, $credential, $canAssert, $return, $showErrorMsg)
    {
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->getMockBuilder(Request::class)
            ->onlyMethods(['getRequestEscapedParameter'])
            ->getMock();
        $requestMock->method('getRequestEscapedParameter')->willReturnCallback(
            function () use ($error, $credential) {
                $args = func_get_args();
                if ($args[0] === 'error')
                    return $error;
                elseif ($args[0] === 'credential')
                    return $credential;
                return null;
            }
        );

        /** @var Webauthn|MockObject $webauthnMock */
        $webauthnMock = $this->getMockBuilder(Webauthn::class)
            ->onlyMethods(['assertAuthn'])
            ->getMock();
        if ($canAssert) {
            $webauthnMock->expects($error || !$credential ? $this->never() : $this->once())->method('assertAuthn');
        } else {
            $webauthnMock->expects($error || !$credential ? $this->never() : $this->once())->method('assertAuthn')
                ->willThrowException(oxNew(WebauthnException::class));
        }

        /** @var Session|MockObject $sessionMock */
        $sessionMock = $this->getMockBuilder(Session::class)
            ->onlyMethods(['initNewSession', 'setVariable'])
            ->getMock();
        $sessionMock->expects($canAssert ? $this->once() : $this->never())->method('initNewSession');
        $sessionMock->expects($canAssert ? $this->atLeast(2) : $this->never())->method('setVariable');

        /** @var SystemEventHandler|MockObject $eventHandlerMock */
        $eventHandlerMock = $this->getMockBuilder(SystemEventHandler::class)
            ->onlyMethods(['onAdminLogin'])
            ->getMock();
        $eventHandlerMock->expects($canAssert ? $this->once() : $this->never())->method('onAdminLogin');

        /** @var LoginController|MockObject $loginControllerMock */
        $loginControllerMock = $this->getMockBuilder(LoginController::class)
            ->onlyMethods(['d3webauthnAfterLogin'])
            ->getMock();
        $loginControllerMock->expects($canAssert ? $this->once() : $this->never())->method('d3webauthnAfterLogin');

        /** @var UtilsView|MockObject $utilsViewMock */
        $utilsViewMock = $this->getMockBuilder(UtilsView::class)
            ->onlyMethods(['addErrorToDisplay'])
            ->getMock();
        $utilsViewMock->expects($showErrorMsg ? $this->once() : $this->never())->method('addErrorToDisplay');

        /** @var UtilsServer|MockObject $utilsServerMock */
        $utilsServerMock = $this->getMockBuilder(UtilsServer::class)
            ->onlyMethods(['getOxCookie'])
            ->getMock();
        $utilsServerMock->method('getOxCookie')->willReturn('cookie');

        /** @var LoggerInterface|MockObject $loggerMock */
        $loggerMock = $this->getMockForAbstractClass(LoggerInterface::class, [], '', true, true, true, ['error', 'debug']);
        $loggerMock->method('error')->willReturn(true);
        $loggerMock->method('debug')->willReturn(true);

        /** @var d3webauthnadminlogin|MockObject $sut */
        $sut = $this->getMockBuilder(d3webauthnadminlogin::class)
            ->onlyMethods(['d3WebAuthnGetRequest', 'd3GetWebauthnObject', 'd3GetSession', 'd3WebauthnGetEventHandler',
                'd3WebauthnGetLoginController', 'd3GetUtilsViewObject', 'd3GetLoggerObject', 'd3WebauthnGetUtilsServer'])
            ->getMock();
        $sut->method('d3WebAuthnGetRequest')->willReturn($requestMock);
        $sut->method('d3GetWebauthnObject')->willReturn($webauthnMock);
        $sut->method('d3GetSession')->willReturn($sessionMock);
        $sut->method('d3WebauthnGetEventHandler')->willReturn($eventHandlerMock);
        $sut->method('d3WebauthnGetLoginController')->willReturn($loginControllerMock);
        $sut->method('d3GetUtilsViewObject')->willReturn($utilsViewMock);
        $sut->method('d3GetLoggerObject')->willReturn($loggerMock);
        $sut->method('d3WebauthnGetUtilsServer')->willReturn($utilsServerMock);

        $this->assertSame(
            $return,
            $this->callMethod(
                $sut,
                'd3AssertAuthn'
            )
        );
    }

    /**
     * @return array
     */
    public function canAssertAuthnDataProvider(): array
    {
        return [
            'has error' => ['errorFixture', null, false, 'login', true],
            'missing credential' => [null, null, false, 'login', true],
            'assertion failed' => [null, 'credential', false, 'login', true],
            'assertion succ' => [null, 'credential', true, 'admin_start', false],
        ];
    }

    /**
     * @test
     * @param $return
     * @param $showErrorMsg
     * @param $cookie
     * @return void
     * @throws ReflectionException
     * @dataProvider canAssertAuthnCookieSubshopDataProvider
     * @covers       \D3\Webauthn\Application\Controller\Admin\d3webauthnadminlogin::d3AssertAuthn
     */
    public function canAssertAuthnCookieSubshop($return, $showErrorMsg, $cookie, $rights)
    {
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->getMockBuilder(Request::class)
            ->onlyMethods(['getRequestEscapedParameter'])
            ->getMock();
        $requestMock->method('getRequestEscapedParameter')->willReturnCallback(
            function () {
                $args = func_get_args();
                if ($args[0] === 'error')
                    return null;
                elseif ($args[0] === 'credential')
                    return 'credential';
                return null;
            }
        );

        /** @var Webauthn|MockObject $webauthnMock */
        $webauthnMock = $this->getMockBuilder(Webauthn::class)
            ->onlyMethods(['assertAuthn'])
            ->getMock();
        $webauthnMock->expects($this->once())->method('assertAuthn');

        /** @var Session|MockObject $sessionMock */
        $sessionMock = $this->getMockBuilder(Session::class)
            ->onlyMethods(['initNewSession', 'setVariable'])
            ->getMock();
        $sessionMock->expects($this->once())->method('initNewSession');
        $sessionMock->expects($this->atLeast(is_int($rights) ? 4 : 2))->method('setVariable');

        /** @var SystemEventHandler|MockObject $eventHandlerMock */
        $eventHandlerMock = $this->getMockBuilder(SystemEventHandler::class)
            ->onlyMethods(['onAdminLogin'])
            ->getMock();
        $eventHandlerMock->expects($cookie && $rights != 'user' ? $this->once() : $this->never())->method('onAdminLogin');

        /** @var LoginController|MockObject $loginControllerMock */
        $loginControllerMock = $this->getMockBuilder(LoginController::class)
            ->onlyMethods(['d3webauthnAfterLogin'])
            ->getMock();
        $loginControllerMock->expects($cookie && $rights != 'user' ? $this->once() : $this->never())->method('d3webauthnAfterLogin');

        /** @var UtilsView|MockObject $utilsViewMock */
        $utilsViewMock = $this->getMockBuilder(UtilsView::class)
            ->onlyMethods(['addErrorToDisplay'])
            ->getMock();
        $utilsViewMock->expects($showErrorMsg ? $this->once() : $this->never())->method('addErrorToDisplay');

        /** @var UtilsServer|MockObject $utilsServerMock */
        $utilsServerMock = $this->getMockBuilder(UtilsServer::class)
            ->onlyMethods(['getOxCookie'])
            ->getMock();
        $utilsServerMock->method('getOxCookie')->willReturn($cookie);

        /** @var LoggerInterface|MockObject $loggerMock */
        $loggerMock = $this->getMockForAbstractClass(LoggerInterface::class, [], '', true, true, true, ['error', 'debug']);
        $loggerMock->method('error')->willReturn(true);
        $loggerMock->method('debug')->willReturn(true);

        /** @var User|MockObject $userMock */
        $userMock = $this->getMockBuilder(User::class)
            ->onlyMethods(['getFieldData'])
            ->getMock();
        $userMock->method('getFieldData')->willReturn($rights);

        /** @var d3webauthnadminlogin|MockObject $sut */
        $sut = $this->getMockBuilder(d3webauthnadminlogin::class)
            ->onlyMethods(['d3WebAuthnGetRequest', 'd3GetWebauthnObject', 'd3GetSession', 'd3WebauthnGetEventHandler',
                'd3WebauthnGetLoginController', 'd3GetUtilsViewObject', 'd3GetLoggerObject', 'd3WebauthnGetUtilsServer',
                'd3GetUserObject'])
            ->getMock();
        $sut->method('d3WebAuthnGetRequest')->willReturn($requestMock);
        $sut->method('d3GetWebauthnObject')->willReturn($webauthnMock);
        $sut->method('d3GetSession')->willReturn($sessionMock);
        $sut->method('d3WebauthnGetEventHandler')->willReturn($eventHandlerMock);
        $sut->method('d3WebauthnGetLoginController')->willReturn($loginControllerMock);
        $sut->method('d3GetUtilsViewObject')->willReturn($utilsViewMock);
        $sut->method('d3GetLoggerObject')->willReturn($loggerMock);
        $sut->method('d3WebauthnGetUtilsServer')->willReturn($utilsServerMock);
        $sut->method('d3GetUserObject')->willReturn($userMock);

        $this->assertSame(
            $return,
            $this->callMethod(
                $sut,
                'd3AssertAuthn'
            )
        );
    }

    /**
     * @return array
     */
    public function canAssertAuthnCookieSubshopDataProvider(): array
    {
        return [
            'missing cookie' => ['login', true, null, 'user'],
            'no admin user' => ['login', true, 'cookie', 'user'],
            'assertion succ malladmin' => ['admin_start', false, 'cookie', 'malladmin'],
            'assertion succ shop1' => ['admin_start', false, 'cookie', 1],
        ];
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\Admin\d3webauthnadminlogin::d3WebauthnGetEventHandler
     */
    public function canGetSystemEventHandler()
    {
        $sut = oxNew(d3webauthnadminlogin::class);

        $this->assertInstanceOf(
            SystemEventHandler::class,
            $this->callMethod(
                $sut,
                'd3WebauthnGetEventHandler'
            )
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\Admin\d3webauthnadminlogin::d3WebAuthnGetRequest
     */
    public function canGetRequest()
    {
        $sut = oxNew(d3webauthnadminlogin::class);

        $this->assertInstanceOf(
            Request::class,
            $this->callMethod(
                $sut,
                'd3WebAuthnGetRequest'
            )
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\Admin\d3webauthnadminlogin::d3WebauthnGetUtilsServer
     */
    public function canGetUtilsServer()
    {
        $sut = oxNew(d3webauthnadminlogin::class);

        $this->assertInstanceOf(
            UtilsServer::class,
            $this->callMethod(
                $sut,
                'd3WebauthnGetUtilsServer'
            )
        );
    }
}