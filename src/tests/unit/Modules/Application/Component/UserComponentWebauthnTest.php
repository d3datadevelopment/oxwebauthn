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

namespace D3\Webauthn\tests\unit\Modules\Application\Component;

use D3\TestingTools\Development\CanAccessRestricted;
use D3\Webauthn\Application\Model\Exceptions\WebauthnGetException;
use D3\Webauthn\Application\Model\Exceptions\WebauthnLoginErrorException;
use D3\Webauthn\Application\Model\Webauthn;
use D3\Webauthn\Application\Model\WebauthnConf;
use D3\Webauthn\Application\Model\WebauthnLogin;
use D3\Webauthn\Modules\Application\Component\d3_webauthn_UserComponent;
use D3\Webauthn\Modules\Application\Component\d3_webauthn_UserComponent_parent;
use D3\Webauthn\tests\unit\WAUnitTestCase;
use OxidEsales\Eshop\Application\Component\UserComponent;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Controller\BaseController;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\Eshop\Core\Utils;
use OxidEsales\Eshop\Core\UtilsView;
use OxidEsales\TestingLibrary\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;

class UserComponentWebauthnTest extends WAUnitTestCase
{
    use CanAccessRestricted;

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Modules\Application\Component\d3_webauthn_UserComponent::login
     */
    public function canLogin()
    {
        /** @var d3_webauthn_UserComponent|MockObject $sut */
        $sut = $this->getMockBuilder(UserComponent::class)
            ->onlyMethods(['d3CallMockableFunction', 'd3WebauthnLogin'])
            ->getMock();
        $sut->expects($this->once())->method('d3CallMockableFunction')->with(
            $this->identicalTo([d3_webauthn_UserComponent_parent::class, 'login'])
        );
        $sut->expects($this->once())->method('d3WebauthnLogin');

        $this->callMethod(
            $sut,
            'login'
        );
    }

    /**
     * @test
     * @param $canUseWebauthn
     * @param $loggedin
     * @param $setVariableCount
     * @param $doRedirect
     * @return void
     * @throws ReflectionException
     * @dataProvider canCheckloginDataProvider
     * @covers       \D3\Webauthn\Modules\Application\Component\d3_webauthn_UserComponent::d3WebauthnLogin
     */
    public function canWebauthnLogin($canUseWebauthn, $loggedin, $setVariableCount, $doRedirect)
    {
        /** @var Utils|MockObject $utilsMock */
        $utilsMock = $this->getMockBuilder(Utils::class)
            ->onlyMethods(['redirect'])
            ->getMock();
        $utilsMock->expects($this->exactly((int) $doRedirect))->method('redirect');
        d3GetOxidDIC()->set('d3ox.webauthn.'.Utils::class, $utilsMock);

        /** @var BaseController|MockObject $baseControllerMock */
        $baseControllerMock = $this->getMockBuilder(BaseController::class)
            ->addMethods(['getNavigationParams'])
            ->getMock();
        $baseControllerMock->method('getNavigationParams')->willReturn(['empty']);

        /** @var Request|MockObject $requestMock */
        $requestMock = $this->getMockBuilder(Request::class)
            ->onlyMethods(['getRequestParameter'])
            ->getMock();
        $requestMock->method('getRequestParameter')->willReturnMap([
            ['lgn_usr', 'myUserName'],
        ]);
        d3GetOxidDIC()->set('d3ox.webauthn.'.Request::class, $requestMock);

        /** @var User|MockObject $userMock */
        $userMock = $this->getMockBuilder(User::class)
            ->onlyMethods(['d3GetLoginUserId'])
            ->getMock();
        $userMock->method('d3GetLoginUserId')->willReturn('myUserId');
        d3GetOxidDIC()->set('d3ox.webauthn.'.User::class, $userMock);

        /** @var Session|MockObject $sessionMock */
        $sessionMock = $this->getMockBuilder(Session::class)
            ->onlyMethods(['setVariable', 'getVariable'])
            ->getMock();
        $sessionMock->expects($this->exactly($setVariableCount))->method('setVariable');
        $sessionMock->method('getVariable')->with(WebauthnConf::WEBAUTHN_ADMIN_SESSION_LOGINUSER)
            ->willReturn('myUserName');
        d3GetOxidDIC()->set('d3ox.webauthn.'.Session::class, $sessionMock);

        /** @var d3_webauthn_UserComponent|MockObject $sut */
        $sut = $this->getMockBuilder(UserComponent::class)
            ->onlyMethods(['d3CanUseWebauthn', 'd3CallMockableFunction', 'd3HasWebauthnButNotLoggedin', 'getParent'])
            ->getMock();
        $sut->method('d3CanUseWebauthn')->willReturn($canUseWebauthn);
        $sut->method('d3CallMockableFunction')->willReturn('parentReturn');
        $sut->method('d3HasWebauthnButNotLoggedin')->willReturn($loggedin);
        $sut->method('getParent')->willReturn($baseControllerMock);

        $this->callMethod(
            $sut,
            'd3WebauthnLogin'
        );
    }

