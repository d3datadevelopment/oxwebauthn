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

use D3\Webauthn\Application\Model\d3webauthn;
use D3\Webauthn\Application\Model\d3webauthn_conf;
use Doctrine\DBAL\DBALException;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Session;

trait d3_webauthn_getUserTrait
{
    /**
     * @return bool|object|User
     * @throws DatabaseConnectionException
     * @throws DBALException
     */
    public function getUser()
    {
        $oUser = parent::getUser();

        if ($oUser && $oUser->getId()) {
            $webauthn = $this->d3GetWebauthnpObject();
            $webauthn->loadByUserId($oUser->getId());

            if ($webauthn->isActive()
                && false == $this->d3GetSessionObject()->getVariable(d3webauthn_conf::WEBAUTHN_SESSION_AUTH)
            ) {
                return false;
            }
        }

        return $oUser;
    }

    /**
     * @return d3webauthn
     */
    public function d3GetWebauthnpObject()
    {
        return oxNew(d3webauthn::class);
    }

    /**
     * @return Session
     */
    public function d3GetSessionObject()
    {
        return Registry::getSession();
    }
}