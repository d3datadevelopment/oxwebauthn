<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * https://www.d3data.de
 *
 * @copyright (C) D3 Data Development (Inh. Thomas Dartsch)
 * @author    D3 Data Development - Daniel Seifert <info@shopmodule.com>
 * @link      https://www.oxidmodule.com
 */

declare(strict_types=1);

namespace D3\Webauthn\Modules\Application\Component;

use Assert\AssertionFailedException;
use D3\Webauthn\Application\Model\Exceptions\WebauthnGetException;
use D3\Webauthn\Application\Model\WebauthnConf;
use D3\Webauthn\Application\Model\Webauthn;
use D3\Webauthn\Application\Model\Exceptions\WebauthnException;
use D3\Webauthn\Modules\Application\Model\d3_User_Webauthn;
use Doctrine\DBAL\Driver\Exception as DoctrineDriverException;
use Doctrine\DBAL\Exception;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\Eshop\Core\UtilsView;
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
        $password = Registry::getRequest()->getRequestParameter('lgn_pwd');
        /** @var d3_User_Webauthn $user */
        $user = oxNew(User::class);
        $userId = $user->d3GetLoginUserId($lgn_user);

        if ($lgn_user && $userId && !strlen(trim($password))) {
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
                    WebauthnConf::WEBAUTHN_SESSION_NAVPARAMS,
                    $this->getParent()->getNavigationParams()
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

    public function d3CancelWebauthnLogin(): void
    {
        $this->d3WebauthnClearSessionVariables();
    }

    /**
     * @param User $user
     * @param $sWebauthn
     */
    public function d3WebauthnRelogin(User $user, $sWebauthn): void
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
                Registry::getConfig()->getShopId()
            );
        }

        $this->_afterLogin($user);
    }

    /**
     * @return void
     */
    public function d3WebauthnClearSessionVariables(): void
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

    /**
     * @return void
     */
    public function d3AssertAuthn(): void
    {
        /** @var d3_User_Webauthn $user */
        $user = oxNew(User::class);

        try {
            if (strlen(Registry::getRequest()->getRequestEscapedParameter('error'))) {
                /** @var WebauthnGetException $e */
                $e = oxNew(
                    WebauthnGetException::class,
                    Registry::getRequest()->getRequestEscapedParameter('error')
                );
                throw $e;
            }

            if (strlen(Registry::getRequest()->getRequestEscapedParameter('credential'))) {
                $credential = Registry::getRequest()->getRequestEscapedParameter('credential');
                $webAuthn = oxNew( Webauthn::class );
                $webAuthn->assertAuthn( $credential );
                $user->load(Registry::getSession()->getVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER));
                $this->d3WebauthnRelogin($user, $credential);
            }
        } catch (WebauthnException $e) {
            Registry::getUtilsView()->addErrorToDisplay($e);
            Registry::getLogger()->error(
                $e->getDetailedErrorMessage(),
                ['UserId'   => Registry::getSession()->getVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER)]
            );
            Registry::getLogger()->debug($e->getTraceAsString());
            $user->logout();
        }
    }
}