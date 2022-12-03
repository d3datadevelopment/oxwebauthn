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

namespace D3\Webauthn\tests\unit\Application\Controller;

use D3\TestingTools\Development\CanAccessRestricted;
use D3\Webauthn\Application\Controller\d3webauthnlogin;
use D3\Webauthn\Application\Model\Exceptions\WebauthnException;
use D3\Webauthn\Application\Model\Webauthn;
use D3\Webauthn\Application\Model\WebauthnConf;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\Eshop\Core\Utils;
use OxidEsales\TestingLibrary\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use ReflectionException;

class d3webauthnloginTest extends UnitTestCase
{
    use CanAccessRestricted;

    protected $sutClassName = d3webauthnlogin::class;

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\d3webauthnlogin::getNavigationParams
     */
    public function canGetNavigationParams()
    {
        /** @var Session|MockObject $sessionMock */
        $sessionMock = $this->getMockBuilder(Session::class)
            ->onlyMethods(['getVariable'])
            ->getMock();
        $sessionMock->method('getVariable')->willReturn([
            'key1' => 'variable1'
        ]);

        /** @var d3webauthnlogin|MockObject $sut */
        $sut = $this->getMockBuilder($this->sutClassName)
            ->onlyMethods(['d3GetMockableRegistryObject', 'd3CallMockableParent'])
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
        $sut->method('d3CallMockableParent')->willReturn(['defKey1' => 'devValues1']);

        $this->assertSame(
            [
                'defKey1' => 'devValues1',
                'key1' => 'variable1',
                'cl' => NULL,
            ],
            $this->callMethod(
                $sut,
                'getNavigationParams'
            )
        );
    }

