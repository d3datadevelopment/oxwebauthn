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
use D3\Webauthn\tests\unit\WAUnitTestCase;
use OxidEsales\Eshop\Core\Config;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;
use Webauthn\PublicKeyCredentialSource;

class PublicKeyCredentialTest extends WAUnitTestCase
{
    use CanAccessRestricted;

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Model\Credential\PublicKeyCredential::__construct
     */
    public function canConstruct()
    {
        /** @var PublicKeyCredential|MockObject $sut */
        $sut = $this->getMockBuilder(PublicKeyCredential::class)
            ->onlyMethods(['init'])
            ->disableOriginalConstructor()
            ->getMock();
        $sut->expects($this->atLeastOnce())->method('init')->willReturn(true);

        $this->callMethod(
            $sut,
            '__construct'
        );
    }

    /**
     * @param $fieldName
     * @param $sutMethod
     * @param $value
     * @return void
     * @throws ReflectionException
     */
    public function canSetField($fieldName, $sutMethod, $value)
    {
        /** @var PublicKeyCredential|MockObject $sut */
        $sut = $this->getMockBuilder(PublicKeyCredential::class)
            ->onlyMethods(['assign'])
            ->getMock();
        $sut->expects($this->atLeastOnce())->method('assign')
            ->with($this->arrayHasKey($fieldName))->willReturn(true);

        $this->callMethod(
            $sut,
            $sutMethod,
            [$value]
        );
    }

