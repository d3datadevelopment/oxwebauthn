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

use D3\Webauthn\Application\Model\d3webauthn;
use D3\Webauthn\Application\Model\d3webauthn_conf;
use OxidEsales\Eshop\Core\Registry;

class d3_User_Webauthn extends d3_User_Webauthn_parent
{
    public function logout()
    {
        $return = parent::logout();

        Registry::getSession()->deleteVariable(d3webauthn_conf::WEBAUTHN_SESSION_AUTH);
        Registry::getSession()->deleteVariable(d3webauthn_conf::WEBAUTHN_LOGIN_OBJECT);
        Registry::getSession()->deleteVariable(d3webauthn_conf::WEBAUTHN_SESSION_CURRENTUSER);
        Registry::getSession()->deleteVariable(d3webauthn_conf::WEBAUTHN_SESSION_CURRENTCLASS);
        Registry::getSession()->deleteVariable(d3webauthn_conf::WEBAUTHN_SESSION_NAVFORMPARAMS);

        return $return;
    }

    public function d3templogout()
    {
        $varname = Registry::getSession()->getVariable(d3webauthn_conf::WEBAUTHN_SESSION_AUTH);
        $object = Registry::getSession()->getVariable(d3webauthn_conf::WEBAUTHN_LOGIN_OBJECT);
        $currentUser = Registry::getSession()->getVariable(d3webauthn_conf::WEBAUTHN_SESSION_CURRENTUSER);
        $currentClass = Registry::getSession()->getVariable(d3webauthn_conf::WEBAUTHN_SESSION_CURRENTCLASS);
        $navFormParams = Registry::getSession()->getVariable(d3webauthn_conf::WEBAUTHN_SESSION_NAVFORMPARAMS);

        $return = $this->logout();

        Registry::getSession()->setVariable(d3webauthn_conf::WEBAUTHN_SESSION_AUTH,  $varname);
        Registry::getSession()->setVariable(d3webauthn_conf::WEBAUTHN_LOGIN_OBJECT, $object);
        Registry::getSession()->setVariable(d3webauthn_conf::WEBAUTHN_SESSION_CURRENTUSER, $currentUser);
        Registry::getSession()->setVariable(d3webauthn_conf::WEBAUTHN_SESSION_CURRENTCLASS, $currentClass);
        Registry::getSession()->setVariable(d3webauthn_conf::WEBAUTHN_SESSION_NAVFORMPARAMS, $navFormParams);

        return $return;
    }

    /**
     * @return d3webauthn
     */
    public function d3getWebauthn()
    {
        return oxNew(d3webauthn::class);
    }
}