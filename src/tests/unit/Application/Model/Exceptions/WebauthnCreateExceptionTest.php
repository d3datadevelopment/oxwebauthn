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
use D3\Webauthn\Application\Model\Exceptions\WebauthnCreateException;
use OxidEsales\TestingLibrary\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;

class WebauthnCreateExceptionTest extends UnitTestCase
{
    use CanAccessRestricted;

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Model\Exceptions\WebauthnCreateException::getRequestType
     */
    public function canGetRequestType()
    {
        /** @var WebauthnCreateException|MockObject $sut */
        $sut = $this->getMockBuilder(WebauthnCreateException::class)
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