    /**
     * @test
     * @param $auth
     * @param $userFromLogin
     * @param $startRedirect
     * @param $redirectController
     * @return void
     * @throws ReflectionException
     * @covers       \D3\Webauthn\Application\Controller\d3webauthnlogin::render
     * @dataProvider canRenderDataProvider
     */
    public function canRender($auth, $userFromLogin, $startRedirect, $redirectController)
    {
        /** @var Session|MockObject $sessionMock */
        $sessionMock = $this->getMockBuilder(Session::class)
            ->onlyMethods(['hasVariable'])
            ->getMock();
        $sessionMock->method('hasVariable')->willReturnMap([
            [WebauthnConf::WEBAUTHN_SESSION_AUTH, $auth],
            [WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER, $userFromLogin]
        ]);

        /** @var Utils|MockObject $utilsMock */
        $utilsMock = $this->getMockBuilder(Utils::class)
            ->onlyMethods(['redirect'])
            ->getMock();
        $utilsMock->expects($startRedirect ? $this->once() : $this->never())
            ->method('redirect')->with('index.php?cl='.$redirectController)->willReturn(true);

        /** @var d3webauthnlogin|MockObject $sut */
        $sut = $this->getMockBuilder($this->sutClassName)
            ->onlyMethods(['d3GetMockableRegistryObject', 'd3CallMockableParent',
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
        $sut->method('d3CallMockableParent')->willReturn('myTemplate.tpl');
        $sut->expects($startRedirect ? $this->any() : $this->atLeastOnce())
            ->method('generateCredentialRequest');
        $sut->expects($startRedirect ? $this->any() : $this->atLeastOnce())
            ->method('addTplParam')->with('navFormParams')->willReturn(true);

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
            'has auth'   => [true, true, true, 'start'],
            'missing user'   => [false, false, true, 'start'],
        ];
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\d3webauthnlogin::generateCredentialRequest
     */
    public function canGenerateCredentialRequest($userSessionVarName = WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER)
    {
        $currUserFixture = 'currentUserFixture';

        /** @var LoggerInterface|MockObject $loggerMock */
        $loggerMock = $this->getMockForAbstractClass(LoggerInterface::class, [], '', true, true, true, ['error', 'debug']);
        $loggerMock->expects($this->never())->method('error')->willReturn(true);
        $loggerMock->expects($this->never())->method('debug')->willReturn(true);

        /** @var Session|MockObject $sessionMock */
        $sessionMock = $this->getMockBuilder(Session::class)
            ->onlyMethods(['getVariable'])
            ->getMock();
        $sessionMock->method('getVariable')->willReturnMap([
            [$userSessionVarName, $currUserFixture]
        ]);

        /** @var Webauthn|MockObject $webAuthnMock */
        $webAuthnMock = $this->getMockBuilder(Webauthn::class)
            ->onlyMethods(['getRequestOptions'])
            ->getMock();
        $webAuthnMock->expects($this->once())->method('getRequestOptions')->with($currUserFixture)
            ->willReturn('success');

        /** @var d3webauthnlogin|MockObject $sut */
        $sut = $this->getMockBuilder($this->sutClassName)
            ->onlyMethods(['d3GetMockableRegistryObject', 'd3GetMockableOxNewObject', 'addTplParam', 'd3GetMockableLogger'])
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
        $sut->method('d3GetMockableOxNewObject')->willReturnCallback(
            function () use ($webAuthnMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case Webauthn::class:
                        return $webAuthnMock;
                    default:
                        return call_user_func_array("oxNew", $args);
                }
            }
        );
        $sut->expects($this->atLeast(2))
            ->method('addTplParam')->willReturn(true);
        $sut->method('d3GetMockableLogger')->willReturn($loggerMock);

        $this->callMethod(
            $sut,
            'generateCredentialRequest'
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\d3webauthnlogin::generateCredentialRequest
     */
    public function generateCredentialRequestFailed($redirectClass = 'start', $userVarName = WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER)
    {
        $currUserFixture = 'currentUserFixture';

        /** @var LoggerInterface|MockObject $loggerMock */
        $loggerMock = $this->getMockForAbstractClass(LoggerInterface::class, [], '', true, true, true, ['error', 'debug']);
        $loggerMock->expects($this->atLeastOnce())->method('error')->willReturn(true);
        $loggerMock->expects($this->atLeastOnce())->method('debug')->willReturn(true);

        /** @var Session|MockObject $sessionMock */
        $sessionMock = $this->getMockBuilder(Session::class)
            ->onlyMethods(['getVariable', 'setVariable'])
            ->getMock();
        $sessionMock->method('getVariable')->willReturnMap([
            [$userVarName, $currUserFixture]
        ]);
        $sessionMock->expects($this->once())->method('setVariable')->with(WebauthnConf::GLOBAL_SWITCH)
            ->willReturn(true);

        /** @var Webauthn|MockObject $webAuthnMock */
        $webAuthnMock = $this->getMockBuilder(Webauthn::class)
            ->onlyMethods(['getRequestOptions'])
            ->getMock();
        $webAuthnMock->expects($this->once())->method('getRequestOptions')->with($currUserFixture)
            ->willThrowException(oxNew(WebauthnException::class, 'foobar0'));

        /** @var Utils|MockObject $utilsMock */
        $utilsMock = $this->getMockBuilder(Utils::class)
            ->onlyMethods(['redirect'])
            ->getMock();
        $utilsMock->expects($this->once())->method('redirect')
            ->with('index.php?cl='.$redirectClass)->willReturn(true);

        /** @var d3webauthnlogin|MockObject $sut */
        $sut = $this->getMockBuilder($this->sutClassName)
            ->onlyMethods(['d3GetMockableOxNewObject', 'addTplParam',
                'd3GetMockableLogger', 'd3GetMockableRegistryObject'])
            ->getMock();
        $sut->method('d3GetMockableOxNewObject')->willReturnCallback(
            function () use ($webAuthnMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case Webauthn::class:
                        return $webAuthnMock;
                    default:
                        return call_user_func_array("oxNew", $args);
                }
            }
        );
        $sut->expects($this->never())
            ->method('addTplParam')->willReturn(true);
        $sut->expects($this->atLeast(2))->method('d3GetMockableLogger')->willReturn($loggerMock);
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

        $this->callMethod(
            $sut,
            'generateCredentialRequest'
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\d3webauthnlogin::d3GetPreviousClass
     */
    public function canGetPreviousClass($sessionVarName = WebauthnConf::WEBAUTHN_SESSION_CURRENTCLASS)
    {
        $currClassFixture = 'currentClassFixture';

        /** @var Session|MockObject $sessionMock */
        $sessionMock = $this->getMockBuilder(Session::class)
            ->onlyMethods(['getVariable'])
            ->getMock();
        $sessionMock->method('getVariable')->willReturnMap([
            [$sessionVarName, $currClassFixture]
        ]);

        /** @var d3webauthnlogin|MockObject $sut */
        $sut = $this->getMockBuilder($this->sutClassName)
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

        $this->assertSame(
            $currClassFixture,
            $this->callMethod(
                $sut,
                'd3GetPreviousClass'
            )
        );
    }

    /**
     * @test
     * @param $currClass
     * @param $isOrderStep
     * @return void
     * @throws ReflectionException
     * @covers       \D3\Webauthn\Application\Controller\d3webauthnlogin::previousClassIsOrderStep
     * @dataProvider canPreviousClassIsOrderStepDataProvider
     */
    public function canPreviousClassIsOrderStep($currClass, $isOrderStep)
    {
        /** @var d3webauthnlogin|MockObject $sut */
        $sut = $this->getMockBuilder($this->sutClassName)
            ->onlyMethods(['d3GetPreviousClass'])
            ->getMock();
        $sut->method('d3GetPreviousClass')->willReturn($currClass);

        $this->assertSame(
            $isOrderStep,
            $this->callMethod(
                $sut,
                'previousClassIsOrderStep'
            )
        );
    }

    /**
     * @return array[]
     */
    public function canPreviousClassIsOrderStepDataProvider(): array
    {
        return [
            'checkout class'    => ['payment', true],
            'no checkout class'    => ['details', false],
            'unknown class'    => ['unknown', false],
        ];
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\d3webauthnlogin::getIsOrderStep
     * @dataProvider canGetIsOrderStepDataProvider
     */
    public function canGetIsOrderStep($boolean)
    {
        /** @var d3webauthnlogin|MockObject $sut */
        $sut = $this->getMockBuilder($this->sutClassName)
            ->onlyMethods(['previousClassIsOrderStep'])
            ->getMock();
        $sut->expects($this->atLeastOnce())->method('previousClassIsOrderStep')->willReturn($boolean);

        $this->assertSame(
            $boolean,
            $this->callMethod(
                $sut,
                'getIsOrderStep'
            )
        );
    }

    /**
     * @return array
     */
    public function canGetIsOrderStepDataProvider(): array
    {
        return [
            [true],
            [false]
        ];
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\d3webauthnlogin::getBreadCrumb
     */
    public function canGetBreadCrumb()
    {
        $sut = oxNew($this->sutClassName);

        $this->assertIsArray(
            $this->callMethod(
                $sut,
                'getBreadCrumb'
            )
        );
    }
}