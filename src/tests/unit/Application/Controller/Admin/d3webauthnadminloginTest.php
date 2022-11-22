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

namespace D3\Webauthn\tests\unit\Application\Controller\Admin;

use D3\TestingTools\Development\CanAccessRestricted;
use D3\Webauthn\Application\Controller\Admin\d3webauthnadminlogin;
use D3\Webauthn\tests\unit\Application\Controller\d3webauthnloginTest;
use ReflectionException;

class d3webauthnadminloginTest extends d3webauthnloginTest
{
    use CanAccessRestricted;

    protected $sutClassName = d3webauthnadminlogin::class;

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\Admin\d3webauthnadminlogin::_authorize
     */
    public function canAuthorize()
    {
        $sut = oxNew(d3webauthnadminlogin::class);

        $this->assertTrue(
            $this->callMethod(
                $sut,
                '_authorize'
            )
        );
    }

    /**
     * @return void
     */
    public function canGetNavigationParams()
    {}

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\Admin\d3webauthnadminlogin::render
     * @dataProvider canRenderDataProvider
     */
    public function canRender($auth, $userFromLogin, $startRedirect, $redirectController = 'admin_start')
    {
        parent::canRender($auth, $userFromLogin, $startRedirect, 'admin_start');
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\Admin\d3webauthnadminlogin::generateCredentialRequest
     */
    public function canGenerateCredentialRequest()
    {
        parent::canGenerateCredentialRequest();
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\Admin\d3webauthnadminlogin::generateCredentialRequest
     */
    public function generateCredentialRequestFailed($redirectClass = 'login')
    {
        parent::generateCredentialRequestFailed($redirectClass);
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\Admin\d3webauthnadminlogin::getUtils
     */
    public function getUtilsReturnsRightInstance()
    {
        parent::getUtilsReturnsRightInstance();
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\Admin\d3webauthnadminlogin::getPreviousClass
     */
    public function canGetPreviousClass()
    {
        parent::canGetPreviousClass();
    }

    /**
     * @test
     * @param $currClass
     * @param $isOrderStep
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\Admin\d3webauthnadminlogin::previousClassIsOrderStep
     * @dataProvider canPreviousClassIsOrderStepDataProvider
     */
    public function canPreviousClassIsOrderStep($currClass, $isOrderStep)
    {
        parent::canPreviousClassIsOrderStep($currClass, $isOrderStep);
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Application\Controller\Admin\d3webauthnadminlogin::getIsOrderStep
     * @dataProvider canGetIsOrderStepDataProvider
     */
    public function canGetIsOrderStep($boolean)
    {
        parent::canGetIsOrderStep($boolean);
    }

    public function canGetBreadCrumb()
    {
    }
}