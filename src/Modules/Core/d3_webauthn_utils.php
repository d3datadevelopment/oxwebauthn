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

use D3\Webauthn\Application\Model\d3webauthn;
use D3\Webauthn\Application\Model\d3webauthn_conf;
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
        $webauthnAuth = (bool) $this->d3GetSessionObject()->getVariable(d3webauthn_conf::WEBAUTHN_SESSION_AUTH);
        /** @var d3webauthn $webauthn */
        $webauthn = $this->d3GetWebauthnObject();
        $webauthn->loadByUserId($userID);

        if ($blAuth && $webauthn->isActive() && false === $webauthnAuth) {
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
     * @return d3webauthn
     */
    public function d3GetWebauthnObject()
    {
        return oxNew(d3webauthn::class);
    }
}