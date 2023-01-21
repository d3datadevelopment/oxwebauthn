<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * https://www.d3data.de
 *
 * @copyright (C) D3 Data Development (Inh. Thomas Dartsch)
 * @author    D3 Data Development - Daniel Seifert <support@shopmodule.com>
 * @link      https://www.oxidmodule.com
 */

declare(strict_types=1);

namespace D3\Webauthn\tests\unit;

use D3\DIContainerHandler\d3DicHandler;
use OxidEsales\TestingLibrary\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class WAUnitTestCase extends UnitTestCase
{
    /**
     * setup basic requirements
     */
    public function setUp(): void
    {
        parent::setUp();

        d3DicHandler::getUncompiledInstance();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        d3DicHandler::removeInstance();
    }

    /**
     * @param $serviceName
     * @param $serviceMock
     *
     * @return MockObject
     */
    protected function getContainerMock($serviceName, $serviceMock): MockObject
    {
        $container = $this->getMockBuilder(ContainerInterface::class)
            ->onlyMethods(['get', 'has'])
            ->getMock();
        $container->expects($this->any())
            ->method('get')
            ->with($this->equalTo($serviceName))
            ->will($this->returnValue($serviceMock));

        return $container;
    }
}
