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

namespace D3\Webauthn\tests\unit\Modules;

use D3\TestingTools\Development\CanAccessRestricted;
use D3\Webauthn\Modules\WebauthnServices;
use D3\Webauthn\tests\unit\WAUnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;

class WebauthnServicesTest extends WAUnitTestCase
{
    use CanAccessRestricted;

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Modules\WebauthnServices::__construct
     */
    public function canConstruct()
    {
        /** @var WebauthnServices|MockObject $sut */
        $sut = $this->getMockBuilder(WebauthnServices::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addYamlDefinitions', 'd3CallMockableFunction'])
            ->getMock();
        $sut->expects($this->atLeastOnce())->method('addYamlDefinitions')->with(
            $this->identicalTo('d3/oxwebauthn/Config/services.yaml')
        );
        $sut->method('d3CallMockableFunction')->willReturn(true);

        $this->callMethod(
            $sut,
            '__construct'
        );
    }
}
