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
use D3\TestingTools\Production\IsMockable;
use D3\Webauthn\Application\Controller\Admin\d3webauthnadminlogin;
use D3\Webauthn\Application\Controller\d3webauthnlogin;
use D3\Webauthn\Application\Model\Exceptions\WebauthnGetException;
use D3\Webauthn\Application\Model\WebauthnAfterLogin;
use D3\Webauthn\Application\Model\WebauthnConf;
use D3\Webauthn\Application\Model\WebauthnLogin;
use D3\Webauthn\tests\unit\Application\Controller\d3webauthnloginTest;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\Eshop\Core\Utils;
use OxidEsales\Eshop\Core\UtilsView;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;

class d3webauthnadminloginTest extends d3webauthnloginTest
{
    use IsMockable;
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
            ->onlyMethods(['d3GetMockableRegistryObject', 'd3CallMockableFunction', 'd3GetMockableOxNewObject',
                'generateCredentialRequest', 'addTplParam'])
            ->getMock();
        $sut->method('d3GetMockableRegistryObject')->willReturnCallback(
            function () use ($utilsMock, $sessionMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case Utils::class:
                        return $utilsMock;
                    case Session::class:
                        return $sessionMock;
                    default:
                        return Registry::get($args[0]);
                }
            }
        );
        $sut->method('d3CallMockableFunction')->willReturn('myTemplate.tpl');
        // "any" because redirect doesn't stop execution
        $sut->expects($startRedirect ? $this->any() : $this->atLeastOnce())
            ->method('generateCredentialRequest');
        $sut->expects($startRedirect ? $this->any() : $this->atLeastOnce())
            ->method('addTplParam')->willReturn(true);
        $sut->method('d3GetMockableOxNewObject')->willReturnCallback(
            function () use ($afterLoginMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case WebauthnAfterLogin::class:
                        return $afterLoginMock;
                    default:
                        return call_user_func_array("oxNew", $args);
                }
            }
        );

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
            ->onlyMethods(['d3GetMockableOxNewObject', 'd3GetMockableRegistryObject'])
            ->getMock();
        $sut->method('d3GetMockableOxNewObject')->willReturnCallback(
            function () use ($loginMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case WebauthnLogin::class:
                        return $loginMock;
                    default:
                        return call_user_func_array("oxNew", $args);
                }
            }
        );
        $sut->method('d3GetMockableRegistryObject')->willReturnCallback(
            function () use ($requestMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case Request::class:
                        return $requestMock;
                    default:
                        return Registry::get($args[0]);
                }
            }
        );

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
            ->onlyMethods(['d3GetMockableOxNewObject', 'd3GetMockableRegistryObject'])
            ->getMock();
        $sut->method('d3GetMockableOxNewObject')->willThrowException(oxNew(WebauthnGetException::class));
        $sut->method('d3GetMockableRegistryObject')->willReturnCallback(
            function () use ($utilsViewMock, $requestMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case UtilsView::class:
                        return $utilsViewMock;
                    case Request::class:
                        return $requestMock;
                    default:
                        return Registry::get($args[0]);
                }
            }
        );

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
}