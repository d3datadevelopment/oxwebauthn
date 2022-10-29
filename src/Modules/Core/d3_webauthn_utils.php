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

namespace D3\Webauthn\Modules\Core;

use D3\Webauthn\Application\Model\Webauthn;
use D3\Webauthn\Application\Model\WebauthnConf;
use Doctrine\DBAL\DBALException;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Session;

class d3_webauthn_utils extends d3_webauthn_utils_parent
{
    /**
     * @return bool
     * @throws DBALException
     * @throws DatabaseConnectionException
     */
    public function checkAccessRights()
    {
        $blAuth = parent::checkAccessRights();

        $userID = $this->d3GetSessionObject()->getVariable("auth");
        $webauthnAuth = (bool) $this->d3GetSessionObject()->getVariable(WebauthnConf::WEBAUTHN_SESSION_AUTH);
        /** @var Webauthn $webauthn */
        $webauthn = $this->d3GetWebauthnObject();

        if ($blAuth && $webauthn->isActive($userID) && false === $webauthnAuth) {
            $this->redirect('index.php?cl=login', true, 302);
            if (false == defined('OXID_PHP_UNIT')) {
                // @codeCoverageIgnoreStart
                exit;
                // @codeCoverageIgnoreEnd
            }
        }

        return $blAuth;
    }

    /**
     * @return Session
     */
    public function d3GetSessionObject()
    {
        return Registry::getSession();
    }

    /**
     * @return Webauthn
     */
    public function d3GetWebauthnObject()
    {
        return oxNew(Webauthn::class);
    }
}