    /**
     * @return array
     */
    public function canCheckloginDataProvider(): array
    {
        return [
            'can not use webauthn'  => [false, false, 0, false],
            'already logged in'     => [true, false, 0, false],
            'passed'                => [true, true, 4, true],
        ];
    }

    /**
     * @test
     * @param $username
     * @param $userId
     * @param $hasWebauthnLogin
     * @param $usedPassword
     * @param $expected
     * @return void
     * @throws ReflectionException
     * @dataProvider canUseWebauthnDataProvider
     * @covers \D3\Webauthn\Modules\Application\Component\d3_webauthn_UserComponent::d3CanUseWebauthn
     */
    public function canUseWebauthn($username, $userId, $hasWebauthnLogin, $usedPassword, $expected)
    {
        /** @var Session|MockObject $sessionMock */
        $sessionMock = $this->getMockBuilder(Session::class)
            ->onlyMethods(['hasVariable'])
            ->getMock();
        $sessionMock->method('hasVariable')->with(WebauthnConf::WEBAUTHN_SESSION_AUTH)
            ->willReturn($hasWebauthnLogin);
        d3GetOxidDIC()->set('d3ox.webauthn.'.Session::class, $sessionMock);

        /** @var Request|MockObject $requestMock */
        $requestMock = $this->getMockBuilder(Request::class)
            ->onlyMethods(['getRequestParameter'])
            ->getMock();
        $requestMock->method('getRequestParameter')->with('lgn_pwd')->willReturn($usedPassword);
        d3GetOxidDIC()->set('d3ox.webauthn.'.Request::class, $requestMock);

        /** @var d3_webauthn_UserComponent|MockObject $sut */
        $sut = $this->getMockBuilder(UserComponent::class)
            ->getMock();

        $this->assertSame(
            $expected,
            $this->callMethod(
                $sut,
                'd3CanUseWebauthn',
                [$username, $userId]
            )
        );
    }

    /**
     * @return array
     */
    public function canUseWebauthnDataProvider(): array
    {
        return [
            'no username'               => [null, 'myUserId', false, null, false],
            'no userid'                 => ['username', null, false, null, false],
            'existing webauthn login'   => ['username', 'myUserId', true, null, false],
            'used password'             => ['username', 'myUserId', false, 'myPassword', false],
            'passed'                    => ['username', 'myUserId', false, null, true],
        ];
    }

    /**
     * @test
     * @param $webauthnActive
     * @param $hasAuth
     * @param $expected
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Modules\Application\Component\d3_webauthn_UserComponent::d3HasWebauthnButNotLoggedin
     * @dataProvider canHasWebauthnButNotLoggedinDataProvider
     */
    public function canHasWebauthnButNotLoggedin($webauthnActive, $hasAuth, $expected)
    {
        /** @var Session|MockObject $sessionMock */
        $sessionMock = $this->getMockBuilder(Session::class)
            ->onlyMethods(['getVariable'])
            ->getMock();
        $sessionMock->method('getVariable')->with(WebauthnConf::WEBAUTHN_SESSION_AUTH)
            ->willReturn($hasAuth);
        d3GetOxidDIC()->set('d3ox.webauthn.'.Session::class, $sessionMock);

        /** @var Webauthn|MockObject $webauthnMock */
        $webauthnMock = $this->getMockBuilder(Webauthn::class)
            ->onlyMethods(['isActive'])
            ->getMock();
        $webauthnMock->method('isActive')->willReturn($webauthnActive);
        d3GetOxidDIC()->set(Webauthn::class, $webauthnMock);

        /** @var UserComponent|MockObject $sut */
        $sut = $this->getMockBuilder(UserComponent::class)
            ->getMock();

        $this->assertSame(
            $expected,
            $this->callMethod(
                $sut,
                'd3HasWebauthnButNotLoggedin',
                ['userId']
            )
        );
    }

