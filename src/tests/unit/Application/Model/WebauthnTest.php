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

use Assert\InvalidArgumentException;
use D3\TestingTools\Development\CanAccessRestricted;
use D3\Webauthn\Application\Model\Credential\PublicKeyCredential;
use D3\Webauthn\Application\Model\Credential\PublicKeyCredentialList;
use D3\Webauthn\Application\Model\Exceptions\WebauthnGetException;
use D3\Webauthn\Application\Model\RelyingPartyEntity;
use D3\Webauthn\Application\Model\UserEntity;
use D3\Webauthn\Application\Model\Webauthn;
use D3\Webauthn\Application\Model\WebauthnConf;
use Exception;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\Eshop\Core\UtilsView;
use OxidEsales\TestingLibrary\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;
use stdClass;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\Server;

class WebauthnTest extends UnitTestCase
{
    use CanAccessRestricted;

    /**
     * @test
     * @param $https
     * @param $forwardedProto
     * @param $forwardedSSL
     * @param $remoteAddr
     * @param $expected
     * @return void
     * @throws ReflectionException
     * @dataProvider canCheckIsAvailableDataProvider
     * @covers       \D3\Webauthn\Application\Model\Webauthn::isAvailable
     */
    public function canCheckIsAvailable($https, $forwardedProto, $forwardedSSL, $remoteAddr, $expected)
    {
        $_SERVER['HTTPS'] = $https;
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = $forwardedProto;
        $_SERVER['HTTP_X_FORWARDED_SSL'] = $forwardedSSL;
        $_SERVER['REMOTE_ADDR'] = $remoteAddr;

        /** @var UtilsView|MockObject $utilsViewMock */
        $utilsViewMock = $this->getMockBuilder(UtilsView::class)
            ->onlyMethods(['addErrorToDisplay'])
            ->getMock();
        $utilsViewMock->expects($this->exactly((int) !$expected))->method('addErrorToDisplay');

        /** @var Webauthn|MockObject $sut */
        $sut = $this->getMockBuilder(Webauthn::class)
            ->onlyMethods(['d3GetMockableRegistryObject'])
            ->getMock();
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

        $this->assertSame(
            $expected,
            $this->callMethod(
                $sut,
                'isAvailable'
            )
        );
    }

    /**
     * @return array[]
     */
    public function canCheckIsAvailableDataProvider(): array
    {
        return [
            'https'                     => ['on', null, null, null, true],
            'HTTP_X_FORWARDED_PROTO'    => [null, 'https', null, null, true],
            'HTTP_X_FORWARDED_SSL'      => [null, null, 'on', null, true],
            'REMOTE_ADDR v4'            => [null, null, null, '127.0.0.1', true],
            'REMOTE_ADDR v6'            => [null, null, null, '::1', true],
            'REMOTE_ADDR localhost'     => [null, null, null, 'some.localhost', true],
            'unset'                     => [null, null, null, null, false],
            'not valid'                 => ['off', 'http', 'off', '160.158.23.7', false]
        ];
    }

