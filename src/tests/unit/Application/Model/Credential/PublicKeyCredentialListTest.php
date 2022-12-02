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
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Config;
use OxidEsales\TestingLibrary\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;

class PublicKeyCredentialListTest extends UnitTestCase
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
            ->onlyMethods(['d3CallMockableParent'])
            ->getMock();
        $sut->expects($this->once())->method('d3CallMockableParent')->with(
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

        /** @var PublicKeyCredentialList|MockObject $sut */
        $sut = $this->getMockBuilder(PublicKeyCredentialList::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConfigObject'])
            ->getMock();
        $sut->method('getConfigObject')->willReturn($configMock);

        if ($doCreate) {
            $pkcsMock = $this->getMockBuilder(PublicKeyCredentialSource::class)
                ->disableOriginalConstructor()
                ->getMock();
            $pkc = $this->getMockBuilder(PublicKeyCredential::class)
                ->onlyMethods(['allowDerivedDelete'])
                ->getMock();
            $pkc->method('allowDerivedDelete')->willReturn(true);
            $pkc->setId($oxid);
            $pkc->assign([
                'credentialid'  => base64_encode('myCredentialId'),
                'oxshopid'      => 55
            ]);
            $pkc->setCredential($pkcsMock);
            $pkc->save();
        }

        $this->assertEquals(
            $expected === 'pkcsource' ? $pkcsMock : $expected,
            $this->callMethod(
                $sut,
                'findOneByCredentialId',
                ['myCredentialId']
            )
        );

        if ($doCreate) {
            $pkc->delete($oxid);
        }
    }

    /**
     * @return array
     */
    public function canFindOneByCredentialIdDataProvider(): array
    {
        return [
            'existing'      => [true, 'pkcsource'],
            'not existing'  => [false, null]
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

        /** @var PublicKeyCredentialList|MockObject $sut */
        $sut = $this->getMockBuilder(PublicKeyCredentialList::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConfigObject'])
            ->getMock();
        $sut->method('getConfigObject')->willReturn($configMock);

        if ($doCreate) {
            $pkcsMock = $this->getMockBuilder(PublicKeyCredentialSource::class)
                ->disableOriginalConstructor()
                ->getMock();
            foreach ($oxids as $oxid) {
                $pkc = $this->getMockBuilder(PublicKeyCredential::class)
                    ->onlyMethods(['allowDerivedDelete'])
                    ->getMock();
                $pkc->method('allowDerivedDelete')->willReturn(true);
                $pkc->setId($oxid);
                $pkc->assign([
                    'oxuserid' => 'userid',
                    'oxshopid' => 55
                ]);
                $pkc->setCredential($pkcsMock);
                $pkc->save();
            }
        }

        /** @var UserEntity|MockObject $pkcUserEntity */
        $pkcUserEntity = $this->getMockBuilder(UserEntity::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();
        $pkcUserEntity->method('getId')->willReturn('userid');

        $this->assertEquals(
            $expected === 'pkcsource' ? [$pkcsMock, $pkcsMock] : [],
            $this->callMethod(
                $sut,
                'findAllForUserEntity',
                [$pkcUserEntity]
            )
        );

        if ($doCreate) {
            foreach ($oxids as $oxid) {
                $pkc->delete($oxid);
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

        /** @var PublicKeyCredentialList|MockObject $sut */
        $sut = $this->getMockBuilder(PublicKeyCredentialList::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConfigObject'])
            ->getMock();
        $sut->method('getConfigObject')->willReturn($configMock);

        /** @var User|MockObject $userMock */
        $userMock = $this->getMockBuilder(User::class)
            ->onlyMethods(['isLoaded', 'getId'])
            ->getMock();
        $userMock->method('isLoaded')->willReturn($userLoaded);
        $userMock->method('getId')->willReturn('userid');

        if ($doCreate) {
            $pkcsMock = $this->getMockBuilder(PublicKeyCredentialSource::class)
                ->disableOriginalConstructor()
                ->getMock();
            foreach ($oxids as $oxid) {
                $pkc = $this->getMockBuilder(PublicKeyCredential::class)
                    ->onlyMethods(['allowDerivedDelete'])
                    ->getMock();
                $pkc->method('allowDerivedDelete')->willReturn(true);
                $pkc->setId($oxid);
                $pkc->assign([
                    'oxuserid' => 'userid',
                    'oxshopid' => 55
                ]);
                $pkc->setCredential($pkcsMock);
                $pkc->save();
            }
        }

        $return = $this->callMethod(
            $sut,
            'getAllFromUser',
            [$userMock]
        );

        if ($doCreate) {
            foreach ($oxids as $oxid) {
                $pkc->delete($oxid);
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

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Model\Credential\PublicKeyCredentialList::getConfigObject()
     */
    public function canGetConfigObject()
    {
        /** @var PublicKeyCredentialList|MockObject $sut */
        $sut = $this->getMockBuilder(PublicKeyCredentialList::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['saveCredentialSource']) // required for code coverage
            ->getMock();
        $sut->method('saveCredentialSource');

        $this->assertInstanceOf(
            Config::class,
            $this->callMethod(
                $sut,
                'getConfigObject'
            )
        );
    }
}