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
use OxidEsales\Eshop\Application\Controller\Admin\LoginController;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Request;
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
}