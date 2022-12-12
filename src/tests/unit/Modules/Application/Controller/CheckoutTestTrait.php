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

    protected $userFixtureId = 'userIdFixture1';

    /** @var User */
    protected $userFixture;

    public function setUp(): void
    {
        $this->userFixture = oxNew(User::class);
        $this->userFixture->setId($this->userFixtureId);
        $this->userFixture->assign(['oxlname'    => __METHOD__]);
        $this->userFixture->save();
        $this->userFixture->load($this->userFixtureId);
    }

    public function tearDown(): void
    {
        $this->userFixture->delete($this->userFixtureId);
    }

    /**
     * @test
     * @param $hasUser
     * @param $isActive
     * @param $sessionAuth
     * @param $expected
     * @return void
     * @throws ReflectionException
     * @dataProvider canGetUserDataProvider
     * @covers       \D3\Webauthn\Application\Controller\Traits\checkoutGetUserTrait::getUser
     * @covers       \D3\Webauthn\Modules\Application\Controller\d3_webauthn_PaymentController::getUser
     * @covers       \D3\Webauthn\Modules\Application\Controller\d3_webauthn_OrderController::getUser
     * @covers       \D3\Webauthn\Modules\Application\Controller\d3_webauthn_UserController::getUser
     */
    public function canGetUser($hasUser, $isActive, $sessionAuth, $expected)
    {
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
            ->onlyMethods(['d3GetMockableOxNewObject', 'd3GetMockableRegistryObject'])
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
        if ($hasUser) {
            $sut->setUser($this->userFixture);
        }

        $return = $this->callMethod(
            $sut,
            'getUser'
        );

        $sut->setUser(oxNew(User::class));

        if ($expected === 'parent') {
            $this->assertSame( $return, $hasUser ? $this->userFixture : false);
        } else {
            $this->assertSame($return, $expected);
        }

        // reset cache
        $this->setValue(
            $sut,
            '_oActUser',
            null
        );
    }

    /**
     * @return array
     */
    public function canGetUserDataProvider(): array
    {
        return [
            'no (valid) user'       => [false, false, null, 'parent'],
            'webauthn not active'   => [true, false, null, 'parent'],
            'has webauthn auth'     => [true, true, 'userIdFixture', 'parent'],
            'no webauthn auth'      => [true, true, null, false],
        ];
    }
}