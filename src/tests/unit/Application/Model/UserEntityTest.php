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
use D3\Webauthn\Application\Model\UserEntity;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\TestingLibrary\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;

class UserEntityTest extends UnitTestCase
{
    use CanAccessRestricted;

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @dataProvider canConstructNoUserDataProvider
     * @covers \D3\Webauthn\Application\Model\UserEntity::__construct
     */
    public function canConstructNoUser($isLoaded, $getId, $runParent)
    {
        /** @var User|MockObject $userMock */
        $userMock = $this->getMockBuilder(User::class)
            ->onlyMethods(['isLoaded', 'getId', 'getFieldData'])
            ->getMock();
        $userMock->method('isLoaded')->willReturn($isLoaded);
        $userMock->method('getId')->willReturn($getId);
        $fieldDataMap = [
            ['oxusername', 'userNameFixture'],
            ['oxfname', 'fNameFixture'],
            ['oxlname', 'lNameFixture'],
        ];
        $userMock->method('getFieldData')->willReturnMap($fieldDataMap);

        /** @var UserEntity|MockObject $sut */
        $sut = $this->getMockBuilder(UserEntity::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['d3CallMockableParent'])
            ->getMock();
        $sut->expects($runParent ? $this->once() : $this->never())->method('d3CallMockableParent')->with(
            $this->anything(),
            $this->identicalTo([
                'usernamefixture',
                'userId',
                'fNameFixture lNameFixture'
            ])
        );

        if (!$runParent) {
            $this->expectException(WebauthnException::class);
        };

        $this->callMethod(
            $sut,
            '__construct',
            [$userMock]
        );
    }

    /**
     * @return array
     */
    public function canConstructNoUserDataProvider(): array
    {
        return [
            'not loaded'    => [false, 'userId', false],
            'no id'         => [true, null, false],
            'user ok'       => [true, 'userId', true],
        ];
    }
}