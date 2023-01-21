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
use D3\Webauthn\tests\unit\WAUnitTestCase;
use Exception;
use Generator;
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

class WebauthnTest extends WAUnitTestCase
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
        d3GetOxidDIC()->set('d3ox.webauthn.'.UtilsView::class, $utilsViewMock);

        /** @var Webauthn $sut */
        $sut = oxNew(Webauthn::class);

        $this->assertSame(
            $expected,
            $this->callMethod(
                $sut,
                'isAvailable'
            )
        );
    }

    /**
     * @return Generator
     */
    public function canCheckIsAvailableDataProvider(): Generator
    {
        yield 'https'                     => ['on', null, null, null, true];
        yield 'HTTP_X_FORWARDED_PROTO'    => [null, 'https', null, null, true];
        yield 'HTTP_X_FORWARDED_SSL'      => [null, null, 'on', null, true];
        yield 'REMOTE_ADDR v4'            => [null, null, null, '127.0.0.1', true];
        yield 'REMOTE_ADDR v6'            => [null, null, null, '::1', true];
        yield 'REMOTE_ADDR localhost'     => [null, null, null, 'some.localhost', true];
        yield 'unset'                     => [null, null, null, null, false];
        yield 'not valid'                 => ['off', 'http', 'off', '160.158.23.7', false];
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
        d3GetOxidDIC()->set('d3ox.webauthn.'.Session::class, $sessionMock);

        /** @var UserEntity|MockObject $userEntityMock */
        $userEntityMock = $this->getMockBuilder(UserEntity::class)
            ->disableOriginalConstructor()
            ->getMock();
        d3GetOxidDIC()->set(UserEntity::class, $userEntityMock);

        /** @var User|MockObject $userMock */
        $userMock = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Webauthn|MockObject $sut */
        $sut = $this->getMockBuilder(Webauthn::class)
            ->onlyMethods(['getServer', 'jsonEncode', 'getExistingCredentials'])
            ->getMock();
        $sut->method('getServer')->willReturn($serverMock);
        $sut->expects($this->once())->method('jsonEncode')->willReturn($jsonReturn);
        $sut->expects($this->once())->method('getExistingCredentials')->willReturn([
            $pubKeyCredDescriptorMock,
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
     * @return Generator
     */
    public function canGetOptionsDataProvider(): Generator
    {
        yield 'json encoded'  => ['jsonstring'];
        yield 'json failed'   => [false];
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
        d3GetOxidDIC()->set(PublicKeyCredentialList::class, $pubKeyCredListMock);

        /** @var UserEntity|MockObject $userEntityMock */
        $userEntityMock = $this->getMockBuilder(UserEntity::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Webauthn $sut */
        $sut = oxNew(Webauthn::class);

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
        d3GetOxidDIC()->set('d3ox.webauthn.'.Session::class, $sessionMock);

        /** @var User|MockObject $userMock */
        $userMock = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['load'])
            ->getMock();
        $userMock->method('load')->willReturn(true);
        d3GetOxidDIC()->set('d3ox.webauthn.'.User::class, $userMock);

        /** @var UserEntity|MockObject $userEntityMock */
        $userEntityMock = $this->getMockBuilder(UserEntity::class)
            ->disableOriginalConstructor()
            ->getMock();
        d3GetOxidDIC()->set(UserEntity::class, $userEntityMock);

        /** @var Webauthn|MockObject $sut */
        $sut = $this->getMockBuilder(Webauthn::class)
            ->onlyMethods(['getServer', 'jsonEncode',
                'getExistingCredentials',
            ])
            ->getMock();
        $sut->method('getServer')->willReturn($serverMock);
        $sut->expects($this->once())->method('jsonEncode')->willReturn($jsonReturn);
        $sut->expects($this->once())->method('getExistingCredentials')->willReturn([
            $pubKeyCredDescriptorMock,
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
        /** @var Server|MockObject $serverMock */
        $serverMock = $this->getMockBuilder(Server::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setLogger'])
            ->getMock();
        $serverMock->expects($this->atLeastOnce())->method('setLogger');

        /** @var Webauthn|MockObject $sut */
        $sut = $this->getMockBuilder(Webauthn::class)
            ->onlyMethods(['getServerObject'])
            ->getMock();
        $sut->method('getServerObject')->willReturn($serverMock);

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
        d3GetOxidDIC()->set(PublicKeyCredential::class, $pubKeyCredMock);

        /** @var Session|MockObject $sessionMock */
        $sessionMock = $this->getMockBuilder(Session::class)
            ->onlyMethods(['getVariable'])
            ->getMock();
        $sessionMock->method('getVariable')->willReturn($pubKeyCredCreationsOptionsMock);
        d3GetOxidDIC()->set('d3ox.webauthn.'.Session::class, $sessionMock);

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
            ->onlyMethods(['getServer'])
            ->getMock();
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
     * @return Generator
     */
    public function loadAndCheckAssertionResponseDataProvider(): Generator
    {
        yield 'check failed'  => [true];
        yield 'check passed'  => [false];
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
        d3GetOxidDIC()->set('d3ox.webauthn.'.Session::class, $sessionMock);

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
            ->onlyMethods(['getUserEntityFrom', 'getServer', 'getSavedUserIdFromSession'])
            ->getMock();
        $sut->method('getUserEntityFrom')->willReturn($userEntity);
        $sut->method('getServer')->willReturn($serverMock);
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
        d3GetOxidDIC()->set(UserEntity::class, $userEntityMock);

        /** @var User|MockObject $userMock */
        $userMock = $this->getMockBuilder(User::class)
            ->onlyMethods(['load'])
            ->getMock();
        $userMock->method('load');
        d3GetOxidDIC()->set('d3ox.webauthn.'.User::class, $userMock);

        /** @var Webauthn|MockObject $sut */
        $sut = $this->getMockBuilder(Webauthn::class)
            ->getMock();

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
                [WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER, $frontendUser],
            ]
        );
        d3GetOxidDIC()->set('d3ox.webauthn.'.Session::class, $sessionMock);

        /** @var Webauthn|MockObject $sut */
        $sut = $this->getMockBuilder(Webauthn::class)
            ->onlyMethods(['isAdmin'])
            ->getMock();
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
     * @return Generator
     */
    public function canGetSavedUserIdFromSessionDataProvider(): Generator
    {
        yield 'admin'     => [true, 'admUsr', 'frontendUsr', 'admUsr'];
        yield 'frontend'  => [false, 'admUsr', 'frontendUsr', 'frontendUsr'];
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
        d3GetOxidDIC()->set('d3ox.webauthn.'.Session::class, $sessionMock);

        /** @var Config|MockObject $configMock */
        $configMock = $this->getMockBuilder(Config::class)
            ->onlyMethods(['getConfigParam'])
            ->getMock();
        $configMock->method('getConfigParam')->willReturn($configGlobalSwitch);
        d3GetOxidDIC()->set('d3ox.webauthn.'.Config::class, $configMock);

        /** @var Webauthn|MockObject $sut */
        $sut = $this->getMockBuilder(Webauthn::class)
            ->onlyMethods(['UserUseWebauthn'])
            ->getMock();
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
     * @return Generator
     */
    public function canCheckIsActiveDataProvider(): Generator
    {
        yield 'user use webauthn'                     => [false, false, true, true];
        yield 'user use webauthn, config disabled'    => [true, false, true, false];
        yield 'user use webauthn, session disabled'   => [false, true, true, false];
        yield 'user use webauthn, both disabled'      => [true, true, true, false];
        yield 'user dont use '                        => [false, false, false, false];
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
        d3GetOxidDIC()->set(PublicKeyCredentialList::class, $pubKeyCredListMock);

        /** @var UserEntity|MockObject $userEntityMock */
        $userEntityMock = $this->getMockBuilder(UserEntity::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Webauthn|MockObject $sut */
        $sut = $this->getMockBuilder(Webauthn::class)
            ->onlyMethods(['getUserEntityFrom'])
            ->getMock();
        $sut->method('getUserEntityFrom')->willReturn($userEntityMock);

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
     * @return Generator
     */
    public function canCheckUserUseWebauthnDataProvider(): Generator
    {
        yield 'no array'      => [null, false];
        yield 'no count'      => [[], false];
        yield 'filled array'  => [['abc'], true];
    }

    /**
     * @test
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Model\Webauthn::getServerObject
     */
    public function canGetServerObject()
    {
        /** @var PublicKeyCredentialList|MockObject $pubKeyCredListMock */
        $pubKeyCredListMock = $this->getMockBuilder(PublicKeyCredentialList::class)
            ->disableOriginalConstructor()
            ->getMock();
        d3GetOxidDIC()->set(PublicKeyCredentialList::class, $pubKeyCredListMock);

        /** @var RelyingPartyEntity|MockObject $rpEntityMock */
        $rpEntityMock = $this->getMockBuilder(RelyingPartyEntity::class)
            ->disableOriginalConstructor()
            ->getMock();
        d3GetOxidDIC()->set(RelyingPartyEntity::class, $rpEntityMock);

        /** @var Webauthn $sut */
        $sut = oxNew(Webauthn::class);

        $this->assertInstanceOf(
            Server::class,
            $this->callMethod(
                $sut,
                'getServerObject'
            )
        );
    }
}
