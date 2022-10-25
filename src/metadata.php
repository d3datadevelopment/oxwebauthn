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

// https://github.com/web-auth/webauthn-framework/tree/master/doc
// https://webauthn-doc.spomky-labs.com/
// https://docs.solokeys.io/solo/

use D3\Webauthn\Application\Controller\Admin\d3user_webauthn;
use D3\Webauthn\Application\Controller\d3_account_webauthn;
use D3\Webauthn\Application\Controller\d3webauthnlogin;
use D3\Webauthn\Modules\Application\Component\d3_webauthn_UserComponent;
use D3\Webauthn\Modules\Application\Controller\Admin\d3_LoginController_Webauthn;
use D3\Webauthn\Modules\Application\Controller\d3_StartController_Webauthn;
use D3\Webauthn\Modules\Application\Controller\d3_webauthn_OrderController;
use D3\Webauthn\Modules\Application\Controller\d3_webauthn_PaymentController;
use D3\Webauthn\Modules\Application\Controller\d3_webauthn_UserController;
use D3\Webauthn\Modules\Application\Model\d3_User_Webauthn;
use D3\Webauthn\Modules\Core\d3_webauthn_utils;
use D3\Webauthn\Setup as ModuleSetup;
use D3\ModCfg\Application\Model\d3utils;
use OxidEsales\Eshop\Application\Component\UserComponent;
use OxidEsales\Eshop\Application\Controller\Admin\LoginController;
use OxidEsales\Eshop\Application\Controller\OrderController;
use OxidEsales\Eshop\Application\Controller\PaymentController;
use OxidEsales\Eshop\Application\Controller\StartController;
use OxidEsales\Eshop\Application\Controller\UserController;
use OxidEsales\Eshop\Core\Utils;
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
$aModule = array(
    'id'          => $sModuleId,
    'title'       => $logo.' Webauthn / FIDO2 Login',
    'description'   => [
        'de'        => 'Webauthn f&uuml;r OXID eSales Shop',
        'en'        => 'Webauthn for OXID eSales shop',
    ],
    'version'     => '0.0.1',
    'author'      => 'D&sup3; Data Development (Inh.: Thomas Dartsch)',
    'email'       => 'support@shopmodule.com',
    'url'         => 'http://www.oxidmodule.com/',
    'extend'      => [
        UserController::class  => d3_webauthn_UserController::class,
        PaymentController::class  => d3_webauthn_PaymentController::class,
        OrderController::class  => d3_webauthn_OrderController::class,
        OxidModel\User::class  => d3_User_Webauthn::class,
        StartController::class => d3_StartController_Webauthn::class,
        LoginController::class => d3_LoginController_Webauthn::class,
        Utils::class           => d3_webauthn_utils::class,
        UserComponent::class   => d3_webauthn_UserComponent::class,
    ],
    'controllers'   => [
        'd3user_webauthn'       => d3user_webauthn::class,
        'd3webauthnlogin'       => d3webauthnlogin::class,
        'd3_account_webauthn'   => d3_account_webauthn::class
    ],
    'templates'     => [
        'd3user_webauthn.tpl'       => 'd3/oxwebauthn/Application/views/admin/tpl/d3user_webauthn.tpl',
        'd3webauthnlogin.tpl'       => 'd3/oxwebauthn/Application/views/tpl/d3webauthnlogin.tpl',
        'd3_account_webauthn.tpl'   => 'd3/oxwebauthn/Application/views/tpl/d3_account_webauthn.tpl',

        'js_create.tpl'             => 'd3/oxwebauthn/Application/views/tpl/inc/js_create.tpl',
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
                'template'      => 'page/shop/start.tpl',
                'block'         => 'start_welcome_text',
                'file'          => 'Application/views/blocks/page/shop/start_welcome_text.tpl',
            ],
        [
            'template'      => 'login.tpl',
            'block'         => 'admin_login_form',
            'file'          => 'Application/views/admin/blocks/d3webauthn_login_admin_login_form.tpl',
        ]
    ]
);