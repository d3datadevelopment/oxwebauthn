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

namespace D3\Webauthn\tests\unit\Modules\Application\Controller\Admin;

use D3\TestingTools\Development\CanAccessRestricted;
use D3\Webauthn\Application\Model\Webauthn;
use D3\Webauthn\Application\Model\WebauthnConf;
use OxidEsales\Eshop\Application\Controller\Admin\LoginController;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\TestingLibrary\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;

class LoginControllerWebauthnTest extends UnitTestCase
{
    use CanAccessRestricted;

    /**
     * @test
     * @covers \D3\Webauthn\Modules\Application\Controller\Admin\d3_LoginController_Webauthn::d3GetWebauthnObject
     * @throws ReflectionException
     */
    public function canGetWebauthnObject()
    {
        $sut = oxNew(LoginController::class);

        $this->assertInstanceOf(
            Webauthn::class,
            $this->callMethod(
                $sut,
                'd3GetWebauthnObject'
            )
        );
    }

    /**
     * @test
     * @throws ReflectionException
     * @covers \D3\Webauthn\Modules\Application\Controller\Admin\d3_LoginController_Webauthn::d3WebauthnCancelLogin
     */
    public function canCancelLogin()
    {
        /** @var User|MockObject $userMock */
        $userMock = $this->getMockBuilder(User::class)
            ->onlyMethods(['logout'])
            ->getMock();
        $userMock->expects($this->atLeastOnce())->method('logout');

        /** @var LoginController|MockObject $sut */
        $sut = $this->getMockBuilder(LoginController::class)
            ->onlyMethods(['d3WebauthnGetUserObject'])
            ->getMock();
        $sut->method('d3WebauthnGetUserObject')->willReturn($userMock);

        $this->callMethod(
            $sut,
            'd3WebauthnCancelLogin'
        );
    }

    /**
     * @test
     * @throws ReflectionException
     * @covers \D3\Webauthn\Modules\Application\Controller\Admin\d3_LoginController_Webauthn::d3WebauthnGetUserObject
     */
    public function canGetUserObject()
    {
        /** @var LoginController $sut */
        $sut = oxNew(LoginController::class);

        $this->assertInstanceOf(
            User::class,
            $this->callMethod(
                $sut,
                'd3WebauthnGetUserObject'
            )
        );
    }

    /**
     * @test
     * @throws ReflectionException
     * @covers \D3\Webauthn\Modules\Application\Controller\Admin\d3_LoginController_Webauthn::d3WebauthnGetRequestObject
     */
    public function canGetRequestObject()
    {
        /** @var LoginController $sut */
        $sut = oxNew(LoginController::class);

        $this->assertInstanceOf(
            Request::class,
            $this->callMethod(
                $sut,
                'd3WebauthnGetRequestObject'
            )
        );
    }