    /**
     * @test
     * @param $jsonReturn
     * @return void
     * @throws ReflectionException
     * @dataProvider canGetOptionsDataProvider
     * @covers \D3\Webauthn\Application\Model\Webauthn::getCreationOptions
     */
    public function canGetCreationOptions($jsonReturn)
    {
        /** @var PublicKeyCredentialDescriptor|MockObject $pubKeyCredDescriptorMock */
        $pubKeyCredDescriptorMock = $this->getMockBuilder(PublicKeyCredentialDescriptor::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var PublicKeyCredentialCreationOptions|MockObject $pubKeyCredCreationOptionsMock */
        $pubKeyCredCreationOptionsMock = $this->getMockBuilder(PublicKeyCredentialCreationOptions::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Server|MockObject $serverMock */
        $serverMock = $this->getMockBuilder(Server::class)
            ->onlyMethods(['generatePublicKeyCredentialCreationOptions'])
            ->disableOriginalConstructor()
            ->getMock();
        $serverMock->expects($this->once())->method('generatePublicKeyCredentialCreationOptions')->with(
            $this->anything(),
            $this->anything(),
            $this->identicalTo([$pubKeyCredDescriptorMock])
        )->willReturn($pubKeyCredCreationOptionsMock);

        /** @var Session|MockObject $sessionMock */
        $sessionMock = $this->getMockBuilder(Session::class)
            ->onlyMethods(['setVariable'])
            ->getMock();
        $sessionMock->expects($this->once())->method('setVariable')->with(
            $this->identicalTo(Webauthn::SESSION_CREATIONS_OPTIONS),
            $this->identicalTo($pubKeyCredCreationOptionsMock)
        );

        /** @var UserEntity|MockObject $userEntityMock */
        $userEntityMock = $this->getMockBuilder(UserEntity::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var User|MockObject $userMock */
        $userMock = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Webauthn|MockObject $sut */
        $sut = $this->getMockBuilder(Webauthn::class)
            ->onlyMethods(['d3GetMockableOxNewObject', 'getServer', 'd3GetMockableRegistryObject', 'jsonEncode',
                'getExistingCredentials'
            ])
            ->getMock();
        $sut->method('d3GetMockableOxNewObject')->willReturnCallback(
            function () use ($userEntityMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case UserEntity::class:
                        return $userEntityMock;
                    default:
                        return call_user_func_array("oxNew", $args);
                }
            }
        );
        $sut->method('getServer')->willReturn($serverMock);
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
        $sut->expects($this->once())->method('jsonEncode')->willReturn($jsonReturn);
        $sut->expects($this->once())->method('getExistingCredentials')->willReturn([
            $pubKeyCredDescriptorMock
        ]);

        if (!$jsonReturn) {
            $this->expectException(Exception::class);
        }

        $return =  $this->callMethod(
            $sut,
            'getCreationOptions',
            [$userMock]
        );

        if ($jsonReturn) {
            $this->assertSame(
                $jsonReturn,
                $return
            );
        }
    }

    /**
     * @return array
     */
    public function canGetOptionsDataProvider(): array
    {
        return [
            'json encoded'  => ['jsonstring'],
            'json failed'   => [false],
        ];
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Model\Webauthn::getExistingCredentials
     */
    public function canGetExistingCredentials()
    {
        /** @var PublicKeyCredentialDescriptor|MockObject $pubKeyCredDescriptorMock */
        $pubKeyCredDescriptorMock = $this->getMockBuilder(PublicKeyCredentialDescriptor::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var PublicKeyCredentialSource|MockObject $pubKeyCredSourceMock */
        $pubKeyCredSourceMock = $this->getMockBuilder(PublicKeyCredentialSource::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPublicKeyCredentialDescriptor'])
            ->getMock();
        $pubKeyCredSourceMock->method('getPublicKeyCredentialDescriptor')->willReturn($pubKeyCredDescriptorMock);

        /** @var PublicKeyCredentialList|MockObject $pubKeyCredListMock */
        $pubKeyCredListMock = $this->getMockBuilder(PublicKeyCredentialList::class)
            ->onlyMethods(['findAllForUserEntity'])
            ->getMock();
        $pubKeyCredListMock->method('findAllForUserEntity')->willReturn(
            [$pubKeyCredSourceMock]
        );

        /** @var UserEntity|MockObject $userEntityMock */
        $userEntityMock = $this->getMockBuilder(UserEntity::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Webauthn|MockObject $sut */
        $sut = $this->getMockBuilder(Webauthn::class)
            ->onlyMethods(['d3GetMockableOxNewObject'])
            ->getMock();
        $sut->method('d3GetMockableOxNewObject')->willReturnCallback(
            function () use ($pubKeyCredListMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case PublicKeyCredentialList::class:
                        return $pubKeyCredListMock;
                    default:
                        return call_user_func_array("oxNew", $args);
                }
            }
        );

        $return = $this->callMethod(
            $sut,
            'getExistingCredentials',
            [$userEntityMock]
        );

        $this->assertIsArray($return);
        $this->assertContains($pubKeyCredDescriptorMock, $return);
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Model\Webauthn::jsonEncode
     */
    public function canJsonEncode()
    {
        /** @var PublicKeyCredentialCreationOptions|MockObject $pubKeyCredCreationsOptions */
        $pubKeyCredCreationsOptions = $this->getMockBuilder(PublicKeyCredentialCreationOptions::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sut = oxNew(Webauthn::class);

        $this->assertJson(
            $this->callMethod(
                $sut,
                'jsonEncode',
                [$pubKeyCredCreationsOptions]
            )
        );
    }

    /**
     * @test
     * @param $jsonReturn
     * @return void
     * @throws ReflectionException
     * @dataProvider canGetOptionsDataProvider
     * @covers \D3\Webauthn\Application\Model\Webauthn::getRequestOptions
     */
    public function canGetRequestOptions($jsonReturn)
    {
        /** @var PublicKeyCredentialDescriptor|MockObject $pubKeyCredDescriptorMock */
        $pubKeyCredDescriptorMock = $this->getMockBuilder(PublicKeyCredentialDescriptor::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var PublicKeyCredentialRequestOptions|MockObject $pubKeyCredRequestOptionsMock */
        $pubKeyCredRequestOptionsMock = $this->getMockBuilder(PublicKeyCredentialRequestOptions::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Server|MockObject $serverMock */
        $serverMock = $this->getMockBuilder(Server::class)
            ->onlyMethods(['generatePublicKeyCredentialRequestOptions'])
            ->disableOriginalConstructor()
            ->getMock();
        $serverMock->expects($this->once())->method('generatePublicKeyCredentialRequestOptions')->with(
            $this->anything(),
            $this->identicalTo([$pubKeyCredDescriptorMock])
        )->willReturn($pubKeyCredRequestOptionsMock);

        /** @var Session|MockObject $sessionMock */
        $sessionMock = $this->getMockBuilder(Session::class)
            ->onlyMethods(['setVariable'])
            ->getMock();
        $sessionMock->expects($this->once())->method('setVariable')->with(
            $this->identicalTo(Webauthn::SESSION_ASSERTION_OPTIONS),
            $this->identicalTo($pubKeyCredRequestOptionsMock)
        );

        /** @var User|MockObject $userMock */
        $userMock = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['load'])
            ->getMock();
        $userMock->method('load')->willReturn(true);

        /** @var UserEntity|MockObject $userEntityMock */
        $userEntityMock = $this->getMockBuilder(UserEntity::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Webauthn|MockObject $sut */
        $sut = $this->getMockBuilder(Webauthn::class)
            ->onlyMethods(['d3GetMockableOxNewObject', 'getServer', 'd3GetMockableRegistryObject', 'jsonEncode',
                'getExistingCredentials'
            ])
            ->getMock();
        $sut->method('d3GetMockableOxNewObject')->willReturnCallback(
            function () use ($userEntityMock, $userMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case UserEntity::class:
                        return $userEntityMock;
                    case User::class:
                        return $userMock;
                    default:
                        return call_user_func_array("oxNew", $args);
                }
            }
        );
        $sut->method('getServer')->willReturn($serverMock);
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
        $sut->expects($this->once())->method('jsonEncode')->willReturn($jsonReturn);
        $sut->expects($this->once())->method('getExistingCredentials')->willReturn([
            $pubKeyCredDescriptorMock
        ]);

        if (!$jsonReturn) {
            $this->expectException(Exception::class);
        }

        $return =  $this->callMethod(
            $sut,
            'getRequestOptions',
            ['userId']
        );

        if ($jsonReturn) {
            $this->assertSame(
                $jsonReturn,
                $return
            );
        }
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Model\Webauthn::getServer
     */
    public function canGetServer()
    {
        /** @var PublicKeyCredentialList|MockObject $pubKeyCredListMock */
        $pubKeyCredListMock = $this->getMockBuilder(PublicKeyCredentialList::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Server|MockObject $serverMock */
        $serverMock = $this->getMockBuilder(Server::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setLogger'])
            ->getMock();
        $serverMock->expects($this->atLeastOnce())->method('setLogger');

        /** @var RelyingPartyEntity|MockObject $rpEntityMock */
        $rpEntityMock = $this->getMockBuilder(RelyingPartyEntity::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Webauthn|MockObject $sut */
        $sut = $this->getMockBuilder(Webauthn::class)
            ->onlyMethods(['d3GetMockableOxNewObject'])
            ->getMock();
        $sut->method('d3GetMockableOxNewObject')->willReturnCallback(
            function () use ($rpEntityMock, $serverMock, $pubKeyCredListMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case RelyingPartyEntity::class:
                        return $rpEntityMock;
                    case Server::class:
                        return $serverMock;
                    case PublicKeyCredentialList::class:
                        return $pubKeyCredListMock;
                    default:
                        return call_user_func_array("oxNew", $args);
                }
            }
        );

        $this->assertSame(
            $serverMock,
            $this->callMethod(
                $sut,
                'getServer'
            )
        );
    }

    /**
     * @test
     * @param $throwsException
     * @return void
     * @throws ReflectionException
     * @dataProvider loadAndCheckAssertionResponseDataProvider
     * @covers \D3\Webauthn\Application\Model\Webauthn::saveAuthn
     */
    public function canSaveAuthn($throwsException)
    {
        /** @var PublicKeyCredentialCreationOptions|MockObject $pubKeyCredCreationsOptionsMock */
        $pubKeyCredCreationsOptionsMock = $this->getMockBuilder(PublicKeyCredentialCreationOptions::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var PublicKeyCredential|MockObject $pubKeyCredMock */
        $pubKeyCredMock = $this->getMockBuilder(PublicKeyCredential::class)
            ->onlyMethods(['saveCredentialSource'])
            ->getMock();
        $pubKeyCredMock->expects($this->exactly((int) !$throwsException))->method('saveCredentialSource');

        /** @var Session|MockObject $sessionMock */
        $sessionMock = $this->getMockBuilder(Session::class)
            ->onlyMethods(['getVariable'])
            ->getMock();
        $sessionMock->method('getVariable')->willReturn($pubKeyCredCreationsOptionsMock);

        /** @var Server|MockObject $serverMock */
        $serverMock = $this->getMockBuilder(Server::class)
            ->onlyMethods(['loadAndCheckAttestationResponse'])
            ->disableOriginalConstructor()
            ->getMock();
        if ($throwsException) {
            $serverMock->expects($this->atLeastOnce())->method('loadAndCheckAttestationResponse')
                ->willThrowException(new InvalidArgumentException('msg', 20));
        } else {
            $serverMock->expects($this->atLeastOnce())->method('loadAndCheckAttestationResponse');
        }

        /** @var Webauthn|MockObject $sut */
        $sut = $this->getMockBuilder(Webauthn::class)
            ->onlyMethods(['d3GetMockableRegistryObject', 'd3GetMockableOxNewObject', 'getServer'])
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
            function () use ($pubKeyCredMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case PublicKeyCredential::class:
                        return $pubKeyCredMock;
                    default:
                        return call_user_func_array("oxNew", $args);
                }
            }
        );
        $sut->method('getServer')->willReturn($serverMock);

        if ($throwsException) {
            $this->expectException(InvalidArgumentException::class);
        }

        $this->callMethod(
            $sut,
            'saveAuthn',
            ['credential', 'keyName']
        );
    }

    /**
     * @return array
     */
    public function loadAndCheckAssertionResponseDataProvider(): array
    {
        return [
            'check failed'  => [true],
            'check passed'  => [false],
        ];
    }


    /**
     * @test
     * @param $throwsException
     * @return void
     * @throws ReflectionException
     * @dataProvider loadAndCheckAssertionResponseDataProvider
     * @covers \D3\Webauthn\Application\Model\Webauthn::assertAuthn
     */
    public function canAssertAuthn($throwsException)
    {
        /** @var PublicKeyCredentialRequestOptions|MockObject $pubKeyCredRequestOptionsMock */
        $pubKeyCredRequestOptionsMock = $this->getMockBuilder(PublicKeyCredentialRequestOptions::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Session|MockObject $sessionMock */
        $sessionMock = $this->getMockBuilder(Session::class)
            ->onlyMethods(['getVariable'])
            ->getMock();
        $sessionMock->method('getVariable')->willReturn($pubKeyCredRequestOptionsMock);

        /** @var Server|MockObject $serverMock */
        $serverMock = $this->getMockBuilder(Server::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['loadAndCheckAssertionResponse'])
            ->getMock();
        if ($throwsException) {
            $serverMock->expects($this->atLeastOnce())->method('loadAndCheckAssertionResponse')
                ->willThrowException(new InvalidArgumentException('msg', 20));
        } else {
            $serverMock->expects($this->atLeastOnce())->method('loadAndCheckAssertionResponse');
        }

        /** @var UserEntity|MockObject $userEntity */
        $userEntity = $this->getMockBuilder(UserEntity::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Webauthn|MockObject $sut */
        $sut = $this->getMockBuilder(Webauthn::class)
            ->onlyMethods(['getUserEntityFrom', 'getServer', 'd3GetMockableRegistryObject', 'getSavedUserIdFromSession'])
            ->getMock();
        $sut->method('getUserEntityFrom')->willReturn($userEntity);
        $sut->method('getServer')->willReturn($serverMock);
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
        $sut->method('getSavedUserIdFromSession')->willReturn('userId');

        if ($throwsException) {
            $this->expectException(WebauthnGetException::class);
        }

        $return = $this->callMethod(
            $sut,
            'assertAuthn',
            ['responseString']
        );

        if (!$throwsException) {
            $this->assertTrue($return);
        }
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Model\Webauthn::getUserEntityFrom
     */
    public function cangetUserEntityFrom()
    {
        /** @var UserEntity|MockObject $userEntityMock */
        $userEntityMock = $this->getMockBuilder(UserEntity::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var User|MockObject $userMock */
        $userMock = $this->getMockBuilder(User::class)
            ->onlyMethods(['load'])
            ->getMock();
        $userMock->method('load');

        /** @var Webauthn|MockObject $sut */
        $sut = $this->getMockBuilder(Webauthn::class)
            ->onlyMethods(['d3GetMockableOxNewObject'])
            ->getMock();
        $sut->method('d3GetMockableOxNewObject')->willReturnCallback(
            function () use ($userMock, $userEntityMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case User::class:
                        return $userMock;
                    case UserEntity::class:
                        return $userEntityMock;
                    default:
                        return call_user_func_array("oxNew", $args);
                }
            }
        );

        $this->assertSame(
            $userEntityMock,
            $this->callMethod(
                $sut,
                'getUserEntityFrom',
                ['userId']
            )
        );
    }

    /**
     * @test
     * @param $isAdmin
     * @param $adminUser
     * @param $frontendUser
     * @param $expected
     * @return void
     * @throws ReflectionException
     * @dataProvider canGetSavedUserIdFromSessionDataProvider
     * @covers \D3\Webauthn\Application\Model\Webauthn::getSavedUserIdFromSession
     */
    public function canGetSavedUserIdFromSession($isAdmin, $adminUser, $frontendUser, $expected)
    {
        /** @var Session|MockObject $sessionMock */
        $sessionMock = $this->getMockBuilder(Session::class)
            ->onlyMethods(['getVariable'])
            ->getMock();
        $sessionMock->method('getVariable')->willReturnMap(
            [
                [WebauthnConf::WEBAUTHN_ADMIN_SESSION_CURRENTUSER, $adminUser],
                [WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER, $frontendUser]
            ]
        );

        /** @var Webauthn|MockObject $sut */
        $sut = $this->getMockBuilder(Webauthn::class)
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
                'getSavedUserIdFromSession'
            )
        );
    }

    /**
     * @return array[]
     */
    public function canGetSavedUserIdFromSessionDataProvider(): array
    {
        return [
            'admin'     => [true, 'admUsr', 'frontendUsr', 'admUsr'],
            'frontend'  => [false, 'admUsr', 'frontendUsr', 'frontendUsr']
        ];
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Model\Webauthn::isAdmin
     */
    public function canCheckIsAdmin()
    {
        /** @var Webauthn $sut */
        $sut = oxNew(Webauthn::class);

        $this->assertIsBool(
            $this->callMethod(
                $sut,
                'isAdmin'
            )
        );
    }

    /**
     * @test
     * @param $sessionGlobalSwitch
     * @param $configGlobalSwitch
     * @param $userUseWebauthn
     * @param $expected
     * @return void
     * @throws ReflectionException
     * @dataProvider canCheckIsActiveDataProvider
     * @covers \D3\Webauthn\Application\Model\Webauthn::isActive
     */
    public function canCheckIsActive($sessionGlobalSwitch, $configGlobalSwitch, $userUseWebauthn, $expected)
    {
        /** @var Session|MockObject $sessionMock */
        $sessionMock = $this->getMockBuilder(Session::class)
            ->onlyMethods(['getVariable'])
            ->getMock();
        $sessionMock->method('getVariable')->willReturn($sessionGlobalSwitch);

        /** @var Config|MockObject $configMock */
        $configMock = $this->getMockBuilder(Config::class)
            ->onlyMethods(['getConfigParam'])
            ->getMock();
        $configMock->method('getConfigParam')->willReturn($configGlobalSwitch);

        /** @var Webauthn|MockObject $sut */
        $sut = $this->getMockBuilder(Webauthn::class)
            ->onlyMethods(['d3GetMockableRegistryObject', 'UserUseWebauthn'])
            ->getMock();
        $sut->method('d3GetMockableRegistryObject')->willReturnCallback(
            function () use ($configMock, $sessionMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case Config::class:
                        return $configMock;
                    case Session::class:
                        return $sessionMock;
                    default:
                        return Registry::get($args[0]);
                }
            }
        );
        $sut->method('UserUseWebauthn')->willReturn($userUseWebauthn);

        $this->assertSame(
            $expected,
            $this->callMethod(
                $sut,
                'isActive',
                ['userId']
            )
        );
    }

    /**
     * @return array
     */
    public function canCheckIsActiveDataProvider(): array
    {
        return [
            'user use webauthn'                     => [false, false, true, true],
            'user use webauthn, config disabled'    => [true, false, true, false],
            'user use webauthn, session disabled'   => [false, true, true, false],
            'user use webauthn, both disabled'      => [true, true, true, false],
            'user dont use '                        => [false, false, false, false]
        ];
    }

    /**
     * @test
     * @param $credList
     * @param $expected
     * @return void
     * @throws ReflectionException
     * @dataProvider canCheckUserUseWebauthnDataProvider
     * @covers \D3\Webauthn\Application\Model\Webauthn::UserUseWebauthn
     */
    public function canCheckUserUseWebauthn($credList, $expected)
    {
        /** @var PublicKeyCredentialList|MockObject $pubKeyCredListMock */
        $pubKeyCredListMock = $this->getMockBuilder(stdClass::class)
            ->addMethods(['findAllForUserEntity'])
            ->getMock();
        $pubKeyCredListMock->method('findAllForUserEntity')->willReturn($credList);

        /** @var UserEntity|MockObject $userEntityMock */
        $userEntityMock = $this->getMockBuilder(UserEntity::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Webauthn|MockObject $sut */
        $sut = $this->getMockBuilder(Webauthn::class)
            ->onlyMethods(['getUserEntityFrom', 'd3GetMockableOxNewObject'])
            ->getMock();
        $sut->method('getUserEntityFrom')->willReturn($userEntityMock);
        $sut->method('d3GetMockableOxNewObject')->willReturnCallback(
            function () use ($pubKeyCredListMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case PublicKeyCredentialList::class:
                        return $pubKeyCredListMock;
                    default:
                        return call_user_func_array("oxNew", $args);
                }
            }
        );

        $this->assertSame(
            $expected,
            $this->callMethod(
                $sut,
                'UserUseWebauthn',
                ['userId']
            )
        );
    }

    /**
     * @return array[]
     */
    public function canCheckUserUseWebauthnDataProvider(): array
    {
        return [
            'no array'      => [null, false],
            'no count'      => [[], false],
            'filled array'  => [['abc'], true],
        ];
    }
}