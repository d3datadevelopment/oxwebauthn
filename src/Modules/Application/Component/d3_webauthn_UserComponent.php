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

use Assert\AssertionFailedException;
use D3\Webauthn\Application\Model\WebauthnConf;
use D3\Webauthn\Application\Model\Webauthn;
use D3\Webauthn\Application\Model\WebauthnException;
use D3\Webauthn\Modules\Application\Model\d3_User_Webauthn;
use Doctrine\DBAL\Driver\Exception as DoctrineDriverException;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Query\QueryBuilder;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\Eshop\Core\UtilsView;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class d3_webauthn_UserComponent extends d3_webauthn_UserComponent_parent
{
    /**
     * @return string|void
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     * @throws DoctrineDriverException
     */
    public function login_noredirect()
    {
        $lgn_user = Registry::getRequest()->getRequestParameter('lgn_usr');
        /** @var d3_User_Webauthn $user */
        $user = oxNew(User::class);
        $userId = $user->d3GetLoginUserId($lgn_user);

        if ($lgn_user && $userId) {
            $webauthn = $this->d3GetWebauthnObject();

            if ($webauthn->isActive($userId)
                && !Registry::getSession()->getVariable(WebauthnConf::WEBAUTHN_SESSION_AUTH)
            ) {
                Registry::getSession()->setVariable(
                    WebauthnConf::WEBAUTHN_SESSION_CURRENTCLASS,
                    $this->getParent()->getClassKey() != 'd3webauthnlogin' ? $this->getParent()->getClassKey() : 'start');
                Registry::getSession()->setVariable(
                    WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER,
                    $userId
                );
                Registry::getSession()->setVariable(
                    WebauthnConf::WEBAUTHN_SESSION_NAVFORMPARAMS,
                    $this->getParent()->getViewConfig()->getNavFormParams()
                );

                return "d3webauthnlogin";
            }
        }

        parent::login_noredirect();
    }

    /**
     * @return Webauthn
     */
    public function d3GetWebauthnObject(): Webauthn
    {
        return oxNew(Webauthn::class);
    }

    /**
     * @return UtilsView
     */
    public function d3GetUtilsView(): UtilsView
    {
        return Registry::getUtilsView();
    }

    public function cancelWebauthnLogin(): bool
    {
        $this->d3WebauthnClearSessionVariables();

        return false;
    }

    /**
     * @param Webauthn $webauthn
     * @param $userId
     * @return bool
     * @throws ContainerExceptionInterface
     * @throws DoctrineDriverException
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    public function isNoWebauthnOrNoLogin(Webauthn $webauthn, $userId): bool
    {
        return false == $this->d3GetSession()->getVariable("auth")
            || false == $webauthn->isActive($userId);
    }

    /**
     * @param string $sWebauth
     * @param Webauthn $webauthn
     * @return bool
     */
    public function hasValidWebauthn(string $sWebauth, Webauthn $webauthn): bool
    {
        try {
            return Registry::getSession()->getVariable(WebauthnConf::WEBAUTHN_SESSION_AUTH) ||
                (
                    $sWebauth && $webauthn->assertAuthn($sWebauth)
                );
        } catch (AssertionFailedException|WebauthnException $e) {
            return false;
        }
    }

    /**
     * @param User $user
     * @param $sWebauthn
     */
    public function d3WebauthnRelogin(User $user, $sWebauthn)
    {
        $setSessionCookie = Registry::getRequest()->getRequestParameter('lgn_cook');
        $this->d3GetSession()->setVariable(WebauthnConf::WEBAUTHN_SESSION_AUTH, $sWebauthn);
        $this->d3GetSession()->setVariable('usr', $user->getId());
        $this->setUser(null);
        $this->setLoginStatus(USER_LOGIN_SUCCESS);

        // cookie must be set ?
        if ($setSessionCookie && Registry::getConfig()->getConfigParam('blShowRememberMe')) {
            Registry::getUtilsServer()->setUserCookie(
                $user->oxuser__oxusername->value,
                $user->oxuser__oxpassword->value,
                Registry::getConfig()->getShopId(),
                31536000,
                User::USER_COOKIE_SALT
            );
        }

        $this->_afterLogin($user);
    }

    public function d3WebauthnClearSessionVariables()
    {
        $this->d3GetSession()->deleteVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTCLASS);
        $this->d3GetSession()->deleteVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER);
        $this->d3GetSession()->deleteVariable(WebauthnConf::WEBAUTHN_SESSION_NAVFORMPARAMS);
        $this->d3GetSession()->deleteVariable(WebauthnConf::WEBAUTHN_LOGIN_OBJECT);
    }

    /**
     * @return Session
     */
    public function d3GetSession(): Session
    {
        return Registry::getSession();
    }
}