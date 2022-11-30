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

use D3\Webauthn\Application\Model\Exceptions\WebauthnException;
use D3\Webauthn\Application\Model\Exceptions\WebauthnGetException;
use D3\Webauthn\Application\Model\Exceptions\WebauthnLoginErrorException;
use D3\Webauthn\Application\Model\WebauthnConf;
use D3\Webauthn\Application\Model\Webauthn;
use D3\Webauthn\Application\Model\WebauthnLogin;
use D3\Webauthn\Modules\Application\Model\d3_User_Webauthn;
use Doctrine\DBAL\Driver\Exception as DoctrineDriverException;
use Doctrine\DBAL\Exception;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\Eshop\Core\UtilsView;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;

class d3_webauthn_UserComponent extends d3_webauthn_UserComponent_parent
{
    /**
     * @return string
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     * @throws DoctrineDriverException
     */
    public function login()
    {
        $lgn_user = $this->d3WebAuthnGetRequest()->getRequestParameter('lgn_usr');
        $password = $this->d3WebAuthnGetRequest()->getRequestParameter('lgn_pwd');
        /** @var d3_User_Webauthn $user */
        $user = oxNew(User::class);
        $userId = $user->d3GetLoginUserId($lgn_user);

        if ($lgn_user && $userId && !strlen(trim((string) $password))) {
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

                $sUrl = Registry::getConfig()->getShopHomeUrl() . 'cl=d3webauthnlogin';
                Registry::getUtils()->redirect($sUrl, true, 302);
            }
        }

        return parent::login();
    }

    /**
     * @return Webauthn
     */
    public function d3GetWebauthnObject(): Webauthn
    {
        return oxNew(Webauthn::class);
    }

    public function d3CancelWebauthnLogin(): void
    {
        $this->d3WebauthnClearSessionVariables();
    }

    /**
     * @return void
     */
    public function d3WebauthnClearSessionVariables(): void
    {
        $this->d3WebauthnGetSession()->deleteVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTCLASS);
        $this->d3WebauthnGetSession()->deleteVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER);
        $this->d3WebauthnGetSession()->deleteVariable(WebauthnConf::WEBAUTHN_SESSION_NAVFORMPARAMS);
        $this->d3WebauthnGetSession()->deleteVariable(WebauthnConf::WEBAUTHN_LOGIN_OBJECT);
    }

    /**
     * @return void
     */
    public function d3AssertAuthn(): void
    {
        try {
            $login = $this->getWebauthnLoginObject(
                $this->d3WebAuthnGetRequest()->getRequestEscapedParameter('credential'),
                $this->d3WebAuthnGetRequest()->getRequestEscapedParameter('error')
            );
            $login->frontendLogin($this, (bool)$this->d3WebAuthnGetRequest()->getRequestParameter('lgn_cook'));
            $this->_afterLogin($this->getUser());
        } catch (WebauthnGetException $e) {
            Registry::getUtilsView()->addErrorToDisplay($e);
        } catch (WebauthnLoginErrorException $e) {}
    }

    /**
     * @param string $credential
     * @param string|null $error
     * @throws WebauthnGetException
     * @return WebauthnLogin
     */
    public function getWebauthnLoginObject(string $credential, ?string $error): WebauthnLogin
    {
        return oxNew(WebauthnLogin::class, $credential, $error);
    }

    /**
     * @return Session
     */
    public function d3WebauthnGetSession(): Session
    {
        return Registry::getSession();
    }

    /**
     * @return User
     */
    public function d3WebauthnGetUserObject(): User
    {
        return oxNew(User::class);
    }

    /**
     * @return UtilsView
     */
    public function d3GetUtilsViewObject(): UtilsView
    {
        return Registry::getUtilsView();
    }

    /**
     * @return LoggerInterface
     */
    public function d3GetLoggerObject(): LoggerInterface
    {
        return Registry::getLogger();
    }

    /**
     * @return Request
     */
    public function d3WebAuthnGetRequest(): Request
    {
        return Registry::getRequest();
    }
}