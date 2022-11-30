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

namespace D3\Webauthn\tests\unit\Modules\Application\Controller;

use D3\TestingTools\Development\CanAccessRestricted;
use OxidEsales\Eshop\Application\Controller\AccountController;
use OxidEsales\Eshop\Application\Controller\AccountDownloadsController;
use OxidEsales\Eshop\Application\Controller\AccountNewsletterController;
use OxidEsales\Eshop\Application\Controller\AccountNoticeListController;
use OxidEsales\Eshop\Application\Controller\AccountOrderController;
use OxidEsales\Eshop\Application\Controller\AccountPasswordController;
use OxidEsales\Eshop\Application\Controller\AccountRecommlistController;
use OxidEsales\Eshop\Application\Controller\AccountReviewController;
use OxidEsales\Eshop\Application\Controller\AccountUserController;
use OxidEsales\Eshop\Application\Controller\AccountWishlistController;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;

trait AccountTestTrait
{
    use CanAccessRestricted;

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Modules\Application\Controller\d3_AccountController_Webauthn::__construct
     * @covers \D3\Webauthn\Modules\Application\Controller\d3_AccountDownloadsController_Webauthn::__construct
     * @covers \D3\Webauthn\Modules\Application\Controller\d3_AccountNewsletterController_Webauthn::__construct
     * @covers \D3\Webauthn\Modules\Application\Controller\d3_AccountNoticeListController_Webauthn::__construct
     * @covers \D3\Webauthn\Modules\Application\Controller\d3_AccountOrderController_Webauthn::__construct
     * @covers \D3\Webauthn\Modules\Application\Controller\d3_AccountPasswordController_Webauthn::__construct
     * @covers \D3\Webauthn\Modules\Application\Controller\d3_AccountRecommlistController_Webauthn::__construct
     * @covers \D3\Webauthn\Modules\Application\Controller\d3_AccountReviewController_Webauthn::__construct
     * @covers \D3\Webauthn\Modules\Application\Controller\d3_AccountUserController_Webauthn::__construct
     * @covers \D3\Webauthn\Modules\Application\Controller\d3_AccountWishlistController_Webauthn::__construct
     */
    public function canConstruct()
    {
        /** @var AccountController|AccountDownloadsController|AccountNewsletterController|AccountNoticeListController|AccountOrderController|AccountPasswordController|AccountRecommlistController|AccountReviewController|AccountUserController|AccountWishlistController|MockObject $sut */
        $sut = $this->getMockBuilder($this->sutClass)
            ->onlyMethods(['addTplParam'])
            ->disableOriginalConstructor()
            ->getMock();
        $sut->expects($this->atLeastOnce())->method('addTplParam')->with($this->identicalTo('oxLoginTpl'));

        $this->callMethod(
            $sut,
            '__construct'
        );

        $this->assertSame(
            'd3webauthnaccountlogin.tpl',
            $this->getValue(
                $sut,
                '_sThisLoginTemplate'
            )
        );
    }
}