    /**
     * @test
     * @throws ReflectionException
     * @covers \D3\Webauthn\Modules\Application\Controller\Admin\d3_LoginController_Webauthn::d3WebauthnGetSessionObject
     */
    public function canGetSessionObject()
    {
        /** @var LoginController $sut */
        $sut = oxNew(LoginController::class);

        $this->assertInstanceOf(
            Session::class,
            $this->callMethod(
                $sut,
                'd3WebauthnGetSessionObject'
            )
        );
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
     * @covers \D3\Webauthn\Modules\Application\Controller\Admin\d3_LoginController_Webauthn::d3CanUseWebauthn
     */
    public function canUseWebauthn($username, $userId, $hasWebauthnLogin, $usedPassword, $expected)
    {
        /** @var Session|MockObject $sessionMock */
        $sessionMock = $this->getMockBuilder(Session::class)
            ->onlyMethods(['hasVariable'])
            ->getMock();
        $sessionMock->method('hasVariable')->with(WebauthnConf::WEBAUTHN_ADMIN_SESSION_AUTH)
            ->willReturn($hasWebauthnLogin);

        /** @var Request|MockObject $requestMock */
        $requestMock = $this->getMockBuilder(Request::class)
            ->onlyMethods(['getRequestParameter'])
            ->getMock();
        $requestMock->method('getRequestParameter')->with('pwd')->willReturn($usedPassword);

        /** @var LoginController|MockObject $sut */
        $sut = $this->getMockBuilder(LoginController::class)
            ->onlyMethods(['d3WebauthnGetRequestObject', 'd3WebauthnGetSessionObject'])
            ->getMock();
        $sut->method('d3WebauthnGetRequestObject')->willReturn($requestMock);
        $sut->method('d3WebauthnGetSessionObject')->willReturn($sessionMock);

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
     * @covers \D3\Webauthn\Modules\Application\Controller\Admin\d3_LoginController_Webauthn::hasWebauthnButNotLoggedin
     * @dataProvider canHasWebauthnButNotLoggedinDataProvider
     */
    public function canHasWebauthnButNotLoggedin($webauthnActive, $hasAuth, $expected)
    {
        /** @var Session|MockObject $sessionMock */
        $sessionMock = $this->getMockBuilder(Session::class)
            ->onlyMethods(['getVariable'])
            ->getMock();
        $sessionMock->method('getVariable')->with(WebauthnConf::WEBAUTHN_ADMIN_SESSION_AUTH)
            ->willReturn($hasAuth);

        /** @var Webauthn|MockObject $webauthnMock */
        $webauthnMock = $this->getMockBuilder(Webauthn::class)
            ->onlyMethods(['isActive'])
            ->getMock();
        $webauthnMock->method('isActive')->willReturn($webauthnActive);

        /** @var LoginController|MockObject $sut */
        $sut = $this->getMockBuilder(LoginController::class)
            ->onlyMethods(['d3GetWebauthnObject', 'd3WebauthnGetSessionObject'])
            ->getMock();
        $sut->method('d3GetWebauthnObject')->willReturn($webauthnMock);
        $sut->method('d3WebauthnGetSessionObject')->willReturn($sessionMock);

        $this->assertSame(
            $expected,
            $this->callMethod(
                $sut,
                'hasWebauthnButNotLoggedin',
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
     * @param $canUseWebauthn
     * @param $loggedin
     * @param $setVariableCount
     * @param $expected
     * @return void
     * @throws ReflectionException
     * @dataProvider canCheckloginDataProvider
     * @covers \D3\Webauthn\Modules\Application\Controller\Admin\d3_LoginController_Webauthn::checklogin
     */
    public function canChecklogin($canUseWebauthn, $loggedin, $setVariableCount, $expected)
    {
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->getMockBuilder(Request::class)
            ->onlyMethods(['getRequestParameter'])
            ->getMock();
        $requestMock->method('getRequestParameter')->willReturnMap([
            ['user', 'myUserName'],
            ['profile', 'myProfile']
        ]);

        /** @var User|MockObject $userMock */
        $userMock = $this->getMockBuilder(User::class)
            ->onlyMethods(['d3GetLoginUserId'])
            ->getMock();
        $userMock->method('d3GetLoginUserId')->willReturn('myUserId');

        /** @var Session|MockObject $sessionMock */
        $sessionMock = $this->getMockBuilder(Session::class)
            ->onlyMethods(['setVariable', 'getVariable'])
            ->getMock();
        $sessionMock->expects($this->exactly($setVariableCount))->method('setVariable');
        $sessionMock->method('getVariable')->with(WebauthnConf::WEBAUTHN_ADMIN_SESSION_LOGINUSER)
            ->willReturn('myUserName');

        /** @var LoginController|MockObject $sut */
        $sut = $this->getMockBuilder(LoginController::class)
            ->onlyMethods(['d3CanUseWebauthn', 'd3CallMockableParent', 'hasWebauthnButNotLoggedin',
                'd3WebauthnGetSessionObject', 'd3WebauthnGetUserObject', 'd3WebauthnGetRequestObject'
            ])
            ->getMock();
        $sut->method('d3CanUseWebauthn')->willReturn($canUseWebauthn);
        $sut->method('d3CallMockableParent')->willReturn('parentReturn');
        $sut->method('hasWebauthnButNotLoggedin')->willReturn($loggedin);
        $sut->method('d3WebauthnGetSessionObject')->willReturn($sessionMock);
        $sut->method('d3WebauthnGetUserObject')->willReturn($userMock);
        $sut->method('d3WebauthnGetRequestObject')->willReturn($requestMock);

        $this->assertSame(
            $expected,
            $this->callMethod(
                $sut,
                'checklogin'
            )
        );
    }

    /**
     * @return array
     */
    public function canCheckloginDataProvider(): array
    {
        return [
            'can not use webauthn'  => [false, false, 0, 'parentReturn'],
            'already logged in'     => [true, false, 2, 'parentReturn'],
            'passed'                => [true, true, 5, 'd3webauthnadminlogin'],
        ];
    }
}