    /**
     * @param $fieldName
     * @param $sutMethod
     * @param $setValue
     * @param $getValue
     * @return void
     * @throws ReflectionException
     */
    public function canGetField($fieldName, $sutMethod, $setValue, $getValue)
    {
        /** @var PublicKeyCredential $sut */
        $sut = oxNew(PublicKeyCredential::class);
        $sut->assign([
            $fieldName  => $setValue,
        ]);

        $this->assertEquals(
            $getValue,
            $this->callMethod(
                $sut,
                $sutMethod
            )
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Model\Credential\PublicKeyCredential::setName
     */
    public function canSetName()
    {
        $this->canSetField('name', 'setName', 'nameFixture');
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Model\Credential\PublicKeyCredential::getName
     */
    public function canGetName()
    {
        $this->canGetField('name', 'getName', 'nameFixture', 'nameFixture');
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Model\Credential\PublicKeyCredential::setCredentialId
     */
    public function canSetCredentialId()
    {
        $this->canSetField('credentialid', 'setCredentialId', 'credentialFixture');
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Model\Credential\PublicKeyCredential::getCredentialId
     */
    public function canGetCredentialId()
    {
        $this->canGetField(
            'credentialid',
            'getCredentialId',
            'credentialFixture',
            base64_decode('credentialFixture')
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Model\Credential\PublicKeyCredential::setUserId
     */
    public function canSetUserId()
    {
        $this->canSetField('oxuserid', 'setUserId', 'userIdFixture');
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Model\Credential\PublicKeyCredential::getUserId
     */
    public function canGetUserId()
    {
        $this->canGetField('oxuserid', 'getUserId', 'userIdFixture', 'userIdFixture');
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Model\Credential\PublicKeyCredential::setCredential
     */
    public function canSetCredential()
    {
        /** @var PublicKeyCredentialSource $publicKeyCredentialSourceMock */
        $publicKeyCredentialSourceMock = $this->getMockBuilder(PublicKeyCredentialSource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->canSetField('credential', 'setCredential', $publicKeyCredentialSourceMock);
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Model\Credential\PublicKeyCredential::getCredential
     */
    public function canGetCredential()
    {
        // can't get mock of PublicKeyCredentialSource because of cascaded mock object is not serializable

        /** @var PublicKeyCredential $sut */
        $sut = oxNew(PublicKeyCredential::class);
        $sut->assign([
            'credential'  => 'TzozNDoiV2ViYXV0aG5cUHVibGljS2V5Q3JlZGVudGlhbFNvdXJjZSI6MTA6e3M6MjQ6IgAqAHB1YmxpY0tleUNyZWRlbnRpYWxJZCI7czo2NToiAQUtRW3vxImpllhVhp3sUeC0aBae8rFm0hBhHpVSdkdrmqZp+tnfgcuP8xJUbsjMMDyt908zZ2RXAtibmbbilOciO3M6NzoiACoAdHlwZSI7czoxMDoicHVibGljLWtleSI7czoxMzoiACoAdHJhbnNwb3J0cyI7YTowOnt9czoxODoiACoAYXR0ZXN0YXRpb25UeXBlIjtzOjQ6Im5vbmUiO3M6MTI6IgAqAHRydXN0UGF0aCI7TzozMzoiV2ViYXV0aG5cVHJ1c3RQYXRoXEVtcHR5VHJ1c3RQYXRoIjowOnt9czo5OiIAKgBhYWd1aWQiO086MzU6IlJhbXNleVxVdWlkXExhenlcTGF6eVV1aWRGcm9tU3RyaW5nIjoxOntzOjY6InN0cmluZyI7czozNjoiMDAwMDAwMDAtMDAwMC0wMDAwLTAwMDAtMDAwMDAwMDAwMDAwIjt9czoyMjoiACoAY3JlZGVudGlhbFB1YmxpY0tleSI7czo3NzoipQECAyYgASFYIKelzI2/b094o/XiJmXWUkVr8cvhAucLplHTxtl0oKtrIlgguKi+0epmmjeemuzzGspNotA7uKnkk4oAmDUOKsJgLykiO3M6MTM6IgAqAHVzZXJIYW5kbGUiO3M6MTQ6Im94ZGVmYXVsdGFkbWluIjtzOjEwOiIAKgBjb3VudGVyIjtpOjA7czoxMDoiACoAb3RoZXJVSSI7Tjt9',
        ]);

        $this->assertInstanceOf(
            PublicKeyCredentialSource::class,
            $this->callMethod(
                $sut,
                'getCredential'
            )
        );
    }

    /**
     * @test
     * @param $credAlreadyExist
     * @param $credIdExist
     * @param $keyName
     * @param $doSave
     * @return void
     * @throws ReflectionException
     * @dataProvider canSaveCredentialSourceDataProvider
     * @covers \D3\Webauthn\Application\Model\Credential\PublicKeyCredential::saveCredentialSource
     */
    public function canSaveCredentialSource($credAlreadyExist, $credIdExist, $keyName, $doSave)
    {
        /** @var PublicKeyCredentialSource|MockObject $publicKeyCredentialSourceMock */
        $publicKeyCredentialSourceMock = $this->getMockBuilder(PublicKeyCredentialSource::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var PublicKeyCredentialListTest|MockObject $pkcListObjectMock */
        $pkcListObjectMock = $this->getMockBuilder(PublicKeyCredentialList::class)
            ->onlyMethods(['findOneByCredentialId'])
            ->getMock();
        $pkcListObjectMock->method('findOneByCredentialId')->willReturn(
            $credAlreadyExist ? $publicKeyCredentialSourceMock : null
        );
        d3GetOxidDIC()->set(PublicKeyCredentialList::class, $pkcListObjectMock);

        /** @var PublicKeyCredential|MockObject $sut */
        $sut = $this->getMockBuilder(PublicKeyCredential::class)
            ->onlyMethods(['exists', 'getIdByCredentialId', 'load', 'save', 'setCredential', 'setCredentialId'])
            ->getMock();
        $sut->method('exists')->willReturn($credIdExist);
        $sut->expects($this->exactly((int) $doSave))->method('getIdByCredentialId');
        $sut->expects($this->exactly((int) ($doSave && $credIdExist)))->method('load');
        $sut->expects($this->exactly((int) $doSave))->method('save');
        $sut->expects($this->exactly((int) $doSave))->method('setCredential');
        $sut->expects($this->exactly((int) $doSave))->method('setCredentialId');

        $this->callMethod(
            $sut,
            'saveCredentialSource',
            [$publicKeyCredentialSourceMock, $keyName]
        );

        if ($doSave) {
            $this->assertNotNull(
                $sut->getName()
            );
        }
    }

    /**
     * @return array
     */
    public function canSaveCredentialSourceDataProvider(): array
    {
        return [
            'credential already exist'      => [true, false, null, false],
            'credential id not exist'       => [false, false, 'keyName', true],
            'credential id already exist'   => [false, true, 'keyName', true],
            'save with no key name'         => [false, false, null, true],
        ];
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @dataProvider canGetIdByCredentialIdDataProvider
     * @covers \D3\Webauthn\Application\Model\Credential\PublicKeyCredential::getIdByCredentialId
     */
    public function canGetIdByCredentialId($doCreate, $expected)
    {
        $oxid = 'idFixture';
        $pkcId = 'CredIdFixture';
        $shopId = 55;

        /** @var Config|MockObject $configMock */
        $configMock = $this->getMockBuilder(Config::class)
            ->onlyMethods(['getShopId'])
            ->getMock();
        $configMock->method('getShopId')->willReturn($shopId);
        d3GetOxidDIC()->set('d3ox.webauthn.'.Config::class, $configMock);

        /** @var PublicKeyCredential|MockObject $sut */
        $sut = $this->getMockBuilder(PublicKeyCredential::class)
            ->onlyMethods(['allowDerivedDelete'])
            ->getMock();
        $sut->method('allowDerivedDelete')->willReturn(true);

        if ($doCreate) {
            $sut->setId($oxid);
            $sut->assign([
                'credentialid' => base64_encode($pkcId),
                'oxshopid' => $shopId,
            ]);
            $sut->save();
        }

        try {
            $this->assertSame(
                $expected,
                $this->callMethod(
                    $sut,
                    'getIdByCredentialId',
                    [$pkcId]
                )
            );
        } finally {
            if ($doCreate) {
                $sut->delete($oxid);
            }
        }
    }

    /**
     * @return array
     */
    public function canGetIdByCredentialIdDataProvider(): array
    {
        return [
            'item exists'       => [true, 'idFixture'],
            'item not exists'   => [false, null],
        ];
    }
}
