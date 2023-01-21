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
use D3\Webauthn\Application\Model\RelyingPartyEntity;
use D3\Webauthn\tests\unit\WAUnitTestCase;
use OxidEsales\Eshop\Application\Model\Shop;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;

class RelyingPartyEntityTest extends WAUnitTestCase
{
    use CanAccessRestricted;

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Model\RelyingPartyEntity::__construct
     */
    public function canConstruct()
    {
        /** @var Shop|MockObject $shopMock */
        $shopMock = $this->getMockBuilder(Shop::class)
            ->onlyMethods(['getFieldData'])
            ->getMock();
        $shopMock->method('getFieldData')->with($this->identicalTo('oxname'))->willReturn('myShopName');

        /** @var RelyingPartyEntity|MockObject $sut */
        $sut = $this->getMockBuilder(RelyingPartyEntity::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['d3CallMockableFunction', 'getActiveShop', 'getRPShopUrl'])
            ->getMock();
        $sut->expects($this->once())->method('d3CallMockableFunction')->with(
            $this->anything(),
            $this->identicalTo(['myShopName', 'myShopUrl'])
        );
        $sut->method('getActiveShop')->willReturn($shopMock);
        $sut->method('getRPShopUrl')->willReturn('myShopUrl');

        $this->callMethod(
            $sut,
            '__construct'
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Model\RelyingPartyEntity::getShopUrlByHost
     * @dataProvider canGetShopUrlByHostDataProvider
     */
    public function canGetShopUrlByHost($host, $expected)
    {
        $_SERVER['HTTP_HOST'] = $host;

        /** @var RelyingPartyEntity|MockObject $sut */
        $sut = $this->getMockBuilder(RelyingPartyEntity::class)
            ->onlyMethods(['d3CallMockableFunction']) // must mock, because can't disable constructor
            ->getMock();
        $sut->method('d3CallMockableFunction')->willReturn(true);

        $this->assertSame(
            $expected,
            $this->callMethod(
                $sut,
                'getShopUrlByHost'
            )
        );
    }

    /**
     * @return array
     */
    public function canGetShopUrlByHostDataProvider(): array
    {
        return [
            'base url'          => ['mydomain.com', 'mydomain.com'],
            'www url'           => ['www.mydomain.com', 'mydomain.com'],
            'www2 url'          => ['www2.mydomain.com', 'www2.mydomain.com'],
            'subdomain url'     => ['subd.mydomain.com', 'subd.mydomain.com'],
            'subdomain www url' => ['subd.www.mydomain.com', 'subd.www.mydomain.com'],
            'multipart TLD'     => ['www.mydomain.co.uk', 'mydomain.co.uk'],
        ];
    }

    /**
     * @test
     * @param $hostUrl
     * @param $expected
     * @return void
     * @throws ReflectionException
     * @dataProvider canGetRPShopUrlDataProvider
     * @covers \D3\Webauthn\Application\Model\RelyingPartyEntity::getRPShopUrl
     */
    public function canGetRPShopUrl($hostUrl, $expected)
    {
        /** @var RelyingPartyEntity|MockObject $sut */
        $sut = $this->getMockBuilder(RelyingPartyEntity::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getShopUrlByHost'])
            ->getMock();
        $sut->method('getShopUrlByHost')->willReturn($hostUrl);

        $this->assertSame(
            $expected,
            $this->callMethod(
                $sut,
                'getRPShopUrl'
            )
        );
    }

    /**
     * @return array
     */
    public function canGetRPShopUrlDataProvider(): array
    {
        return [
            'not configured'=> ['www.myhost.de', 'www.myhost.de'],
        ];
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Model\RelyingPartyEntity::getActiveShop
     */
    public function canGetActiveShop()
    {
        /** @var RelyingPartyEntity|MockObject $sut */
        $sut = $this->getMockBuilder(RelyingPartyEntity::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRPShopUrl']) // required for code coverage
            ->getMock();
        $sut->method('getRPShopUrl')->willReturn('fixture');

        $this->assertInstanceOf(
            Shop::class,
            $this->callMethod(
                $sut,
                'getActiveShop'
            )
        );
    }
}
