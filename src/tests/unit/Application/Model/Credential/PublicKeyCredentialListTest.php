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

namespace D3\Webauthn\tests\unit\Application\Model\Credential;

use D3\TestingTools\Development\CanAccessRestricted;
use D3\Webauthn\Application\Model\Credential\PublicKeyCredential;
use D3\Webauthn\Application\Model\Credential\PublicKeyCredentialList;
use D3\Webauthn\Application\Model\UserEntity;
use D3\Webauthn\tests\unit\WAUnitTestCase;
use Hoa\Iterator\Mock;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\TestingLibrary\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;
use Webauthn\PublicKeyCredentialSource;

class PublicKeyCredentialListTest extends WAUnitTestCase
{
    use CanAccessRestricted;

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Model\Credential\PublicKeyCredentialList::__construct
     */
    public function canConstruct()
    {
        /** @var PublicKeyCredentialList|MockObject $sut */
        $sut = $this->getMockBuilder(PublicKeyCredentialList::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['d3CallMockableFunction'])
            ->getMock();
        $sut->expects($this->once())->method('d3CallMockableFunction')->with(
            $this->anything(),
            $this->containsIdentical(PublicKeyCredential::class)
        );

        $this->callMethod(
            $sut,
            '__construct'
        );
    }

    /**
     * @test
     * @param $doCreate
     * @param $expected
     * @return void
     * @throws ReflectionException
     * @dataProvider canFindOneByCredentialIdDataProvider
     * @covers \D3\Webauthn\Application\Model\Credential\PublicKeyCredentialList::findOneByCredentialId
     */
    public function canFindOneByCredentialId($doCreate, $expected)
    {
        $oxid = 'idFixture';

        /** @var Config|MockObject $configMock */
        $configMock = $this->getMockBuilder(Config::class)
            ->onlyMethods(['getShopId'])
            ->getMock();
        $configMock->method('getShopId')->willReturn(55);
        d3GetOxidDIC()->set('d3ox.webauthn.'.Config::class, $configMock);

        /** @var PublicKeyCredentialList|MockObject $sut */
        $sut = $this->getMockBuilder(PublicKeyCredentialList::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findAllForUserEntity'])
            ->getMock();

        if ($doCreate) {
            /** @var PublicKeyCredential|MockObject $pkc */
            $pkc = $this->getMockBuilder(PublicKeyCredential::class)
                ->onlyMethods(['allowDerivedDelete'])
                ->getMock();
            $pkc->method('allowDerivedDelete')->willReturn(true);
            $pkc->setId($oxid);
            $pkc->assign([
                'credentialid'  => base64_encode('myCredentialId'),
                'oxshopid'      => 55,
                'name'  => __METHOD__,
                // can't get mock of PublicKeyCredentialSource because of cascaded mock object is not serializable
                'credential'    => 'TzozNDoiV2ViYXV0aG5cUHVibGljS2V5Q3JlZGVudGlhbFNvdXJjZSI6MTA6e3M6MjQ6IgAqAHB1YmxpY0tleUNyZWRlbnRpYWxJZCI7czo2NToiAQUtRW3vxImpllhVhp3sUeC0aBae8rFm0hBhHpVSdkdrmqZp+tnfgcuP8xJUbsjMMDyt908zZ2RXAtibmbbilOciO3M6NzoiACoAdHlwZSI7czoxMDoicHVibGljLWtleSI7czoxMzoiACoAdHJhbnNwb3J0cyI7YTowOnt9czoxODoiACoAYXR0ZXN0YXRpb25UeXBlIjtzOjQ6Im5vbmUiO3M6MTI6IgAqAHRydXN0UGF0aCI7TzozMzoiV2ViYXV0aG5cVHJ1c3RQYXRoXEVtcHR5VHJ1c3RQYXRoIjowOnt9czo5OiIAKgBhYWd1aWQiO086MzU6IlJhbXNleVxVdWlkXExhenlcTGF6eVV1aWRGcm9tU3RyaW5nIjoxOntzOjY6InN0cmluZyI7czozNjoiMDAwMDAwMDAtMDAwMC0wMDAwLTAwMDAtMDAwMDAwMDAwMDAwIjt9czoyMjoiACoAY3JlZGVudGlhbFB1YmxpY0tleSI7czo3NzoipQECAyYgASFYIKelzI2/b094o/XiJmXWUkVr8cvhAucLplHTxtl0oKtrIlgguKi+0epmmjeemuzzGspNotA7uKnkk4oAmDUOKsJgLykiO3M6MTM6IgAqAHVzZXJIYW5kbGUiO3M6MTQ6Im94ZGVmYXVsdGFkbWluIjtzOjEwOiIAKgBjb3VudGVyIjtpOjA7czoxMDoiACoAb3RoZXJVSSI7Tjt9'
            ]);
            $pkc->save();
        }

        try {
            $return = $this->callMethod(
                $sut,
                'findOneByCredentialId',
                ['myCredentialId']
            );

            if ($expected === 'pkcsource') {
                $this->assertInstanceOf(
                    PublicKeyCredentialSource::class,
                    $return
                );
            } else {
                $this->assertEquals(
                    $expected,
                    $return
                );
            }
        } finally {
            if ($doCreate) {
                $pkc->delete($oxid);
            }
        }
    }

    /**
     * @return array
     */
    public function canFindOneByCredentialIdDataProvider(): array
    {
        return [
            'existing'      => [true, 'pkcsource'],
            'not existing'  => [false, null],
        ];
    }

    /**
     * @test
     * @param $doCreate
     * @param $expected
     * @return void
     * @throws ReflectionException
     * @dataProvider canFindOneByCredentialIdDataProvider
     * @covers \D3\Webauthn\Application\Model\Credential\PublicKeyCredentialList::findAllForUserEntity()
     */
    public function canFindAllForUserEntity($doCreate, $expected)
    {
        $oxids = ['idFixture1','idFixture2'];

        /** @var Config|MockObject $configMock */
        $configMock = $this->getMockBuilder(Config::class)
            ->onlyMethods(['getShopId'])
            ->getMock();
        $configMock->method('getShopId')->willReturn(55);
        d3GetOxidDIC()->set('d3ox.webauthn.'.Config::class, $configMock);

        /** @var PublicKeyCredentialList|MockObject $sut */
        $sut = $this->getMockBuilder(PublicKeyCredentialList::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findOneByCredentialId'])
            ->getMock();

        if ($doCreate) {
            foreach ($oxids as $oxid) {
                $pkc = $this->getMockBuilder(PublicKeyCredential::class)
                    ->onlyMethods(['allowDerivedDelete'])
                    ->getMock();
                $pkc->method('allowDerivedDelete')->willReturn(true);
                $pkc->setId($oxid);
                $pkc->assign([
                    'oxuserid' => 'userid',
                    'oxshopid' => 55,
                    'credentialid'  => __METHOD__,
                    // can't get mock of PublicKeyCredentialSource because of cascaded mock object is not serializable
                    'credential'    => 'TzozNDoiV2ViYXV0aG5cUHVibGljS2V5Q3JlZGVudGlhbFNvdXJjZSI6MTA6e3M6MjQ6IgAqAHB1YmxpY0tleUNyZWRlbnRpYWxJZCI7czo2NToiAQUtRW3vxImpllhVhp3sUeC0aBae8rFm0hBhHpVSdkdrmqZp+tnfgcuP8xJUbsjMMDyt908zZ2RXAtibmbbilOciO3M6NzoiACoAdHlwZSI7czoxMDoicHVibGljLWtleSI7czoxMzoiACoAdHJhbnNwb3J0cyI7YTowOnt9czoxODoiACoAYXR0ZXN0YXRpb25UeXBlIjtzOjQ6Im5vbmUiO3M6MTI6IgAqAHRydXN0UGF0aCI7TzozMzoiV2ViYXV0aG5cVHJ1c3RQYXRoXEVtcHR5VHJ1c3RQYXRoIjowOnt9czo5OiIAKgBhYWd1aWQiO086MzU6IlJhbXNleVxVdWlkXExhenlcTGF6eVV1aWRGcm9tU3RyaW5nIjoxOntzOjY6InN0cmluZyI7czozNjoiMDAwMDAwMDAtMDAwMC0wMDAwLTAwMDAtMDAwMDAwMDAwMDAwIjt9czoyMjoiACoAY3JlZGVudGlhbFB1YmxpY0tleSI7czo3NzoipQECAyYgASFYIKelzI2/b094o/XiJmXWUkVr8cvhAucLplHTxtl0oKtrIlgguKi+0epmmjeemuzzGspNotA7uKnkk4oAmDUOKsJgLykiO3M6MTM6IgAqAHVzZXJIYW5kbGUiO3M6MTQ6Im94ZGVmYXVsdGFkbWluIjtzOjEwOiIAKgBjb3VudGVyIjtpOjA7czoxMDoiACoAb3RoZXJVSSI7Tjt9'
                ]);
                $pkc->save();
            }
        }

        /** @var UserEntity|MockObject $pkcUserEntity */
        $pkcUserEntity = $this->getMockBuilder(UserEntity::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();
        $pkcUserEntity->method('getId')->willReturn('userid');

        try {
            $list = $this->callMethod(
                $sut,
                'findAllForUserEntity',
                [$pkcUserEntity]
            );

            if ($expected === 'pkcsource') {
                $this->assertCount( 2, $list );
                foreach ( $list as $item ) {
                    $this->assertInstanceOf( PublicKeyCredentialSource::class, $item );
                }
            } else {
                $this->assertEmpty($list);
            }
        } finally {
            if ($doCreate) {
                foreach ($oxids as $oxid) {
                    /** @var PublicKeyCredential $pkc */
                    $pkc = $this->getMockBuilder(PublicKeyCredential::class)
                        ->onlyMethods(['allowDerivedDelete'])
                        ->getMock();
                    $pkc->method('allowDerivedDelete')->willReturn(true);
                    $pkc->delete($oxid);
                }
            }
        }
    }

    /**
     * @test
     * @param $userLoaded
     * @param $doCreate
     * @param $expectedCount
     * @return void
     * @throws ReflectionException
     * @dataProvider canGetAllFromUserDataProvider
     * @covers       \D3\Webauthn\Application\Model\Credential\PublicKeyCredentialList::getAllFromUser()
     */
    public function canGetAllFromUser($userLoaded, $doCreate, $expectedCount)
    {
        $oxids = ['idFixture1','idFixture2'];

        /** @var Config|MockObject $configMock */
        $configMock = $this->getMockBuilder(Config::class)
            ->onlyMethods(['getShopId'])
            ->getMock();
        $configMock->method('getShopId')->willReturn(55);
        d3GetOxidDIC()->set('d3ox.webauthn.'.Config::class, $configMock);

        /** @var PublicKeyCredentialList|MockObject $sut */
        $sut = $this->getMockBuilder(PublicKeyCredentialList::class)
            ->onlyMethods(['saveCredentialSource'])
            ->disableOriginalConstructor()
            ->getMock();

        /** @var User|MockObject $userMock */
        $userMock = $this->getMockBuilder(User::class)
            ->onlyMethods(['isLoaded', 'getId'])
            ->getMock();
        $userMock->method('isLoaded')->willReturn($userLoaded);
        $userMock->method('getId')->willReturn('userid');

        if ($doCreate) {
            foreach ($oxids as $oxid) {
                $pkc = $this->getMockBuilder(PublicKeyCredential::class)
                    ->onlyMethods(['allowDerivedDelete'])
                    ->getMock();
                $pkc->method('allowDerivedDelete')->willReturn(true);
                $pkc->setId($oxid);
                $pkc->assign([
                    'oxuserid' => 'userid',
                    'oxshopid' => 55,
                    'credentialid'  => __METHOD__,
                    // can't get mock of PublicKeyCredentialSource because of cascaded mock object is not serializable
                    'credential'    => 'TzozNDoiV2ViYXV0aG5cUHVibGljS2V5Q3JlZGVudGlhbFNvdXJjZSI6MTA6e3M6MjQ6IgAqAHB1YmxpY0tleUNyZWRlbnRpYWxJZCI7czo2NToiAQUtRW3vxImpllhVhp3sUeC0aBae8rFm0hBhHpVSdkdrmqZp+tnfgcuP8xJUbsjMMDyt908zZ2RXAtibmbbilOciO3M6NzoiACoAdHlwZSI7czoxMDoicHVibGljLWtleSI7czoxMzoiACoAdHJhbnNwb3J0cyI7YTowOnt9czoxODoiACoAYXR0ZXN0YXRpb25UeXBlIjtzOjQ6Im5vbmUiO3M6MTI6IgAqAHRydXN0UGF0aCI7TzozMzoiV2ViYXV0aG5cVHJ1c3RQYXRoXEVtcHR5VHJ1c3RQYXRoIjowOnt9czo5OiIAKgBhYWd1aWQiO086MzU6IlJhbXNleVxVdWlkXExhenlcTGF6eVV1aWRGcm9tU3RyaW5nIjoxOntzOjY6InN0cmluZyI7czozNjoiMDAwMDAwMDAtMDAwMC0wMDAwLTAwMDAtMDAwMDAwMDAwMDAwIjt9czoyMjoiACoAY3JlZGVudGlhbFB1YmxpY0tleSI7czo3NzoipQECAyYgASFYIKelzI2/b094o/XiJmXWUkVr8cvhAucLplHTxtl0oKtrIlgguKi+0epmmjeemuzzGspNotA7uKnkk4oAmDUOKsJgLykiO3M6MTM6IgAqAHVzZXJIYW5kbGUiO3M6MTQ6Im94ZGVmYXVsdGFkbWluIjtzOjEwOiIAKgBjb3VudGVyIjtpOjA7czoxMDoiACoAb3RoZXJVSSI7Tjt9'
                ]);
                $pkc->save();
            }
        }

        try {
            $return = $this->callMethod(
                $sut,
                'getAllFromUser',
                [$userMock]
            );
        } finally {
            if ($doCreate) {
                foreach ($oxids as $oxid) {
                    /** @var PublicKeyCredential|MockObject $pkc */
                    $pkc = $this->getMockBuilder(PublicKeyCredential::class)
                        ->onlyMethods(['allowDerivedDelete'])
                        ->getMock();
                    $pkc->method('allowDerivedDelete')->willReturn(true);
                    $pkc->delete($oxid);
                }
            }
        }

        $this->assertInstanceOf(PublicKeyCredentialList::class, $return);

        $this->assertCount($expectedCount, $return);
    }

    /**
     * @return array
     */
    public function canGetAllFromUserDataProvider(): array
    {
        return [
            'no user loaded'    => [false, false, 0],
            'empty list'        => [true, false, 0],
            'filled list'       => [true, true, 2],
        ];
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Model\Credential\PublicKeyCredentialList::saveCredentialSource()
     */
    public function canSaveCredentialSource()
    {
        /** @var PublicKeyCredentialSource|MockObject $pkcsMock */
        $pkcsMock = $this->getMockBuilder(PublicKeyCredentialSource::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var PublicKeyCredential|MockObject $baseObjectMock */
        $baseObjectMock = $this->getMockBuilder(PublicKeyCredential::class)
            ->onlyMethods(['saveCredentialSource'])
            ->getMock();
        $baseObjectMock->expects($this->once())->method('saveCredentialSource')
            ->with($this->identicalTo($pkcsMock));

        /** @var PublicKeyCredentialList|MockObject $sut */
        $sut = $this->getMockBuilder(PublicKeyCredentialList::class)
            ->onlyMethods(['getBaseObject'])
            ->getMock();
        $sut->method('getBaseObject')->willReturn($baseObjectMock);

        $this->callMethod(
            $sut,
            'saveCredentialSource',
            [$pkcsMock]
        );
    }
}
