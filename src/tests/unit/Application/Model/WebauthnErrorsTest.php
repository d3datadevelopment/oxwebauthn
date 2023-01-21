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
use D3\Webauthn\Application\Model\WebauthnErrors;
use D3\Webauthn\tests\unit\WAUnitTestCase;
use OxidEsales\Eshop\Core\Language;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\TestingLibrary\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;

class WebauthnErrorsTest extends WAUnitTestCase
{
    use CanAccessRestricted;

    /**
     * @test
     * @param $errId
     * @param $message
     * @param $expectedTranslationIdPart
     * @return void
     * @throws ReflectionException
     * @dataProvider canTranslateErrorDataProvider
     * @covers \D3\Webauthn\Application\Model\WebauthnErrors::translateError
     */
    public function canTranslateError($errId, $message, $expectedTranslationIdPart)
    {
        /** @var Language|MockObject $languageMock */
        $languageMock = $this->getMockBuilder(Language::class)
            ->onlyMethods(['translateString'])
            ->getMock();
        $languageMock->expects($this->once())->method('translateString')->with(
            $this->callback(
                function ($value) use ($expectedTranslationIdPart) {
                    return (bool) strstr($value, $expectedTranslationIdPart);
                }
            )
        )->willReturn('translated');
        d3GetOxidDIC()->set('d3ox.webauthn.'.Language::class, $languageMock);

        /** @var WebauthnErrors|MockObject $sut */
        $sut = $this->getMockBuilder(WebauthnErrors::class)
            ->onlyMethods(['getErrIdFromMessage'])
            ->getMock();
        $sut->method('getErrIdFromMessage')->willReturn($errId);

        $this->assertSame(
            'translated',
            $this->callMethod(
                $sut,
                'translateError',
                [$message]
            )
        );
    }

    /**
     * @return array
     */
    public function canTranslateErrorDataProvider(): array
    {
        return [
            'invalid state' => [WebauthnErrors::INVALIDSTATE, 'myMsg', 'INVALIDSTATE'],
            'not allowed'   => [WebauthnErrors::NOTALLWED, 'myMsg', 'NOTALLOWED'],
            'abort'         => [WebauthnErrors::ABORT, 'myMsg', 'ABORT'],
            'constraint'    => [WebauthnErrors::CONSTRAINT, 'myMsg', 'CONSTRAINT'],
            'not supported' => [WebauthnErrors::NOTSUPPORTED, 'myMsg', 'NOTSUPPORTED'],
            'unknown'       => [WebauthnErrors::UNKNOWN, 'myMsg', 'UNKNOWN'],
            'no pubkey sppt'=> [WebauthnErrors::NOPUBKEYSUPPORT, 'myMsg', 'NOPUBKEYSUPPORT'],
            'unsecure'      => ['other', strtoupper(WebauthnErrors::UNSECURECONNECTION), strtoupper(WebauthnErrors::UNSECURECONNECTION)],
            'other'         => ['other', 'other', 'TECHNICALERROR'],
        ];
    }

    /**
     * @test
     * @param $message
     * @param $expected
     * @return void
     * @throws ReflectionException
     * @dataProvider canGetErrIdFromMessageDataProvider
     * @covers \D3\Webauthn\Application\Model\WebauthnErrors::getErrIdFromMessage
     */
    public function canGetErrIdFromMessage($message, $expected)
    {
        /** @var WebauthnErrors $sut */
        $sut = oxNew(WebauthnErrors::class);

        $this->assertSame(
            $expected,
            $this->callMethod(
                $sut,
                'getErrIdFromMessage',
                [$message]
            )
        );
    }

    /**
     * @return array[]
     */
    public function canGetErrIdFromMessageDataProvider(): array
    {
        return [
            'with colon'        => [' My Text With : Colon', 'my text with'],
            'without colon'     => [' My Text With Colon', 'my text with colon'],
        ];
    }
}
