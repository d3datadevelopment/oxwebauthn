<?php

/**
 * This Software is the property of Data Development and is protected
 * by copyright law - it is NOT Freeware.
 *
 * Any unauthorized use of this software without a valid license
 * is a violation of the license agreement and will be prosecuted by
 * civil and criminal law.
 *
 * http://www.shopmodule.com
 *
 * @copyright (C) D3 Data Development (Inh. Thomas Dartsch)
 * @author    D3 Data Development - Daniel Seifert <support@shopmodule.com>
 * @link      http://www.oxidmodule.com
 */

namespace D3\Webauthn\Modules\Application\Component
{
    use OxidEsales\Eshop\Application\Component\UserComponent;

    class d3_webauthn_UserComponent_parent extends UserComponent {}
}

namespace D3\Webauthn\Modules\Application\Controller
{

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
    use OxidEsales\Eshop\Application\Controller\OrderController;
    use OxidEsales\Eshop\Application\Controller\PaymentController;
    use OxidEsales\Eshop\Application\Controller\UserController;

    class d3_webauthn_UserController_parent extends UserController {}

    class d3_webauthn_OrderController_parent extends OrderController {}

    class d3_webauthn_PaymentController_parent extends PaymentController {}

    /** workarounds for missing tpl blocks (https://github.com/OXID-eSales/wave-theme/pull/124) */
    class d3_AccountController_Webauthn_parent extends AccountController {}

    class d3_AccountDownloadsController_Webauthn_parent extends AccountDownloadsController {}

    class d3_AccountNoticeListController_Webauthn_parent extends AccountNoticeListController {}

    class d3_AccountWishlistController_Webauthn_parent extends AccountWishlistController {}

    class d3_AccountRecommlistController_Webauthn_parent extends AccountRecommlistController {}

    class d3_AccountPasswordController_Webauthn_parent extends AccountPasswordController {}

    class d3_AccountNewsletterController_Webauthn_parent extends AccountNewsletterController {}

    class d3_AccountUserController_Webauthn_parent extends AccountUserController {}

    class d3_AccountOrderController_Webauthn_parent extends AccountOrderController {}

    class d3_AccountReviewController_Webauthn_parent extends AccountReviewController {}
}

namespace D3\Webauthn\Modules\Application\Controller\Admin
{
    use OxidEsales\Eshop\Application\Controller\Admin\LoginController;

    class d3_LoginController_Webauthn_parent extends LoginController {}
}

namespace D3\Webauthn\Modules\Application\Model
{
    use OxidEsales\Eshop\Application\Model\User;

    class d3_User_Webauthn_parent extends User {}
}