    /**
     * @return array
     */
    public function canHasWebauthnButNotLoggedinDataProvider(): array
    {
        return [
            'webauthn not active'   => [false, false, false],
            'has webauthn auth'     => [true, true, false],
            'passed'                => [true, false, true],
        ];
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Modules\Application\Component\d3_webauthn_UserComponent::d3CancelWebauthnLogin
     */
    public function canCancelWebauthnLogin()
    {
        /** @var UserComponent|MockObject $sut */
        $sut = $this->getMockBuilder(UserComponent::class)
            ->onlyMethods(['d3WebauthnClearSessionVariables'])
            ->getMock();
        $sut->expects($this->once())->method('d3WebauthnClearSessionVariables');

        $this->callMethod(
            $sut,
            'd3CancelWebauthnLogin'
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Modules\Application\Component\d3_webauthn_UserComponent::d3WebauthnClearSessionVariables
     */
    public function canClearSessionVariables()
    {
        /** @var Session|MockObject $sessionMock */
        $sessionMock = $this->getMockBuilder(Session::class)
            ->onlyMethods(['deleteVariable'])
            ->getMock();
        $sessionMock->expects($this->atLeast(4))->method('deleteVariable')->willReturn(true);
        d3GetOxidDIC()->set('d3ox.webauthn.'.Session::class, $sessionMock);

        /** @var UserComponent $sut */
        $sut = oxNew(UserComponent::class);

        $this->callMethod(
            $sut,
            'd3WebauthnClearSessionVariables'
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Modules\Application\Component\d3_webauthn_UserComponent::d3AssertAuthn
     * @dataProvider canAssertAuthnDataProvider
     */
    public function canAssertAuthn($thrownExcecption, $afterLoginInvocationCount, $addErrorInvocationCount)
    {
        /** @var UtilsView|MockObject $utilsViewMock */
        $utilsViewMock = $this->getMockBuilder(UtilsView::class)
            ->onlyMethods(['addErrorToDisplay'])
            ->getMock();
        $utilsViewMock->expects($addErrorInvocationCount)->method('addErrorToDisplay');
        d3GetOxidDIC()->set('d3ox.webauthn.'.UtilsView::class, $utilsViewMock);

        /** @var WebauthnLogin|MockObject $webauthnLoginMock */
        $webauthnLoginMock = $this->getMockBuilder(WebauthnLogin::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['frontendLogin'])
            ->getMock();
        if ($thrownExcecption) {
            $webauthnLoginMock->expects($this->once())->method('frontendLogin')->willThrowException(
                oxNew($thrownExcecption)
            );
        } else {
            $webauthnLoginMock->expects($this->once())->method('frontendLogin');
        }

        /** @var UserComponent|MockObject $sut */
        $sut = $this->getMockBuilder(UserComponent::class)
            ->onlyMethods(['_afterLogin', 'd3GetWebauthnLogin'])
            ->getMock();
        $sut->expects($afterLoginInvocationCount)->method('_afterLogin');
        $sut->method('d3GetWebauthnLogin')->willReturn($webauthnLoginMock);

        $this->callMethod(
            $sut,
            'd3AssertAuthn'
        );
    }

    /**
     * @return array[]
     */
    public function canAssertAuthnDataProvider(): array
    {
        return [
            'passed'                => [null, $this->once(), $this->never()],
            'webauthnException'     => [WebauthnGetException::class, $this->never(), $this->once()],
            'webauthnLoginError'    => [WebauthnLoginErrorException::class, $this->never(), $this->never()],
        ];
    }

    /**
     * @test
     * @throws ReflectionException
     * @covers  \D3\Webauthn\Modules\Application\Component\d3_webauthn_UserComponent::d3GetWebauthnLogin
     */
    public function canGetWebAuthnLogin()
    {
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->getMockBuilder(Request::class)
            ->onlyMethods(['getRequestEscapedParameter'])
            ->getMock();
        $requestMock->method('getRequestEscapedParameter')->willReturn('requestReturn');
        d3GetOxidDIC()->set('d3ox.webauthn.'.Request::class, $requestMock);

        /** @var d3webauthnadminlogin|MockObject $sut */
        $sut = oxNew(UserComponent::class);

        $this->assertInstanceOf(
            WebauthnLogin::class,
            $this->callMethod(
                $sut,
                'd3GetWebauthnLogin'
            )
        );
    }
}
