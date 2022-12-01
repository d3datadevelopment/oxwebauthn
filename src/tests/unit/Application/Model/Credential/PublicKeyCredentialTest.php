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
use OxidEsales\Eshop\Core\Config;
use OxidEsales\TestingLibrary\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;
use Webauthn\PublicKeyCredentialSource;

class PublicKeyCredentialTest extends UnitTestCase
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
     * @param $value
     * @return void
     * @throws ReflectionException
     */
    public function canGetField($fieldName, $sutMethod, $setValue, $getValue)
    {
        /** @var PublicKeyCredential $sut */
        $sut = oxNew(PublicKeyCredential::class);
        $sut->assign([
            $fieldName  => $setValue
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
        /** @var PublicKeyCredentialSource $publicKeyCredentialSourceMock */
        $publicKeyCredentialSourceMock = $this->getMockBuilder(PublicKeyCredentialSource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->canGetField(
            'credential',
            'getCredential',
            base64_encode(serialize($publicKeyCredentialSourceMock)),
            $publicKeyCredentialSourceMock
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

        /** @var PublicKeyCredentialList|MockObject $pkcListObjectMock */
        $pkcListObjectMock = $this->getMockBuilder(PublicKeyCredentialList::class)
            ->onlyMethods(['findOneByCredentialId'])
            ->getMock();
        $pkcListObjectMock->method('findOneByCredentialId')->willReturn(
            $credAlreadyExist ? $publicKeyCredentialSourceMock : null
        );

        /** @var PublicKeyCredential|MockObject $sut */
        $sut = $this->getMockBuilder(PublicKeyCredential::class)
            ->onlyMethods(['getPublicKeyCredentialListObject', 'exists', 'getIdByCredentialId', 'load', 'save'])
            ->getMock();
        $sut->method('getPublicKeyCredentialListObject')->willReturn($pkcListObjectMock);
        $sut->method('exists')->willReturn($credIdExist);
        $sut->expects($this->exactly((int) $doSave))->method('getIdByCredentialId');
        $sut->expects($this->exactly((int) ($doSave && $credIdExist)))->method('load');
        $sut->expects($this->exactly((int) $doSave))->method('save');

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
     * @covers \D3\Webauthn\Application\Model\Credential\PublicKeyCredential::getPublicKeyCredentialListObject
     */
    public function canGetPublicKeyCredentialListObject()
    {
        /** @var PublicKeyCredential|MockObject $sut */
        $sut = $this->getMockBuilder(PublicKeyCredential::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertInstanceOf(
            PublicKeyCredentialList::class,
            $this->callMethod(
                $sut,
                'getPublicKeyCredentialListObject'
            )
        );
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

        /** @var PublicKeyCredential|MockObject $sut */
        $sut = $this->getMockBuilder(PublicKeyCredential::class)
            ->onlyMethods(['d3GetConfig', 'allowDerivedDelete'])
            ->getMock();
        $sut->method('d3GetConfig')->willReturn($configMock);
        $sut->method('allowDerivedDelete')->willReturn(true);

        if ($doCreate) {
            $sut->setId($oxid);
            $sut->assign([
                'credentialid' => base64_encode($pkcId),
                'oxshopid' => $shopId
            ]);
            $sut->save();
        }

        $this->assertSame(
            $expected,
            $this->callMethod(
                $sut,
                'getIdByCredentialId',
                [$pkcId]
            )
        );

        if ($doCreate) {
            $sut->delete($oxid);
        }
    }

    /**
     * @return array
     */
    public function canGetIdByCredentialIdDataProvider(): array
    {
        return [
            'item exists'       => [true, 'idFixture'],
            'item not exists'   => [false, null]
        ];
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Model\Credential\PublicKeyCredential::d3GetConfig
     */
    public function canGetConfig()
    {
        /** @var PublicKeyCredential $sut */
        $sut = oxNew(PublicKeyCredential::class);

        $this->assertInstanceOf(
            Config::class,
            $this->callMethod(
                $sut,
                'd3GetConfig'
            )
        );
    }
}