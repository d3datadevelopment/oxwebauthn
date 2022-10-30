<?php

/**
 * This Software is the property of Data Development and is protected
 * by copyright law - it is NOT Freeware.
 * Any unauthorized use of this software without a valid license
 * is a violation of the license agreement and will be prosecuted by
 * civil and criminal law.
 * http://www.shopmodule.com
 *
 * @copyright (C) D3 Data Development (Inh. Thomas Dartsch)
 * @author        D3 Data Development - Daniel Seifert <support@shopmodule.com>
 * @link          http://www.oxidmodule.com
 */

namespace D3\Webauthn\Modules\Application\Model;

use D3\Webauthn\Application\Model\WebauthnConf;
use OxidEsales\Eshop\Core\Registry;
use ReflectionClass;

class d3_User_Webauthn extends d3_User_Webauthn_parent
{
    public function logout()
    {
        $return = parent::logout();

        Registry::getSession()->deleteVariable(WebauthnConf::WEBAUTHN_SESSION_AUTH);
        Registry::getSession()->deleteVariable(WebauthnConf::WEBAUTHN_LOGIN_OBJECT);
        Registry::getSession()->deleteVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER);
        Registry::getSession()->deleteVariable(WebauthnConf::WEBAUTHN_SESSION_LOGINUSER);
        Registry::getSession()->deleteVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTCLASS);
        Registry::getSession()->deleteVariable(WebauthnConf::WEBAUTHN_SESSION_NAVFORMPARAMS);

        return $return;
    }

    public function d3templogout()
    {
        $varname = Registry::getSession()->getVariable(WebauthnConf::WEBAUTHN_SESSION_AUTH);
        $object = Registry::getSession()->getVariable(WebauthnConf::WEBAUTHN_LOGIN_OBJECT);
        $currentUser = Registry::getSession()->getVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER);
        $currentClass = Registry::getSession()->getVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTCLASS);
        $navFormParams = Registry::getSession()->getVariable(WebauthnConf::WEBAUTHN_SESSION_NAVFORMPARAMS);
        $loginUser = Registry::getSession()->getVariable(WebauthnConf::WEBAUTHN_SESSION_LOGINUSER);

        $return = $this->logout();

        Registry::getSession()->setVariable(WebauthnConf::WEBAUTHN_SESSION_AUTH,  $varname);
        Registry::getSession()->setVariable(WebauthnConf::WEBAUTHN_LOGIN_OBJECT, $object);
        Registry::getSession()->setVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER, $currentUser);
        Registry::getSession()->setVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTCLASS, $currentClass);
        Registry::getSession()->setVariable(WebauthnConf::WEBAUTHN_SESSION_NAVFORMPARAMS, $navFormParams);
        Registry::getSession()->setVariable(WebauthnConf::WEBAUTHN_SESSION_LOGINUSER, $loginUser);

        return $return;
    }

    public function login($userName, $password, $setSessionCookie = false)
    {
        if (Registry::getSession()->getVariable(WebauthnConf::WEBAUTHN_SESSION_AUTH)) {
            $userName = $userName ?: Registry::getSession()->getVariable(WebauthnConf::WEBAUTHN_SESSION_LOGINUSER);
            $config = Registry::getConfig();
            $shopId = $config->getShopId();

            /** private method is out of scope */
            $class = new ReflectionClass($this);
            $method = $class->getMethod('loadAuthenticatedUser');
            $method->setAccessible(true);
            $method->invokeArgs(
                $this,
                [
                    Registry::getSession()->getVariable(WebauthnConf::WEBAUTHN_SESSION_LOGINUSER),
                    $shopId
                ]
            );
        }

        return parent::login($userName, $password, $setSessionCookie);
    }
}