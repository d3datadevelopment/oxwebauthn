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

namespace D3\Webauthn\tests\unit\Modules\Application\Component;

use D3\TestingTools\Development\CanAccessRestricted;
use D3\Webauthn\Application\Model\Exceptions\WebauthnException;
use D3\Webauthn\Application\Model\Exceptions\WebauthnGetException;
use D3\Webauthn\Application\Model\Exceptions\WebauthnLoginErrorException;
use D3\Webauthn\Application\Model\WebauthnLogin;
use OxidEsales\Eshop\Application\Component\UserComponent;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\Eshop\Core\UtilsView;
use OxidEsales\TestingLibrary\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;

class UserComponentWebauthnTest extends UnitTestCase
{
    use CanAccessRestricted;

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Modules\Application\Component\d3_webauthn_UserComponent::d3CancelWebauthnLogin
     */
    public function canCancelWebauthnLogin()
    {
        /** @var UserComponent|MockObject $sut */
        $sut = $this->getMockBuilder(UserComponent::class)
            ->onlyMethods(['d3WebauthnClearSessionVariables'])
            ->getMock();
        $sut->expects($this->once())->method('d3WebauthnClearSessionVariables');

        $this->callMethod(
            $sut,
            'd3CancelWebauthnLogin'
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Modules\Application\Component\d3_webauthn_UserComponent::d3WebauthnClearSessionVariables
     */
    public function canClearSessionVariables()
    {
        /** @var Session|MockObject $sessionMock */
        $sessionMock = $this->getMockBuilder(Session::class)
            ->onlyMethods(['deleteVariable'])
            ->getMock();
        $sessionMock->expects($this->atLeast(4))->method('deleteVariable')->willReturn(true);

        /** @var UserComponent|MockObject $sut */
        $sut = $this->getMockBuilder(UserComponent::class)
            ->onlyMethods(['d3GetMockableRegistryObject'])
            ->getMock();
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
            'd3WebauthnClearSessionVariables'
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Modules\Application\Component\d3_webauthn_UserComponent::d3AssertAuthn
     * @dataProvider canAssertAuthnDataProvider
     */
    public function canAssertAuthn($thrownExcecption, $afterLoginInvocationCount, $addErrorInvocationCount)
    {
        /** @var UtilsView|MockObject $utilsViewMock */
        $utilsViewMock = $this->getMockBuilder(UtilsView::class)
            ->onlyMethods(['addErrorToDisplay'])
            ->getMock();
        $utilsViewMock->expects($addErrorInvocationCount)->method('addErrorToDisplay');

        /** @var WebauthnLogin|MockObject $webauthnLoginMock */
        $webauthnLoginMock = $this->getMockBuilder(WebauthnLogin::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['frontendLogin'])
            ->getMock();
        if ($thrownExcecption) {
            $webauthnLoginMock->expects($this->once())->method('frontendLogin')->willThrowException(
                oxNew($thrownExcecption)
            );
        } else {
            $webauthnLoginMock->expects($this->once())->method('frontendLogin');
        }

        /** @var UserComponent|MockObject $sut */
        $sut = $this->getMockBuilder(UserComponent::class)
            ->onlyMethods(['d3GetMockableOxNewObject', 'd3GetMockableRegistryObject', '_afterLogin'])
            ->getMock();
        $sut->method('d3GetMockableOxNewObject')->willReturnCallback(
            function () use ($webauthnLoginMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case WebauthnLogin::class:
                        return $webauthnLoginMock;
                    default:
                        return call_user_func_array("oxNew", $args);
                }
            }
        );
        $sut->method('d3GetMockableRegistryObject')->willReturnCallback(
            function () use ($utilsViewMock) {
                $args = func_get_args();
                switch ($args[0]) {
                    case UtilsView::class:
                        return $utilsViewMock;
                    default:
                        return Registry::get($args[0]);
                }
            }
        );
        $sut->expects($afterLoginInvocationCount)->method('_afterLogin');

        $this->callMethod(
            $sut,
            'd3AssertAuthn'
        );
    }

    /**
     * @return array[]
     */
    public function canAssertAuthnDataProvider(): array
    {
        return [
            'passed'                => [null, $this->once(), $this->never()],
            'webauthnException'     => [WebauthnGetException::class, $this->never(), $this->once()],
            'webauthnLoginError'    => [WebauthnLoginErrorException::class, $this->never(), $this->never()]
        ];
    }
}