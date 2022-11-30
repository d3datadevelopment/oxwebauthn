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
use D3\Webauthn\Application\Model\Exceptions\WebauthnGetException;
use D3\Webauthn\Application\Model\WebauthnAfterLogin;
use D3\Webauthn\Application\Model\WebauthnConf;
use D3\Webauthn\Application\Model\WebauthnLogin;
use D3\Webauthn\tests\unit\Application\Controller\d3webauthnloginTest;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\Eshop\Core\SystemEventHandler;
use OxidEsales\Eshop\Core\Utils;
use OxidEsales\Eshop\Core\UtilsServer;
use OxidEsales\Eshop\Core\UtilsView;
use PHPUnit\Framework\MockObject\MockObject;
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

        /** @var WebauthnAfterLogin|MockObject $afterLoginMock */
        $afterLoginMock = $this->getMockBuilder(WebauthnAfterLogin::class)
            ->onlyMethods(['changeLanguage'])
            ->getMock();
        $afterLoginMock->expects($this->once())->method('changeLanguage');

        /** @var d3webauthnlogin|MockObject $sut */
        $sut = $this->getMockBuilder($this->sutClassName)
            ->onlyMethods(['d3GetSession', 'getUtils', 'd3CallMockableParent', 'd3WebauthnGetAfterLogin',
                'generateCredentialRequest', 'addTplParam'])
            ->getMock();
        $sut->method('d3GetSession')->willReturn($sessionMock);
        $sut->method('getUtils')->willReturn($utilsMock);
        $sut->method('d3CallMockableParent')->willReturn('myTemplate.tpl');
        // "any" because redirect doesn't stop execution
        $sut->expects($startRedirect ? $this->any() : $this->atLeastOnce())
            ->method('generateCredentialRequest');
        $sut->expects($startRedirect ? $this->any() : $this->atLeastOnce())
            ->method('addTplParam')->willReturn(true);
        $sut->method('d3WebauthnGetAfterLogin')->willReturn($afterLoginMock);

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
     * @covers       \D3\Webauthn\Application\Controller\Admin\d3webauthnadminlogin::d3AssertAuthn
     */
    public function canAssertAuthn()
    {
        /** @var WebauthnLogin|MockObject $loginMock */
        $loginMock = $this->getMockBuilder(WebauthnLogin::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['adminLogin'])
            ->getMock();
        $loginMock->expects($this->once())->method('adminLogin')->willReturn('expected');

        /** @var Request|MockObject $requestMock */
        $requestMock = $this->getMockBuilder(Request::class)
            ->onlyMethods(['getRequestEscapedParameter'])
            ->getMock();
        $requestMock->expects($this->exactly(3))->method('getRequestEscapedParameter')->willReturn('abc');

        /** @var d3webauthnadminlogin|MockObject $sut */
        $sut = $this->getMockBuilder(d3webauthnadminlogin::class)
            ->onlyMethods(['getWebauthnLoginObject', 'd3WebAuthnGetRequest'])
            ->getMock();
        $sut->method('getWebauthnLoginObject')->willReturn($loginMock);
        $sut->method('d3WebAuthnGetRequest')->willReturn($requestMock);

        $this->assertSame(
            'expected',
            $this->callMethod(
                $sut,
                'd3AssertAuthn'
            )
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers       \D3\Webauthn\Application\Controller\Admin\d3webauthnadminlogin::d3AssertAuthn
     */
    public function cannotAssertAuthn()
    {
        /** @var UtilsView|MockObject $utilsViewMock */
        $utilsViewMock = $this->getMockBuilder(UtilsView::class)
            ->onlyMethods(['addErrorToDisplay'])
            ->getMock();
        $utilsViewMock->expects($this->once())->method('addErrorToDisplay');

        /** @var Request|MockObject $requestMock */
        $requestMock = $this->getMockBuilder(Request::class)
            ->onlyMethods(['getRequestEscapedParameter'])
            ->getMock();
        $requestMock->expects($this->atLeast(2))->method('getRequestEscapedParameter')->willReturn('abc');

        /** @var d3webauthnadminlogin|MockObject $sut */
        $sut = $this->getMockBuilder(d3webauthnadminlogin::class)
            ->onlyMethods(['getWebauthnLoginObject', 'd3WebAuthnGetRequest', 'd3GetUtilsViewObject'])
            ->getMock();
        $sut->method('getWebauthnLoginObject')->willThrowException(oxNew(WebauthnGetException::class));
        $sut->method('d3WebAuthnGetRequest')->willReturn($requestMock);
        $sut->method('d3GetUtilsViewObject')->willReturn($utilsViewMock);

        $this->assertSame(
            'login',
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

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\Admin\d3webauthnadminlogin::getWebauthnLoginObject
     */
    public function canGetWebauthnLoginObject()
    {
        $sut = oxNew(d3webauthnadminlogin::class);

        $this->assertInstanceOf(
            WebauthnLogin::class,
            $this->callMethod(
                $sut,
                'getWebauthnLoginObject',
                ['credential', 'error']
            )
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\Admin\d3webauthnadminlogin::d3WebauthnGetAfterLogin
     */
    public function canGetWebauthnAfterLoginObject()
    {
        $sut = oxNew(d3webauthnadminlogin::class);

        $this->assertInstanceOf(
            WebauthnAfterLogin::class,
            $this->callMethod(
                $sut,
                'd3WebauthnGetAfterLogin'
            )
        );
    }
}