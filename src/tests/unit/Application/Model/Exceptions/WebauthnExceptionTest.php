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

namespace D3\Webauthn\tests\unit\Application\Model\Exceptions;

use D3\TestingTools\Development\CanAccessRestricted;
use D3\Webauthn\Application\Model\Exceptions\WebauthnException;
use D3\Webauthn\Application\Model\WebauthnErrors;
use OxidEsales\TestingLibrary\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;

class WebauthnExceptionTest extends UnitTestCase
{
    use CanAccessRestricted;

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Model\Exceptions\WebauthnException::__construct
     */
    public function canConstruct()
    {
        /** @var \Exception|MockObject $previousMock */
        $previousMock = $this->getMockBuilder(\Exception::class)
            ->getMock();

        /** @var WebauthnErrors|MockObject $translatorMock */
        $translatorMock = $this->getMockBuilder(WebauthnErrors::class)
            ->onlyMethods(['translateError'])
            ->getMock();
        $translatorMock->method('translateError')->willReturn('translatedError');

        /** @var WebauthnException|MockObject $sut */
        $sut = $this->getMockBuilder(WebauthnException::class)
            ->onlyMethods(['setDetailedErrorMessage', 'getErrorMessageTranslator', 'getRequestType',
                'd3CallMockableParent'])
            ->disableOriginalConstructor()
            ->getMock();
        $sut->expects($this->once())->method('setDetailedErrorMessage');
        $sut->method('getErrorMessageTranslator')->willReturn($translatorMock);
        $sut->method('getRequestType')->willReturn('requestType');
        $sut->expects($this->once())->method('d3CallMockableParent')->with(
            $this->anything(),
            $this->identicalTo(['translatedError', 255, $previousMock])
        );

        $this->callMethod(
            $sut,
            '__construct',
            ['myMessage', 255, $previousMock]
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Model\Exceptions\WebauthnException::getErrorMessageTranslator
     */
    public function canGetErrorMessageTranslator()
    {
        /** @var WebauthnException|MockObject $sut */
        $sut = $this->getMockBuilder(WebauthnException::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertInstanceOf(
            WebauthnErrors::class,
            $this->callMethod(
                $sut,
                'getErrorMessageTranslator'
            )
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Model\Exceptions\WebauthnException::getRequestType
     */
    public function canGetRequestType()
    {
        /** @var WebauthnException|MockObject $sut */
        $sut = $this->getMockBuilder(WebauthnException::class)
            ->onlyMethods(['getErrorMessageTranslator'])
            ->getMock();
        $sut->method('getErrorMessageTranslator');

        $this->assertNull(
            $this->callMethod(
                $sut,
                'getRequestType'
            )
        );
    }

    /**
     * @test
     * @param $messageFixture
     * @return void
     * @throws ReflectionException
     * @dataProvider canSetAndGetDetailedErrorMessageDataProvider
     * @covers \D3\Webauthn\Application\Model\Exceptions\WebauthnException::setDetailedErrorMessage
     * @covers \D3\Webauthn\Application\Model\Exceptions\WebauthnException::getDetailedErrorMessage
     */
    public function canSetAndGetDetailedErrorMessage($messageFixture)
    {
        /** @var WebauthnException|MockObject $sut */
        $sut = $this->getMockBuilder(WebauthnException::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRequestType'])
            ->getMock();
        $sut->method('getRequestType');

        $this->callMethod(
            $sut,
            'setDetailedErrorMessage',
            [$messageFixture]
        );

        $this->assertSame(
            'Webauthn: '.$messageFixture,
            $this->callMethod(
                $sut,
                'getDetailedErrorMessage'
            )
        );
    }

    /**
     * @return array
     */
    public function canSetAndGetDetailedErrorMessageDataProvider(): array
    {
        return [
            'message is string' => ['errorMessageFixture'],
            'message is null'   => [null]
        ];
    }
}