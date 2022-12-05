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

namespace D3\Webauthn\tests\unit\Modules\Application\Model;

use D3\TestingTools\Development\CanAccessRestricted;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\TestingLibrary\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;

class UserWebauthnTest extends UnitTestCase
{
    use CanAccessRestricted;

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Modules\Application\Model\d3_User_Webauthn::logout
     */
    public function canLogout()
    {
        /** @var Session|MockObject $sessionMock */
        $sessionMock = $this->getMockBuilder(Session::class)
            ->onlyMethods(['deleteVariable'])
            ->getMock();
        $sessionMock->expects($this->atLeast(11))->method('deleteVariable')->willReturn(true);

        /** @var User|MockObject $sut */
        $sut = $this->getMockBuilder(User::class)
            ->onlyMethods(['d3CallMockableParent', 'd3GetMockableRegistryObject'])
            ->getMock();
        $sut->method('d3CallMockableParent')->willReturn(true);
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
            'logout'
        );
    }
}