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
 * @author    D3 Data Development - Daniel Seifert <support@shopmodule.com>
 * @link      http://www.oxidmodule.com
 */

namespace D3\Webauthn\Modules\Application\Controller;

use D3\Webauthn\Application\Model\Webauthn;
use D3\Webauthn\Application\Model\WebauthnConf;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Session;

trait d3_webauthn_getUserTrait
{
    /**
     * @return bool|object|User
     */
    public function getUser()
    {
        $user = parent::getUser();

        if ($user && $user->getId()) {
            $webauthn = $this->d3GetWebauthnpObject();

            if ($webauthn->isActive($user->getId())
                && false == $this->d3GetSessionObject()->getVariable(WebauthnConf::WEBAUTHN_SESSION_AUTH)
            ) {
                return false;
            }
        }

        return $user;
    }

    /**
     * @return Webauthn
     */
    public function d3GetWebauthnObject()
    {
        return oxNew(Webauthn::class);
    }

    /**
     * @return Session
     */
    public function d3GetSessionObject()
    {
        return Registry::getSession();
    }
}