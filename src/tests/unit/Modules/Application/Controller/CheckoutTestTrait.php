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

namespace D3\Webauthn\tests\unit\Modules\Application\Controller;

use D3\TestingTools\Development\CanAccessRestricted;
use D3\Webauthn\Application\Model\Webauthn;
use D3\Webauthn\Application\Model\WebauthnConf;
use OxidEsales\Eshop\Application\Controller\OrderController;
use OxidEsales\Eshop\Application\Controller\PaymentController;
use OxidEsales\Eshop\Application\Controller\UserController;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Session;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;

trait CheckoutTestTrait
{
    use CanAccessRestricted;

    /**
     * @test
     * @param $hasUser
     * @param $userId
     * @param $isActive
     * @param $sessionAuth
     * @param $expected
     * @return void
     * @throws ReflectionException
     * @dataProvider canGetUserDataProvider
     * @covers \D3\Webauthn\Application\Controller\Traits\checkoutGetUserTrait::getUser
     * @covers \D3\Webauthn\Modules\Application\Controller\d3_webauthn_PaymentController::getUser
     * @covers \D3\Webauthn\Modules\Application\Controller\d3_webauthn_OrderController::getUser
     * @covers \D3\Webauthn\Modules\Application\Controller\d3_webauthn_UserController::getUser
     */
    public function canGetUser($hasUser, $userId, $isActive, $sessionAuth, $expected)
    {
        if ($hasUser) {
            /** @var User|MockObject $userMock */
            $userMock = $this->getMockBuilder(User::class)
                ->onlyMethods(['getId'])
                ->getMock();
            $userMock->method('getId')->willReturn($userId);
        } else {
            $userMock = false;
        }

        /** @var Session|MockObject $sessionMock */
        $sessionMock = $this->getMockBuilder(Session::class)
            ->onlyMethods(['getVariable'])
            ->getMock();
        $sessionMock->method('getVariable')
            ->with($this->identicalTo(WebauthnConf::WEBAUTHN_SESSION_AUTH))->willReturn($sessionAuth);

        /** @var Webauthn|MockObject $webauthnMock */
        $webauthnMock = $this->getMockBuilder(Webauthn::class)
            ->onlyMethods(['isActive'])
            ->getMock();
        $webauthnMock->method('isActive')->willReturn($isActive);

        /** @var PaymentController|OrderController|UserController|MockObject $sut */
        $sut = $this->getMockBuilder($this->sutClass)
            ->onlyMethods(['d3GetMockableOxNewObject', 'd3GetMockableRegistryObject', 'd3CallMockableParent'])
            ->getMock();
        $sut->method('d3GetMockableOxNewObject')->willReturnCallback(
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
        $sut->method('d3CallMockableParent')->willReturn($userMock);

        $return = $this->callMethod(
            $sut,
            'getUser'
        );

        if ($expected === 'parent') {
            $this->assertSame($return, $userMock);
        } else {
            $this->assertSame($return, $expected);
        }
    }

    /**
     * @return array
     */
    public function canGetUserDataProvider(): array
    {
        return [
            'no user'               => [false, null, false, null, 'parent'],
            'no user id'            => [true, 'null', false, null, 'parent'],
            'webauthn not active'   => [true, 'userIdFixture', false, null, 'parent'],
            'has webauthn auth'     => [true, 'userIdFixture', true, 'userIdFixture', 'parent'],
            'no webauthn auth'      => [true, 'userIdFixture', true, null, false],
        ];
    }
}