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

namespace D3\Webauthn\Modules\Application\Component;

use D3\Webauthn\Application\Model\d3webauthn;
use D3\Webauthn\Application\Model\d3webauthn_conf;
use D3\Webauthn\Application\Model\Exceptions\d3webauthnMissingPublicKeyCredentialRequestOptions;
use D3\Webauthn\Application\Model\Exceptions\d3webauthnWrongAuthException;
use D3\Webauthn\Modules\Application\Model\d3_User_Webauthn;
use Doctrine\DBAL\DBALException;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\Eshop\Core\UtilsView;

class d3_webauthn_UserComponent extends d3_webauthn_UserComponent_parent
{
    /**
     * @return string|void
     * @throws DBALException
     * @throws DatabaseConnectionException
     */
    public function login_noredirect()
    {
        $sUser = Registry::getRequest()->getRequestParameter('lgn_usr');
        $oUser = oxNew(User::class);
        $q = "SELECT * FROM ".$oUser->getViewName()." WHERE oxusername = ? and oxshopid = ?";
        $userId = DatabaseProvider::getDb()->getOne(
            $q,
            array($sUser, Registry::getConfig()->getActiveShop()->getId())
        );

        if ($sUser) {
            $webauthn = $this->d3GetWebauthnObject();
            $webauthn->loadByUserId($userId);
            if ($webauthn->isActive()
                && false == Registry::getSession()->getVariable(d3webauthn_conf::WEBAUTHN_SESSION_AUTH)
            ) {
                Registry::getSession()->setVariable(
                    d3webauthn_conf::WEBAUTHN_SESSION_CURRENTCLASS,
                    $this->getParent()->getClassKey() != 'd3webauthnlogin' ? $this->getParent()->getClassKey() : 'start');
                Registry::getSession()->setVariable(d3webauthn_conf::WEBAUTHN_SESSION_CURRENTUSER, $oUser->getId());
                Registry::getSession()->setVariable(
                    d3webauthn_conf::WEBAUTHN_SESSION_NAVFORMPARAMS,
                    $this->getParent()->getViewConfig()->getNavFormParams()
                );

                //$oUser->d3templogout();

                return "d3webauthnlogin";
            }
        }

        parent::login_noredirect();

        /** @var d3_User_Webauthn $oUser */
/*
        $oUser = $this->getUser();

        if ($oUser && $oUser->getId()) {
            $webauthn = $this->d3GetWebauthnObject();
            $webauthn->loadByUserId($oUser->getId());

            if ($webauthn->isActive()
                && false == Registry::getSession()->getVariable(d3webauthn_conf::WEBAUTHN_SESSION_AUTH)
            ) {
                Registry::getSession()->setVariable(
                    d3webauthn_conf::WEBAUTHN_SESSION_CURRENTCLASS,
                    $this->getParent()->getClassKey() != 'd3webauthnlogin' ? $this->getParent()->getClassKey() : 'start');
                Registry::getSession()->setVariable(d3webauthn_conf::WEBAUTHN_SESSION_CURRENTUSER, $oUser->getId());
                Registry::getSession()->setVariable(
                    d3webauthn_conf::WEBAUTHN_SESSION_NAVFORMPARAMS,
                    $this->getParent()->getViewConfig()->getNavFormParams()
                );

                $oUser->d3templogout();

                return "d3webauthnlogin";
            }
        }
*/
    }

    /**
     * @return d3webauthn
     */
    public function d3GetWebauthnObject()
    {
        return oxNew(d3webauthn::class);
    }

    /**
     * @return bool|string
     * @throws DatabaseConnectionException
     * @throws d3webauthnMissingPublicKeyCredentialRequestOptions
     */
    public function checkWebauthnlogin()
    {
        $sWebauth = base64_decode(Registry::getRequest()->getRequestParameter('keyauth'));

        $sUserId = Registry::getSession()->getVariable(d3webauthn_conf::WEBAUTHN_SESSION_CURRENTUSER);
        $oUser = oxNew(User::class);
        $oUser->load($sUserId);

        $webauthn = $this->d3GetWebauthnObject();
        $webauthn->loadByUserId($sUserId);

        try {
            if (false == $this->isNoWebauthnOrNoLogin($webauthn) && $this->hasValidWebauthn($sWebauth, $webauthn)) {
                $this->d3WebauthnRelogin($oUser, $sWebauth);
                $this->d3WebauthnClearSessionVariables();

                return false;
            }
        } catch (d3webauthnWrongAuthException $oEx) {
            $this->d3GetUtilsView()->addErrorToDisplay($oEx, false, false, "", 'd3webauthnlogin');
        }

        return 'd3webauthnlogin';
    }

    /**
     * @return UtilsView
     */
    public function d3GetUtilsView()
    {
        return Registry::getUtilsView();
    }

    public function cancelWebauthnLogin()
    {
        $this->d3WebauthnClearSessionVariables();

        return false;
    }

    /**
     * @param d3webauthn $webauthn
     * @return bool
     */
    public function isNoWebauthnOrNoLogin($webauthn)
    {
        return false == $this->d3GetSession()->getVariable("auth")
            || false == $webauthn->isActive();
    }

    /**
     * @param string $sWebauth
     * @param d3webauthn $webauthn
     * @return bool
     * @throws d3webauthnMissingPublicKeyCredentialRequestOptions
     * @throws d3webauthnWrongAuthException
     */
    public function hasValidWebauthn($sWebauth, $webauthn)
    {
        return Registry::getSession()->getVariable(d3webauthn_conf::WEBAUTHN_SESSION_AUTH) ||
            (
                $sWebauth && $webauthn->verify($sWebauth)
            );
    }

    /**
     * @param User $oUser
     * @param $sWebauthn
     */
    public function d3WebauthnRelogin(User $oUser, $sWebauthn)
    {
        $this->d3GetSession()->setVariable(d3webauthn_conf::WEBAUTHN_SESSION_AUTH, $sWebauthn);
        $this->d3GetSession()->setVariable('usr', $oUser->getId());
        $this->setUser(null);
        $this->setLoginStatus(USER_LOGIN_SUCCESS);
        $this->_afterLogin($oUser);
    }

    public function d3WebauthnClearSessionVariables()
    {
        $this->d3GetSession()->deleteVariable(d3webauthn_conf::WEBAUTHN_SESSION_CURRENTCLASS);
        $this->d3GetSession()->deleteVariable(d3webauthn_conf::WEBAUTHN_SESSION_CURRENTUSER);
        $this->d3GetSession()->deleteVariable(d3webauthn_conf::WEBAUTHN_SESSION_NAVFORMPARAMS);
    }

    /**
     * @return Session
     */
    public function d3GetSession()
    {
        return Registry::getSession();
    }
}