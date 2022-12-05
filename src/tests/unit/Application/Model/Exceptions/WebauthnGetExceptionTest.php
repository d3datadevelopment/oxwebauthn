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
use D3\Webauthn\Application\Model\Exceptions\WebauthnGetException;
use OxidEsales\TestingLibrary\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;

class WebauthnGetExceptionTest extends UnitTestCase
{
    use CanAccessRestricted;

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Model\Exceptions\WebauthnGetException::getRequestType
     */
    public function canGetRequestType()
    {
        /** @var WebauthnGetException|MockObject $sut */
        $sut = $this->getMockBuilder(WebauthnGetException::class)
            ->onlyMethods(['getErrorMessageTranslator'])
            ->getMock();
        $sut->method('getErrorMessageTranslator');

        $this->assertIsString(
            $this->callMethod(
                $sut,
                'getRequestType'
            )
        );
    }
}