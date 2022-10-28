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
use D3\Webauthn\Application\Model\WebauthnConf;
use D3\Webauthn\Application\Model\Exceptions\d3webauthnMissingPublicKeyCredentialRequestOptions;
use D3\Webauthn\Application\Model\Exceptions\d3webauthnWrongAuthException;
use D3\Webauthn\Application\Model\Webauthn;
use D3\Webauthn\Modules\Application\Model\d3_User_Webauthn;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Query\QueryBuilder;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
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
     * @throws DBALException
     * @throws DatabaseConnectionException
     */
    public function login_noredirect()
    {
        $lgn_user = Registry::getRequest()->getRequestParameter('lgn_usr');
        $userId = $this->d3GetLoginUserId($lgn_user);

        if ($lgn_user && $userId) {
            $webauthn = $this->d3GetWebauthnObject();

            if ($webauthn->isActive($userId)
                && false == Registry::getSession()->getVariable(WebauthnConf::WEBAUTHN_SESSION_AUTH)
            ) {
                Registry::getSession()->setVariable(
                    WebauthnConf::WEBAUTHN_SESSION_CURRENTCLASS,
                    $this->getParent()->getClassKey() != 'd3webauthnlogin' ? $this->getParent()->getClassKey() : 'start');
                Registry::getSession()->setVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER, $userId);
                Registry::getSession()->setVariable(
                    WebauthnConf::WEBAUTHN_SESSION_NAVFORMPARAMS,
                    $this->getParent()->getViewConfig()->getNavFormParams()
                );

                //$oUser->d3templogout();

                return "d3webauthnlogin";
            }
        }

        parent::login_noredirect();

        /** @var d3_User_Webauthn $user */
/*
        $oUser = $this->getUser();

        if ($oUser && $oUser->getId()) {
            $webauthn = $this->d3GetWebauthnObject();
            $webauthn->loadByUserId($oUser->getId());

            if ($webauthn->isActive()
                && false == Registry::getSession()->getVariable(WebauthnConf::WEBAUTHN_SESSION_AUTH)
            ) {
                Registry::getSession()->setVariable(
                    WebauthnConf::WEBAUTHN_SESSION_CURRENTCLASS,
                    $this->getParent()->getClassKey() != 'd3webauthnlogin' ? $this->getParent()->getClassKey() : 'start');
                Registry::getSession()->setVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER, $oUser->getId());
                Registry::getSession()->setVariable(
                    WebauthnConf::WEBAUTHN_SESSION_NAVFORMPARAMS,
                    $this->getParent()->getViewConfig()->getNavFormParams()
                );

                $oUser->d3templogout();

                return "d3webauthnlogin";
            }
        }
*/
    }

    /**
     * @return Webauthn
     */
    public function d3GetWebauthnObject()
    {
        return oxNew(Webauthn::class);
    }

    /**
     * @return bool|string
     * @throws DatabaseConnectionException
     * @throws d3webauthnMissingPublicKeyCredentialRequestOptions
     */
    public function checkWebauthnlogin()
    {
        $sWebauth = base64_decode(Registry::getRequest()->getRequestParameter('keyauth'));

        $sUserId = Registry::getSession()->getVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER);
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
        return Registry::getSession()->getVariable(WebauthnConf::WEBAUTHN_SESSION_AUTH) ||
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
        $this->d3GetSession()->setVariable(WebauthnConf::WEBAUTHN_SESSION_AUTH, $sWebauthn);
        $this->d3GetSession()->setVariable('usr', $oUser->getId());
        $this->setUser(null);
        $this->setLoginStatus(USER_LOGIN_SUCCESS);
        $this->_afterLogin($oUser);
    }

    public function d3WebauthnClearSessionVariables()
    {
        $this->d3GetSession()->deleteVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTCLASS);
        $this->d3GetSession()->deleteVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER);
        $this->d3GetSession()->deleteVariable(WebauthnConf::WEBAUTHN_SESSION_NAVFORMPARAMS);
    }

    /**
     * @return Session
     */
    public function d3GetSession()
    {
        return Registry::getSession();
    }

    /**
     * @return string|null
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws Exception
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function d3GetLoginUserId($username): ?string
    {
        if (empty($username)) {
            return null;
        }

        $user = oxNew(User::class);

        /** @var QueryBuilder $qb */
        $qb = ContainerFactory::getInstance()->getContainer()->get(QueryBuilderFactoryInterface::class)->create();
        $qb->select('oxid')
            ->from($user->getViewName())
            ->where(
                $qb->expr()->and(
                    $qb->expr()->eq(
                        'oxusername',
                        $qb->createNamedParameter($username)
                    ),
                    $qb->expr()->eq(
                        'oxshopid',
                        $qb->createNamedParameter(Registry::getConfig()->getShopId())
                    )
                )
            )->setMaxResults(1);

        return $qb->execute()->fetchOne();
    }
}