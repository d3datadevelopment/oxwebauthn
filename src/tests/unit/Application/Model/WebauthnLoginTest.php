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

namespace D3\Webauthn\tests\unit\Application\Model;

use D3\TestingTools\Development\CanAccessRestricted;
use D3\Webauthn\Application\Model\Exceptions\WebauthnException;
use D3\Webauthn\Application\Model\Exceptions\WebauthnGetException;
use D3\Webauthn\Application\Model\Exceptions\WebauthnLoginErrorException;
use D3\Webauthn\Application\Model\Webauthn;
use D3\Webauthn\Application\Model\WebauthnConf;
use D3\Webauthn\Application\Model\WebauthnLogin;
use OxidEsales\Eshop\Application\Component\UserComponent;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Exception\CookieException;
use OxidEsales\Eshop\Core\Exception\UserException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\Eshop\Core\SystemEventHandler;
use OxidEsales\Eshop\Core\Utils;
use OxidEsales\Eshop\Core\UtilsServer;
use OxidEsales\Eshop\Core\UtilsView;
use OxidEsales\TestingLibrary\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use ReflectionException;
use TypeError;

class WebauthnLoginTest extends UnitTestCase
{
    use CanAccessRestricted;

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Model\WebauthnLogin::__construct
     */
    public function canConstruct()
    {
        $credFixture = 'credentialFixture';
        $errorFixture = 'errorFixture';

        /** @var WebauthnLogin|MockObject $sut */
        $sut = $this->getMockBuilder(WebauthnLogin::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setCredential', 'setErrorMsg'])
            ->getMock();
        $sut->expects($this->atLeastOnce())->method('setCredential')->with($credFixture);
        $sut->expects($this->atLeastOnce())->method('setErrorMsg')->with($errorFixture);

        $this->callMethod(
            $sut,
            '__construct',
            [$credFixture, $errorFixture]
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Model\WebauthnLogin::setCredential
     * @covers \D3\Webauthn\Application\Model\WebauthnLogin::getCredential
     */
    public function canSetAndGetExistingCredentials()
    {
        $credFixture = 'credentialFixture';

        /** @var WebauthnLogin $sut */
        $sut = oxNew(WebauthnLogin::class, 'cred', 'err');

        $this->callMethod(
            $sut,
            'setCredential',
            [$credFixture]
        );

        $this->assertSame(
            $credFixture,
            $this->callMethod(
                $sut,
                'getCredential'
            )
        );
    }

    /**
     * @test
     * @param $credFixture
     * @param $setException
     * @param $getExpection
     * @return void
     * @throws ReflectionException
     * @dataProvider credentialErrorDataProvider
     * @covers \D3\Webauthn\Application\Model\WebauthnLogin::setCredential
     */
    public function cannotSetErrorCredentials($credFixture, $setException, $getExpection)
    {
        unset($getExpection);

        /** @var WebauthnLogin $sut */
        $sut = oxNew(WebauthnLogin::class, 'cred', 'err');

        $this->expectException($setException);

        $this->callMethod(
            $sut,
            'setCredential',
            [$credFixture]
        );
    }

    /**
     * @test
     * @param $credFixture
     * @param $setException
     * @param $getExpection
     * @return void
     * @throws ReflectionException
     * @dataProvider credentialErrorDataProvider
     * @covers \D3\Webauthn\Application\Model\WebauthnLogin::getCredential
     */
    public function cannotGetErrorCredentials($credFixture, $setException, $getExpection)
    {
        unset($setException);

        /** @var WebauthnLogin $sut */
        $sut = oxNew(WebauthnLogin::class, 'cred', 'err');

        $this->setValue(
            $sut,
            'credential',
            $credFixture
        );

        $this->expectException($getExpection);

        $this->callMethod(
            $sut,
            'getCredential'
        );
    }

    /**
     * @return array[]
     */
    public function credentialErrorDataProvider(): array
    {
        return [
            'empty credential'      => ['', WebauthnGetException::class, WebauthnGetException::class],
            'spaced credential'     => ['   ', WebauthnGetException::class, WebauthnGetException::class],
            'null credential'       => [null, TypeError::class, WebauthnGetException::class],
        ];
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Model\WebauthnLogin::setErrorMsg
     * @covers \D3\Webauthn\Application\Model\WebauthnLogin::getErrorMsg
     */
    public function canAndGetErrorMessage()
    {
        $errorFixture = 'errorFixture';

        /** @var WebauthnLogin $sut */
        $sut = oxNew(WebauthnLogin::class, 'cred', 'err');

        $this->callMethod(
            $sut,
            'setErrorMsg',
            [$errorFixture]
        );

        $this->assertSame(
            $errorFixture,
            $this->callMethod(
                $sut,
                'getErrorMsg'
            )
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Model\WebauthnLogin::frontendLogin
     * @dataProvider frontendLoginSuccessDataProvider
     */
    public function frontendLoginSuccess($setCookie)
    {
        /** @var UserComponent|MockObject $userComponentMock */
        $userComponentMock = $this->getMockBuilder(UserComponent::class)
            ->onlyMethods(['setUser'])
            ->getMock();
        $userComponentMock->expects($this->atLeast(2))->method('setUser');

        /** @var WebauthnLogin|MockObject $sut */
        $sut = $this->getMockBuilder(WebauthnLogin::class)
            ->onlyMethods(['getUserId', 'handleErrorMessage', 'assertUser', 'assertAuthn',
                'setFrontendSession', 'handleBackendCookie', 'handleBackendSubshopRights', 'setSessionCookie',
                'd3GetMockableOxNewObject', 'getCredential', 'regenerateSessionId', ])
            ->disableOriginalConstructor()
            ->getMock();
        $sut->expects($this->exactly((int) $setCookie))->method('setSessionCookie');
        $sut->expects($this->once())->method('setFrontendSession');

        $this->assertEmpty(
            $this->callMethod(
                $sut,
                'frontendLogin',
                [$userComponentMock, $setCookie]
            )
        );
    }

    /**
     * @return array
     */
    public function frontendLoginSuccessDataProvider(): array
    {
        return [
            'setCookie'     => [true],
            'dontSetCookie' => [false],
        ];
    }

    /**
     * @test
     * @param $exceptionClass
     * @param $writeLog
     * @param $setCookie
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Model\WebauthnLogin::frontendLogin
     * @dataProvider frontendLoginExceptionDataProvider
     */
    public function frontendLoginException($exceptionClass, $writeLog, $setCookie)
    {
        /** @var UtilsView|MockObject $utilsViewMock */
        $utilsViewMock = $this->getMockBuilder(UtilsView::class)
            ->onlyMethods(['addErrorToDisplay'])
            ->getMock();
        $utilsViewMock->expects($this->once())->method('addErrorToDisplay');

        /** @var User|MockObject $userMock */
        $userMock = $this->getMockBuilder(User::class)
            ->onlyMethods(['logout'])
            ->getMock();
        $userMock->expects($this->once())->method('logout');

        /** @var LoggerInterface|MockObject $loggerMock */
        $loggerMock = $this->getMockForAbstractClass(LoggerInterface::class, [], '', true, true, true, ['error', 'debug']);
        $loggerMock->expects($writeLog)->method('error')->willReturn(true);
        $loggerMock->expects($writeLog)->method('debug')->willReturn(true);

        /** @var UserComponent|MockObject $userComponentMock */
        $userComponentMock = $this->getMockBuilder(UserComponent::class)
            ->onlyMethods(['setUser'])
            ->getMock();
        $userComponentMock->expects($this->never())->method('setUser');

        /** @var WebauthnLogin|MockObject $sut */
        $sut = $this->getMockBuilder(WebauthnLogin::class)
            ->onlyMethods(['getUserId', 'handleErrorMessage', 'assertUser', 'assertAuthn', 'd3GetMockableRegistryObject',
                'setFrontendSession', 'handleBackendCookie', 'handleBackendSubshopRights', 'setSessionCookie',
                'd3GetMockableLogger', 'd3GetMockableOxNewObject', 'getCredential', 'regenerateSessionId', ])
            ->disableOriginalConstructor()
            ->getMock();
        $sut->method('handleErrorMessage')->willThrowException(oxNew($exceptionClass));
        $sut->method('d3GetMockableRegistryObject')->willReturnCallback(
            function () use ($utilsViewMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case UtilsView::class:
                        return $utilsViewMock;
                    default:
                        return Registry::get($args[0]);
                }
            }
        );
        $sut->method('d3GetMockableOxNewObject')->willReturnCallback(
            function () use ($userMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case User::class:
                        return $userMock;
                    default:
                        return call_user_func_array("oxNew", $args);
                }
            }
        );
        $sut->method('d3GetMockableLogger')->willReturn($loggerMock);

        $this->expectException(WebauthnLoginErrorException::class);

        $this->assertEmpty(
            $this->callMethod(
                $sut,
                'frontendLogin',
                [$userComponentMock, $setCookie]
            )
        );
    }

    /**
     * @return array[]
     */
    public function frontendLoginExceptionDataProvider(): array
    {
        return [
            'userException'         => [UserException::class, $this->any(), false],
            'cookieException'       => [CookieException::class, $this->any(), true],
            'webauthnException'     => [WebauthnException::class, $this->atLeastOnce(), false],
            'webauthnGetException'  => [WebauthnGetException::class, $this->atLeastOnce(), true],
        ];
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Model\WebauthnLogin::adminLogin
     */
    public function adminLoginSuccess()
    {
        /** @var User|MockObject $userMock */
        $userMock = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var WebauthnLogin|MockObject $sut */
        $sut = $this->getMockBuilder(WebauthnLogin::class)
            ->onlyMethods(['getUserId', 'handleErrorMessage', 'assertUser', 'assertAuthn',
                'setAdminSession', 'handleBackendCookie', 'handleBackendSubshopRights',
                'd3GetMockableOxNewObject', ])
            ->disableOriginalConstructor()
            ->getMock();
        $sut->method('d3GetMockableOxNewObject')->willReturnCallback(
            function () use ($userMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case User::class:
                        return $userMock;
                    default:
                        return call_user_func_array("oxNew", $args);
                }
            }
        );

        $this->assertSame(
            'admin_start',
            $this->callMethod(
                $sut,
                'adminLogin',
                [1]
            )
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Model\WebauthnLogin::adminLogin
     * @dataProvider adminLoginExceptionDataProvider
     */
    public function adminLoginException($exceptionClass, $writeLog)
    {
        /** @var SystemEventHandler|MockObject $systemEventHandlerMock */
        $systemEventHandlerMock = $this->getMockBuilder(SystemEventHandler::class)
            ->onlyMethods(['onAdminLogin'])
            ->getMock();
        $systemEventHandlerMock->expects($this->never())->method('onAdminLogin');

        /** @var UtilsView|MockObject $utilsViewMock */
        $utilsViewMock = $this->getMockBuilder(UtilsView::class)
            ->onlyMethods(['addErrorToDisplay'])
            ->getMock();
        $utilsViewMock->expects($this->once())->method('addErrorToDisplay');

        /** @var User|MockObject $userMock */
        $userMock = $this->getMockBuilder(User::class)
            ->onlyMethods(['logout'])
            ->getMock();
        $userMock->expects($this->once())->method('logout');

        /** @var LoggerInterface|MockObject $loggerMock */
        $loggerMock = $this->getMockForAbstractClass(LoggerInterface::class, [], '', true, true, true, ['error', 'debug']);
        $loggerMock->expects($writeLog)->method('error')->willReturn(true);
        $loggerMock->expects($writeLog)->method('debug')->willReturn(true);

        /** @var WebauthnLogin|MockObject $sut */
        $sut = $this->getMockBuilder(WebauthnLogin::class)
            ->onlyMethods(['getUserId', 'handleErrorMessage', 'assertUser', 'assertAuthn',
                'setAdminSession', 'handleBackendCookie', 'handleBackendSubshopRights',
                'd3GetMockableOxNewObject', 'd3GetMockableRegistryObject',
                'd3GetMockableLogger', ])
            ->disableOriginalConstructor()
            ->getMock();
        $sut->method('handleErrorMessage')->willThrowException(oxNew($exceptionClass));
        $sut->method('d3GetMockableRegistryObject')->willReturnCallback(
            function () use ($utilsViewMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case UtilsView::class:
                        return $utilsViewMock;
                    default:
                        return Registry::get($args[0]);
                }
            }
        );
        $sut->method('d3GetMockableLogger')->willReturn($loggerMock);
        $sut->method('d3GetMockableOxNewObject')->willReturnCallback(
            function () use ($userMock, $systemEventHandlerMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case User::class:
                        return $userMock;
                    case SystemEventHandler::class:
                        return $systemEventHandlerMock;
                    default:
                        return call_user_func_array("oxNew", $args);
                }
            }
        );

        $this->assertSame(
            'login',
            $this->callMethod(
                $sut,
                'adminLogin',
                [1]
            )
        );
    }

    /**
     * @return array
     */
    public function adminLoginExceptionDataProvider(): array
    {
        return [
            'userException'         => [UserException::class, $this->any()],
            'cookieException'       => [CookieException::class, $this->any()],
            'webauthnException'     => [WebauthnException::class, $this->atLeastOnce()],
            'webauthnGetException'  => [WebauthnGetException::class, $this->atLeastOnce()],
        ];
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Model\WebauthnLogin::handleErrorMessage
     * @dataProvider canHandleErrorMessageDataProvider
     */
    public function canHandleErrorMessage($message, $throwsException)
    {
        /** @var WebauthnLogin|MockObject $sut */
        $sut = $this->getMockBuilder(WebauthnLogin::class)
            ->onlyMethods(['getErrorMsg'])
            ->disableOriginalConstructor()
            ->getMock();
        $sut->method('getErrorMsg')->willReturn($message);

        if ($throwsException) {
            $this->expectException(WebauthnGetException::class);
        }

        $this->assertEmpty(
            $this->callMethod(
                $sut,
                'handleErrorMessage'
            )
        );
    }

    /**
     * @return array
     */
    public function canHandleErrorMessageDataProvider(): array
    {
        return [
            'has error message' => ['errorMessage', true],
            'empty error message'  => ['', false],
            'error message null'  => [null, false],
        ];
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Model\WebauthnLogin::assertAuthn
     * @dataProvider canAssertAuthDataProvider
     */
    public function canAssertAuthn($credential, $doAssert, $throwException)
    {
        /** @var Webauthn|MockObject $webauthnMock */
        $webauthnMock = $this->getMockBuilder(Webauthn::class)
            ->onlyMethods(['assertAuthn'])
            ->getMock();
        $webauthnMock->expects($doAssert)->method('assertAuthn');

        /** @var WebauthnLogin|MockObject $sut */
        $sut = $this->getMockBuilder(WebauthnLogin::class)
            ->onlyMethods(['getCredential', 'd3GetMockableOxNewObject'])
            ->disableOriginalConstructor()
            ->getMock();
        if ($throwException) {
            $sut->method('getCredential')->willThrowException(oxNew(WebauthnGetException::class));
        } else {
            $sut->method('getCredential')->willReturn('credential');
        }
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

        if ($throwException) {
            $this->expectException(WebauthnGetException::class);
        }

        $this->assertEmpty(
            $this->callMethod(
                $sut,
                'assertAuthn'
            )
        );
    }

    /**
     * @return array
     */
    public function canAssertAuthDataProvider(): array
    {
        return [
            'has credential' => ['credentialFixture', $this->atLeastOnce(), false],
            'no credential'  => [null, $this->never(), true],
        ];
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Model\WebauthnLogin::setAdminSession
     */
    public function canSetAdminSession()
    {
        /** @var Session|MockObject $sessionMock */
        $sessionMock = $this->getMockBuilder(Session::class)
            ->onlyMethods(['getVariable', 'initNewSession', 'setVariable'])
            ->getMock();
        $sessionMock->method('getVariable')->willReturn('sessVariable');
        $sessionMock->expects($this->once())->method('initNewSession');
        $sessionMock->method('setVariable')->with(
            $this->anything(),
            $this->logicalOr(
                $this->identicalTo('sessVariable'),
                $this->identicalTo('userId')
            )
        );

        /** @var WebauthnLogin|MockObject $sut */
        $sut = $this->getMockBuilder(WebauthnLogin::class)
            ->onlyMethods(['d3GetMockableRegistryObject'])
            ->disableOriginalConstructor()
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
            $sessionMock,
            $this->callMethod(
                $sut,
                'setAdminSession',
                ['userId']
            )
        );
    }

    /**
     * @test
     * @param $setCookie
     * @return void
     * @throws ReflectionException
     * @dataProvider canSetSessionCookieDataProvider
     * @covers \D3\Webauthn\Application\Model\WebauthnLogin::setSessionCookie
     */
    public function canSetSessionCookie($setCookie)
    {
        /** @var User|MockObject $userMock */
        $userMock = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var UtilsServer|MockObject $utilsServerMock */
        $utilsServerMock = $this->getMockBuilder(UtilsServer::class)
            ->onlyMethods(['setUserCookie'])
            ->getMock();
        $utilsServerMock->expects($this->exactly((int) $setCookie))->method('setUserCookie');

        /** @var Config|MockObject $configMock */
        $configMock = $this->getMockBuilder(Config::class)
            ->onlyMethods(['getConfigParam', 'getShopId'])
            ->getMock();
        $configMock->method('getConfigParam')->with('blShowRememberMe')->willReturn($setCookie);
        $configMock->method('getShopId')->willReturn(1);

        /** @var WebauthnLogin|MockObject $sut */
        $sut = $this->getMockBuilder(WebauthnLogin::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['d3GetMockableRegistryObject'])
            ->getMock();
        $sut->method('d3GetMockableRegistryObject')->willReturnCallback(
            function () use ($utilsServerMock, $configMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case UtilsServer::class:
                        return $utilsServerMock;
                    case Config::class:
                        return $configMock;
                    default:
                        return Registry::get($args[0]);
                }
            }
        );

        $this->callMethod(
            $sut,
            'setSessionCookie',
            [$userMock]
        );
    }

    /**
     * @return array
     */
    public function canSetSessionCookieDataProvider(): array
    {
        return [
            'set cookie'        => [true],
            'dont set cookie'   => [false],
        ];
    }

    /**
     * @test
     * @param $isBackend
     * @param $userLoaded
     * @param $rights
     * @param $throwsException
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Model\WebauthnLogin::assertUser
     * @dataProvider canAssertUserDataProvider
     */
    public function canAssertUser($isBackend, $userLoaded, $rights, $throwsException)
    {
        /** @var User|MockObject $userMock */
        $userMock = $this->getMockBuilder(User::class)
            ->onlyMethods(['isLoaded', 'getFieldData'])
            ->getMock();
        $userMock->method('isLoaded')->willReturn($userLoaded);
        $userMock->method('getFieldData')->with('oxrights')->willReturn($rights);

        /** @var WebauthnLogin|MockObject $sut */
        $sut = $this->getMockBuilder(WebauthnLogin::class)
            ->onlyMethods(['d3GetMockableOxNewObject'])
            ->disableOriginalConstructor()
            ->getMock();
        $sut->method('d3GetMockableOxNewObject')->willReturnCallback(
            function () use ($userMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case User::class:
                        return $userMock;
                    default:
                        return call_user_func_array("oxNew", $args);
                }
            }
        );

        if ($throwsException) {
            $this->expectException(UserException::class);
        }

        $this->assertInstanceOf(
            User::class,
            $this->callMethod(
                $sut,
                'assertUser',
                ['userId', $isBackend]
            )
        );
    }

    /**
     * @return array[]
     */
    public function canAssertUserDataProvider(): array
    {
        return [
            'frontend, user not loaded'       => [false, false, 'malladmin', true],
            'backend, user not loaded'          => [true, false, 'malladmin', true],
            'frontend, frontend user loaded'  => [false, true, 'user', false],
            'backend, frontend user loaded'  => [true, true, 'user', true],
            'frontend, backend user loaded'   => [false, true, 'malladmin', false],
            'backend, backend user loaded'   => [true, true, 'malladmin', false],
            'frontend, backend 2 user loaded' => [false, true, '2', false],
            'backend, backend 2 user loaded' => [true, true, '2', false],
        ];
    }

    /**
     * @test
     * @param $cookie
     * @param $throwException
     * @return void
     * @throws ReflectionException
     * @dataProvider canHandleBackendCookieDataProvider
     * @covers \D3\Webauthn\Application\Model\WebauthnLogin::handleBackendCookie
     */
    public function canHandleBackendCookie($cookie, $throwException)
    {
        /** @var UtilsServer|MockObject $utilsServerMock */
        $utilsServerMock = $this->getMockBuilder(UtilsServer::class)
            ->onlyMethods(['getOxCookie'])
            ->getMock();
        $utilsServerMock->method('getOxCookie')->willReturn($cookie);

        /** @var WebauthnLogin|MockObject $sut */
        $sut = $this->getMockBuilder(WebauthnLogin::class)
            ->onlyMethods(['d3GetMockableRegistryObject'])
            ->disableOriginalConstructor()
            ->getMock();
        $sut->method('d3GetMockableRegistryObject')->willReturnCallback(
            function () use ($utilsServerMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case UtilsServer::class:
                        return $utilsServerMock;
                    default:
                        return Registry::get($args[0]);
                }
            }
        );

        if ($throwException) {
            $this->expectException(CookieException::class);
        }

        $this->assertEmpty(
            $this->callMethod(
                $sut,
                'handleBackendCookie'
            )
        );
    }

    /**
     * @return array[]
     */
    public function canHandleBackendCookieDataProvider(): array
    {
        return [
            'has cookie'    => ['cookiecontent', false],
            'has no cookie' => [null, true],
        ];
    }

    /**
     * @test
     * @param $rights
     * @param $setVar
     * @return void
     * @throws ReflectionException
     * @dataProvider canHandleBackendSubshopRightsDataProvider
     * @covers \D3\Webauthn\Application\Model\WebauthnLogin::handleBackendSubshopRights
     */
    public function canHandleBackendSubshopRights($rights, $setVar)
    {
        /** @var Config|MockObject $configMock */
        $configMock = $this->getMockBuilder(Config::class)
            ->onlyMethods(['setShopId'])
            ->getMock();
        $configMock->expects($setVar)->method('setShopId');

        /** @var User|MockObject $userMock */
        $userMock = $this->getMockBuilder(User::class)
            ->onlyMethods(['getFieldData'])
            ->getMock();
        $userMock->method('getFieldData')->with('oxrights')->willReturn($rights);

        /** @var Session|MockObject $sessionMock */
        $sessionMock = $this->getMockBuilder(Session::class)
            ->onlyMethods(['setVariable'])
            ->getMock();
        $sessionMock->expects($setVar)->method('setVariable');

        /** @var WebauthnLogin|MockObject $sut */
        $sut = $this->getMockBuilder(WebauthnLogin::class)
            ->onlyMethods(['d3GetMockableRegistryObject'])
            ->disableOriginalConstructor()
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

        $this->callMethod(
            $sut,
            'handleBackendSubshopRights',
            [$userMock, $sessionMock]
        );
    }

    /**
     * @return array
     */
    public function canHandleBackendSubshopRightsDataProvider(): array
    {
        return [
            'malladmin'     => ['malladmin', $this->never()],
            '1'             => ['1', $this->atLeastOnce()],
            '2'             => ['2', $this->atLeastOnce()],
        ];
    }

    /**
     * @test
     * @param $sessionStarted
     * @return void
     * @throws ReflectionException
     * @dataProvider canRegenerateSessionIdDataProvider
     * @covers \D3\Webauthn\Application\Model\WebauthnLogin::regenerateSessionId
     */
    public function canRegenerateSessionId($sessionStarted)
    {
        /** @var Session|MockObject $sessionMock */
        $sessionMock = $this->getMockBuilder(Session::class)
            ->onlyMethods(['isSessionStarted', 'regenerateSessionId'])
            ->getMock();
        $sessionMock->method('isSessionStarted')->willReturn($sessionStarted);
        $sessionMock->expects($this->exactly((int) $sessionStarted))->method('regenerateSessionId');

        /** @var WebauthnLogin|MockObject $sut */
        $sut = $this->getMockBuilder(WebauthnLogin::class)
            ->disableOriginalConstructor()
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
            'regenerateSessionId'
        );
    }

    /**
     * @return array
     */
    public function canRegenerateSessionIdDataProvider(): array
    {
        return [
            'session started'       => [true],
            'session not started'   => [false],
        ];
    }

    /**
     * @test
     * @param $inBlockedGroup
     * @return void
     * @throws ReflectionException
     * @dataProvider canHandleBlockedUserDataProvider
     * @covers \D3\Webauthn\Application\Model\WebauthnLogin::handleBlockedUser
     */
    public function canHandleBlockedUser($inBlockedGroup)
    {
        /** @var Utils|MockObject $utilsMock */
        $utilsMock = $this->getMockBuilder(Utils::class)
            ->onlyMethods(['redirect'])
            ->getMock();
        $utilsMock->expects($this->exactly((int) $inBlockedGroup))->method('redirect');

        /** @var Config|MockObject $configMock */
        $configMock = $this->getMockBuilder(Config::class)
            ->onlyMethods(['getShopHomeUrl'])
            ->getMock();
        $configMock->method('getShopHomeUrl')->willReturn('homeurl');

        /** @var User|MockObject $userMock */
        $userMock = $this->getMockBuilder(User::class)
            ->onlyMethods(['inGroup'])
            ->getMock();
        $userMock->method('inGroup')->willReturn($inBlockedGroup);

        /** @var WebauthnLogin|MockObject $sut */
        $sut = $this->getMockBuilder(WebauthnLogin::class)
            ->onlyMethods(['d3GetMockableRegistryObject'])
            ->disableOriginalConstructor()
            ->getMock();
        $sut->method('d3GetMockableRegistryObject')->willReturnCallback(
            function () use ($utilsMock, $configMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case Utils::class:
                        return $utilsMock;
                    case Config::class:
                        return $configMock;
                    default:
                        return Registry::get($args[0]);
                }
            }
        );

        $this->callMethod(
            $sut,
            'handleBlockedUser',
            [$userMock]
        );
    }

    /**
     * @return array
     */
    public function canHandleBlockedUserDataProvider(): array
    {
        return [
            'is in blocked group'       => [true],
            'is not in blocked group'   => [false],
        ];
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Model\WebauthnLogin::updateBasket
     */
    public function canUpdateBasket()
    {
        /** @var Basket|MockObject $basketMock */
        $basketMock = $this->getMockBuilder(Basket::class)
            ->onlyMethods(['onUpdate'])
            ->getMock();
        $basketMock->expects($this->once())->method('onUpdate');

        /** @var Session|MockObject $sessionMock */
        $sessionMock = $this->getMockBuilder(Session::class)
            ->onlyMethods(['getBasket'])
            ->getMock();
        $sessionMock->method('getBasket')->willReturn($basketMock);

        /** @var WebauthnLogin|MockObject $sut */
        $sut = $this->getMockBuilder(WebauthnLogin::class)
            ->disableOriginalConstructor()
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
            'updateBasket'
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Model\WebauthnLogin::isAdmin
     */
    public function canGetIsAdmin()
    {
        /** @var WebauthnLogin $sut */
        $sut = oxNew(WebauthnLogin::class, 'cred');

        $this->assertIsBool(
            $this->callMethod(
                $sut,
                'isAdmin'
            )
        );
    }

    /**
     * @test
     * @param $isAdmin
     * @param $expected
     * @return void
     * @throws ReflectionException
     * @dataProvider canGetUserIdDataProvider
     * @covers       \D3\Webauthn\Application\Model\WebauthnLogin::getUserId
     */
    public function canGetUserId($isAdmin, $expected)
    {
        /** @var Session|MockObject $sessionMock */
        $sessionMock = $this->getMockBuilder(Session::class)
            ->onlyMethods(['getVariable'])
            ->getMock();
        $sessionMock->method('getVariable')->willReturnCallback(
            function () {
                $args = func_get_args();
                if ($args[0] === WebauthnConf::WEBAUTHN_ADMIN_SESSION_CURRENTUSER) {
                    return 'adminUser';
                } elseif ($args[0] === WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER) {
                    return 'frontendUser';
                }
                return null;
            }
        );

        /** @var WebauthnLogin|MockObject $sut */
        $sut = $this->getMockBuilder(WebauthnLogin::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['d3GetMockableRegistryObject', 'isAdmin'])
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
        $sut->method('isAdmin')->willReturn($isAdmin);

        $this->assertSame(
            $expected,
            $this->callMethod(
                $sut,
                'getUserId'
            )
        );
    }

    /**
     * @return array
     */
    public function canGetUserIdDataProvider(): array
    {
        return [
            'admin'     => [true, 'adminUser'],
            'frontend'  => [false, 'frontendUser'],
        ];
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Model\WebauthnLogin::setFrontendSession
     */
    public function canSetFrontendSession()
    {
        /** @var Session|MockObject $sessionMock */
        $sessionMock = $this->getMockBuilder(Session::class)
            ->onlyMethods(['setVariable'])
            ->getMock();
        $sessionMock->expects($this->exactly(2))->method('setVariable')->withConsecutive(
            [
                $this->identicalTo(WebauthnConf::WEBAUTHN_SESSION_AUTH),
                $this->identicalTo('credentialFixture'),
            ],
            [
                $this->identicalTo(WebauthnConf::OXID_FRONTEND_AUTH),
                $this->identicalTo('idFixture'),
            ]
        );

        /** @var User|MockObject $userMock */
        $userMock = $this->getMockBuilder(User::class)
            ->onlyMethods(['getId'])
            ->getMock();
        $userMock->method('getId')->willReturn('idFixture');

        /** @var WebauthnLogin|MockObject $sut */
        $sut = $this->getMockBuilder(WebauthnLogin::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCredential', 'd3GetMockableRegistryObject'])
            ->getMock();
        $sut->method('getCredential')->willReturn('credentialFixture');
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
            'setFrontendSession',
            [$userMock]
        );
    }
}
