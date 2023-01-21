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

use D3\DIContainerHandler\definitionFileContainer;
use D3\Webauthn\Application\Controller\Admin\d3user_webauthn;
use D3\Webauthn\Application\Controller\Admin\d3webauthnadminlogin;
use D3\Webauthn\Application\Controller\d3_account_webauthn;
use D3\Webauthn\Application\Controller\d3webauthnlogin;
use D3\Webauthn\Modules\Application\Component\d3_webauthn_UserComponent;
use D3\Webauthn\Modules\Application\Controller\Admin\d3_LoginController_Webauthn;
use D3\Webauthn\Modules\Application\Controller\d3_AccountController_Webauthn;
use D3\Webauthn\Modules\Application\Controller\d3_AccountDownloadsController_Webauthn;
use D3\Webauthn\Modules\Application\Controller\d3_AccountNewsletterController_Webauthn;
use D3\Webauthn\Modules\Application\Controller\d3_AccountNoticeListController_Webauthn;
use D3\Webauthn\Modules\Application\Controller\d3_AccountOrderController_Webauthn;
use D3\Webauthn\Modules\Application\Controller\d3_AccountPasswordController_Webauthn;
use D3\Webauthn\Modules\Application\Controller\d3_AccountRecommlistController_Webauthn;
use D3\Webauthn\Modules\Application\Controller\d3_AccountReviewController_Webauthn;
use D3\Webauthn\Modules\Application\Controller\d3_AccountUserController_Webauthn;
use D3\Webauthn\Modules\Application\Controller\d3_AccountWishlistController_Webauthn;
use D3\Webauthn\Modules\Application\Controller\d3_webauthn_OrderController;
use D3\Webauthn\Modules\Application\Controller\d3_webauthn_PaymentController;
use D3\Webauthn\Modules\Application\Controller\d3_webauthn_UserController;
use D3\Webauthn\Modules\Application\Model\d3_User_Webauthn;
use D3\Webauthn\Modules\WebauthnServices;
use OxidEsales\Eshop\Application\Component\UserComponent;
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
use OxidEsales\Eshop\Application\Controller\Admin\LoginController;
use OxidEsales\Eshop\Application\Controller\OrderController;
use OxidEsales\Eshop\Application\Controller\PaymentController;
use OxidEsales\Eshop\Application\Controller\UserController;
use OxidEsales\Eshop\Application\Model as OxidModel;

/**
 * Metadata version
 */
$sMetadataVersion = '2.1';

$sModuleId = 'd3webauthn';
$logo = '<img src="https://logos.oxidmodule.com/d3logo.svg" alt="(D3)" style="height:1em;width:1em">';

/**
 * Module information
 */
$aModule = [
    'id'          => $sModuleId,
    'title'       => $logo.' Webauthn / FIDO2 Login',
    'description'   => [
        'de'        => 'Webauthn f&uuml;r OXID eSales Shop',
        'en'        => 'Webauthn for OXID eSales shop',
    ],
    'version'     => '1.0.0.0',
    'author'      => 'D&sup3; Data Development (Inh.: Thomas Dartsch)',
    'email'       => 'support@shopmodule.com',
    'url'         => 'https://www.oxidmodule.com/',
    'extend'      => [
        UserController::class           => d3_webauthn_UserController::class,
        PaymentController::class        => d3_webauthn_PaymentController::class,
        OrderController::class          => d3_webauthn_OrderController::class,
        OxidModel\User::class           => d3_User_Webauthn::class,
        LoginController::class          => d3_LoginController_Webauthn::class,
        UserComponent::class            => d3_webauthn_UserComponent::class,
        definitionFileContainer::class  => WebauthnServices::class,

        /** workarounds for missing tpl blocks (https://github.com/OXID-eSales/wave-theme/pull/124) */
        AccountController::class    => d3_AccountController_Webauthn::class,
        AccountDownloadsController::class    => d3_AccountDownloadsController_Webauthn::class,
        AccountNoticeListController::class   => d3_AccountNoticeListController_Webauthn::class,
        AccountWishlistController::class => d3_AccountWishlistController_Webauthn::class,
        AccountRecommlistController::class => d3_AccountRecommlistController_Webauthn::class,
        AccountPasswordController::class => d3_AccountPasswordController_Webauthn::class,
        AccountNewsletterController::class => d3_AccountNewsletterController_Webauthn::class,
        AccountUserController::class => d3_AccountUserController_Webauthn::class,
        AccountOrderController::class => d3_AccountOrderController_Webauthn::class,
        AccountReviewController::class => d3_AccountReviewController_Webauthn::class,
    ],
    'controllers'   => [
        'd3user_webauthn'       => d3user_webauthn::class,
        'd3webauthnlogin'       => d3webauthnlogin::class,
        'd3webauthnadminlogin'  => d3webauthnadminlogin::class,
        'd3_account_webauthn'   => d3_account_webauthn::class,
    ],
    'templates'     => [
        'd3user_webauthn.tpl'       => 'd3/oxwebauthn/Application/views/admin/tpl/d3user_webauthn.tpl',
        'd3webauthnlogin.tpl'       => 'd3/oxwebauthn/Application/views/tpl/d3webauthnlogin.tpl',
        'd3webauthnadminlogin.tpl'  => 'd3/oxwebauthn/Application/views/admin/tpl/d3webauthnlogin.tpl',
        'd3_account_webauthn.tpl'   => 'd3/oxwebauthn/Application/views/tpl/d3_account_webauthn.tpl',
        /** workaround for missing tpl blocks (https://github.com/OXID-eSales/wave-theme/pull/124) */
        'd3webauthnaccountlogin.tpl'=> 'd3/oxwebauthn/Application/views/tpl/d3webauthnaccountlogin.tpl',

        'js_create.tpl'             => 'd3/oxwebauthn/Application/views/tpl/inc/js_create.tpl',
        'js_login.tpl'              => 'd3/oxwebauthn/Application/views/tpl/inc/js_login.tpl',
    ],
    'events'      => [
        'onActivate'    => '\D3\Webauthn\Setup\Events::onActivate',
        'onDeactivate'  => '\D3\Webauthn\Setup\Events::onDeactivate',
    ],
    'blocks'      => [
        [
            'template'      => 'page/account/inc/account_menu.tpl',
            'block'         => 'account_menu',
            'file'          => 'Application/views/blocks/page/account/inc/account_menu.tpl',
        ],
        [
            'template'      => 'page/account/dashboard.tpl',
            'block'         => 'account_dashboard_col2',
            'file'          => 'Application/views/blocks/page/account/account_dashboard_col2_wave.tpl',
        ],
        [
            'theme'         => 'flow',
            'template'      => 'page/account/dashboard.tpl',
            'block'         => 'account_dashboard_col2',
            'file'          => 'Application/views/blocks/page/account/account_dashboard_col2_flow.tpl',
        ],
        [
            'template'      => 'widget/header/servicebox.tpl',
            'block'         => 'widget_header_servicebox_items',
            'file'          => 'Application/views/blocks/widget/header/widget_header_servicebox_items.tpl',
        ],
        [
            'template'      => 'page/checkout/inc/options.tpl',
            'block'         => 'checkout_options_login',
            'file'          => 'Application/views/blocks/page/checkout/inc/checkout_options_login.tpl',
        ],
    ],
];
