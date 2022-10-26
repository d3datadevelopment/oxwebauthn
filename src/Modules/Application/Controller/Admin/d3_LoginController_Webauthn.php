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

namespace D3\Webauthn\Modules\Application\Controller\Admin;

use D3\Webauthn\Application\Model\d3webauthn;
use D3\Webauthn\Application\Model\WebauthnConf;
use D3\Webauthn\Application\Model\Exceptions\d3WebauthnExceptionAbstract;
use D3\Webauthn\Application\Model\Exceptions\d3webauthnMissingPublicKeyCredentialRequestOptions;
use D3\Webauthn\Application\Model\Exceptions\d3webauthnWrongAuthException;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\Eshop\Core\UtilsView;

class d3_LoginController_Webauthn extends d3_LoginController_Webauthn_parent
{
    /**
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function render()
    {
        $auth = $this->d3GetSession()->getVariable("auth");

        $return = parent::render();

        if ($auth) {
            $webauthn = $this->d3GetWebauthnObject();
            $publicKeyCredentialRequestOptions = $webauthn->getCredentialRequestOptions($auth);

            $this->addTplParam(
                'webauthn_publickey_login',
                $publicKeyCredentialRequestOptions
            );

            $this->addTplParam('request_webauthn', true);
        }

        return $return;
    }

    /**
     * @return d3webauthn
     */
    public function d3GetWebauthnObject()
    {
        return oxNew(d3webauthn::class);
    }

    /**
     * @return UtilsView
     */
    public function d3GetUtilsView()
    {
        return Registry::getUtilsView();
    }

    /**
     * @return Session
     */
    public function d3GetSession()
    {
        return Registry::getSession();
    }

    /**
     * @return mixed|string
     * @throws DatabaseConnectionException
     */
    public function checklogin()
    {
        //$sWebauth = Registry::getRequest()->getRequestEscapedParameter('keyauth');
        $sWebauth = base64_decode(Registry::getRequest()->getRequestParameter('keyauth'));

        $webauthn = $this->d3GetWebauthnObject();
        $webauthn->loadByUserId(Registry::getSession()->getVariable("auth"));

        $return = 'login';

        try {
            if ($this->isNoWebauthnOrNoLogin($webauthn)) {
                $return = parent::checklogin();
            } elseif ($this->hasValidWebauthn($sWebauth, $webauthn)) {
                $this->d3GetSession()->setVariable(WebauthnConf::WEBAUTHN_SESSION_AUTH, $sWebauth);
                $return = "admin_start";
            }
        } catch (d3webauthnExceptionAbstract $oEx) {
            $this->d3GetUtilsView()->addErrorToDisplay($oEx);
        }

        return $return;
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
        return Registry::getSession()->getVariable(WebauthnConf::WEBAUTHN_SESSION_AUTH) ||
        (
            $sWebauth && $webauthn->verify($sWebauth)
        );
    }

    public function d3WebauthnCancelLogin()
    {
        $oUser = $this->d3GetUserObject();
        $oUser->logout();
    }

    /**
     * @return User
     */
    public function d3GetUserObject()
    {
        return oxNew(User::class);
    }
}