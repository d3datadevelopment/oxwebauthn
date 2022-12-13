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

namespace D3\Webauthn\tests\unit\Modules\Application\Model;

use D3\TestingTools\Development\CanAccessRestricted;
use D3\Webauthn\Application\Model\WebauthnConf;
use D3\Webauthn\Modules\Application\Model\d3_User_Webauthn;
use D3\Webauthn\Modules\Application\Model\d3_User_Webauthn_parent;
use Exception;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Exception\UserException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\TestingLibrary\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;

class UserWebauthnTest extends UnitTestCase
{
    use CanAccessRestricted;

    protected $userId = 'userIdFixture';

    public function setUp(): void
    {
        parent::setUp();

        /** @var d3_User_Webauthn $user */
        $user = oxNew(User::class);
        $user->setId($this->userId);
        $user->assign([
            'oxusername'    => 'userNameFixture',
            'oxshopid'      => '15',
            'oxrights'      => 'user',
        ]);
        $user->save();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        try {
            /** @var d3_User_Webauthn $user */
            $user = oxNew(User::class);
            $user->delete($this->userId);
        } catch (Exception $e) {
        }
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Modules\Application\Model\d3_User_Webauthn::logout
     */
    public function canLogout()
    {
        /** @var User|MockObject $sut */
        $sut = $this->getMockBuilder(User::class)
            ->onlyMethods(['d3CallMockableFunction', 'd3WebauthnLogout'])
            ->getMock();
        $sut->expects($this->once())->method('d3CallMockableFunction')->willReturn(true);
        $sut->expects($this->once())->method('d3WebauthnLogout');

        $this->callMethod(
            $sut,
            'logout'
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Modules\Application\Model\d3_User_Webauthn::d3WebauthnLogout
     */
    public function canWebauthnLogout()
    {
        /** @var Session|MockObject $sessionMock */
        $sessionMock = $this->getMockBuilder(Session::class)
            ->onlyMethods(['deleteVariable'])
            ->getMock();
        $sessionMock->expects($this->atLeast(11))->method('deleteVariable')->willReturn(true);

        /** @var User|MockObject $sut */
        $sut = $this->getMockBuilder(User::class)
            ->onlyMethods(['d3GetMockableRegistryObject'])
            ->getMock();
        $sut->method('d3GetMockableRegistryObject')->willReturnCallback(
            function () use ($sessionMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case Session::class:
                        return $sessionMock;
                    default:
                        return Registry::get($args[0]);
                }
            }
        );

        $this->callMethod(
            $sut,
            'd3WebauthnLogout'
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Modules\Application\Model\d3_User_Webauthn::login
     */
    public function canLogin()
    {
        /** @var User|MockObject $sut */
        $sut = $this->getMockBuilder(User::class)
            ->onlyMethods(['d3CallMockableFunction', 'd3WebauthnLogin'])
            ->getMock();
        $sut->expects($this->once())->method('d3CallMockableFunction')->with($this->identicalTo(
            [d3_User_Webauthn_parent::class, 'login']
        ))->willReturn(true);
        $sut->expects($this->once())->method('d3WebauthnLogin')->willReturn(true);

        $this->callMethod(
            $sut,
            'login',
            ['myUserName', 'myPassword']
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Modules\Application\Model\d3_User_Webauthn::d3WebauthnLogin
     * @dataProvider canWebauthnLoginDataProvider
     */
    public function canWebauthnLogin($authInSession, $userNameArgument, $userNameInSession, $canLoad, $userIsLoadable, $expected)
    {
        /** @var Config|MockObject $configMock */
        $configMock = $this->getMockBuilder(Config::class)
            ->onlyMethods(['getShopId'])
            ->getMock();
        $configMock->method('getShopId')->willReturn(1);

        /** @var Session|MockObject $sessionMock */
        $sessionMock = $this->getMockBuilder(Session::class)
            ->onlyMethods(['getVariable'])
            ->getMock();
        $sessionMock->method('getVariable')->willReturnMap([
            [WebauthnConf::WEBAUTHN_SESSION_AUTH, $authInSession],
            [WebauthnConf::WEBAUTHN_SESSION_LOGINUSER, $userNameInSession],
        ]);

        /** @var User|MockObject $sut */
        $sut = $this->getMockBuilder(User::class)
            ->onlyMethods(['d3GetMockableRegistryObject', 'load'])
            ->getMock();
        $sut->method('d3GetMockableRegistryObject')->willReturnCallback(
            function () use ($sessionMock, $configMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case Session::class:
                        return $sessionMock;
                    case Config::class:
                        return $configMock;
                    default:
                        return Registry::get($args[0]);
                }
            }
        );
        $sut->expects($this->exactly((int) ($canLoad)))->method('load')->will(
            $userIsLoadable ?
                $this->returnValue(true) :
                $this->throwException(oxNew(UserException::class))
        );

        if (!$userIsLoadable) {
            $this->expectException(UserException::class);
        }

        $this->assertSame(
            $expected,
            $this->callMethod(
                $sut,
                'd3WebauthnLogin',
                [$userNameArgument]
            )
        );
    }

    /**
     * @return array[]
     */
    public function canWebauthnLoginDataProvider(): array
    {
        return [
            'has no session auth'       => [null, 'userArgument', null, false, true, 'userArgument'],
            'different username'        => ['sessionAuth', 'userArgument', 'sessionArgument', false, true, 'userArgument'],
            'identical username'        => ['sessionAuth', 'myUserName', 'myUserName', true, true, 'myUserName'],
            'user not loadable'         => ['sessionAuth', 'myUserName', 'myUserName', true, false, 'userSession'],
        ];
    }

    /**
     * @test
     * @param $userName
     * @param $shopId
     * @param $rights
     * @param $expected
     * @return void
     * @throws ReflectionException
     * @dataProvider canGetLoginUserIdDataProvider
     * @covers \D3\Webauthn\Modules\Application\Model\d3_User_Webauthn::d3GetLoginUserId
     */
    public function canGetLoginUserId($userName, $shopId, $rights, $expected)
    {
        /** @var Config|MockObject $configMock */
        $configMock = $this->getMockBuilder(Config::class)
            ->onlyMethods(['getShopId'])
            ->getMock();
        $configMock->method('getShopId')->willReturn($shopId);

        /** @var User|MockObject $sut */
        $sut = $this->getMockBuilder(User::class)
            ->onlyMethods(['d3GetMockableRegistryObject'])
            ->getMock();
        $sut->method('d3GetMockableRegistryObject')->willReturnCallback(
            function () use ($configMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case Config::class:
                        return $configMock;
                    default:
                        return Registry::get($args[0]);
                }
            }
        );

        $this->assertSame(
            $expected,
            $this->callMethod(
                $sut,
                'd3GetLoginUserId',
                [$userName, $rights]
            )
        );
    }

    /**
     * @return array[]
     */
    public function canGetLoginUserIdDataProvider(): array
    {
        return [
            'username not set'  => [null, '15', 'user', null],
            'user is loadable'  => ['userNameFixture', '15', 'user', $this->userId],
            'wrong shop id'     => ['userNameFixture', '13', 'user', null],
            'wrong rights'      => ['userNameFixture', '15', 'foobar', null],
            'no rights check'   => ['userNameFixture', '15', null, $this->userId],
            'user not loadable' => ['unknown', '15', '20', null],
        ];
    }
}
