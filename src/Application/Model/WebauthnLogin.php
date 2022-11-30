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

namespace D3\Webauthn\Application\Model;

use D3\Webauthn\Application\Controller\Traits\helpersTrait;
use D3\Webauthn\Application\Model\Exceptions\WebauthnException;
use D3\Webauthn\Application\Model\Exceptions\WebauthnGetException;
use D3\Webauthn\Application\Model\Exceptions\WebauthnLoginErrorException;
use D3\Webauthn\Modules\Application\Model\d3_User_Webauthn;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Exception\CookieException;
use OxidEsales\Eshop\Core\Exception\UserException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\Eshop\Core\Str;
use OxidEsales\Eshop\Core\SystemEventHandler;
use OxidEsales\Eshop\Core\UtilsServer;
use OxidEsales\EshopCommunity\Application\Component\UserComponent;

class WebauthnLogin
{
    use helpersTrait;

    public $credential;

    public $errorMsg;

    /**
     * @param string $credential
     * @param string|null $error
     * @throws WebauthnGetException
     */
    public function __construct(string $credential, string $error = null)
    {
        $this->setCredential($credential);
        $this->setErrorMsg($error);
    }

    /**
     * @return string
     * @throws WebauthnGetException
     */
    public function getCredential(): string
    {
        if (!strlen(trim((string) $this->credential))) {
            /** @var WebauthnGetException $e */
            $e = oxNew(WebauthnGetException::class, 'missing credential data');
            throw $e;
        }

        return trim($this->credential);
    }

    /**
     * @param string $credential
     * @throws WebauthnGetException
     */
    public function setCredential(string $credential): void
    {
        if (!strlen(trim($credential))) {
            /** @var WebauthnGetException $e */
            $e = oxNew(WebauthnGetException::class, 'missing credential data');
            throw $e;
        }

        $this->credential = trim($credential);
    }

    /**
     * @return ?string
     */
    public function getErrorMsg(): ?string
    {
        return $this->errorMsg;
    }

    /**
     * @param string|null $errorMsg
     */
    public function setErrorMsg(?string $errorMsg): void
    {
        $this->errorMsg = $errorMsg;
    }

    /**
     * @param UserComponent $usrCmp
     * @param bool $setSessionCookie
     * @return void
     * @throws WebauthnLoginErrorException
     */
    public function frontendLogin(UserComponent $usrCmp, bool $setSessionCookie = false)
    {
        $myUtilsView = $this->d3GetUtilsViewObject();
        /** @var d3_User_Webauthn $user */
        $user = $this->d3GetUserObject();
        $userId = $this->getUserId();

        try {
            $this->handleErrorMessage();

            $user = $this->assertUser($userId);
            $this->assertAuthn();

            // relogin, don't extract from this try block
            $usrCmp->setUser($this->d3GetUserObject());
            $this->setFrontendSession($user);
            $usrCmp->setLoginStatus(USER_LOGIN_SUCCESS);

            if ($setSessionCookie) {
                $this->setSessionCookie($user);
            }

            $this->regenerateSessionId();

            $usrCmp->setUser($user);

            return;
        } catch (UserException $oEx) {
            // for login component send exception text to a custom component (if defined)
            $myUtilsView->addErrorToDisplay($oEx, false, true, '', false);

            //return 'user';
        } catch (\OxidEsales\Eshop\Core\Exception\CookieException $oEx) {
            $myUtilsView->addErrorToDisplay($oEx);

            //return 'user';
        } catch (WebauthnException $e) {
            $myUtilsView->addErrorToDisplay($e);
            $this->d3GetLoggerObject()->error($e->getDetailedErrorMessage(), ['UserId'   => $userId]);
            $this->d3GetLoggerObject()->debug($e->getTraceAsString());
        }

        $user->logout();
        $exc = oxNew(WebauthnLoginErrorException::class);
        throw $exc;
    }

    /**
     * @param string $selectedProfile
     * @return string
     */
    public function adminLogin(string $selectedProfile): string
    {
        $myUtilsView = $this->d3GetUtilsViewObject();
        /** @var d3_User_Webauthn $user */
        $user = $this->d3GetUserObject();
        $userId = $this->getUserId();

        try {
            $this->handleErrorMessage();
            $this->assertUser($userId, true);
            $this->handleBlockedUser($user);
            $this->assertAuthn();
            $session = $this->setAdminSession($userId);
            $this->handleBackendCookie();
            $this->handleBackendSubshopRights($user, $session);

            $oEvenHandler = $this->d3WebauthnGetEventHandler();
            $oEvenHandler->onAdminLogin();

            $afterLogin = $this->getAfterLogin();
            $afterLogin->setDisplayProfile();
            $afterLogin->changeLanguage();

            $this->regenerateSessionId();
            $this->updateBasket();

            return "admin_start";
        } catch (UserException $oEx) {
            $myUtilsView->addErrorToDisplay('LOGIN_ERROR');
        } catch (CookieException $oEx) {
            $myUtilsView->addErrorToDisplay('LOGIN_NO_COOKIE_SUPPORT');
        } catch (WebauthnException $e) {
            $myUtilsView->addErrorToDisplay($e);
            $this->d3GetLoggerObject()->error($e->getDetailedErrorMessage(), ['UserId'   => $userId]);
            $this->d3GetLoggerObject()->debug($e->getTraceAsString());
        }

        $user->logout();
        $oStr = Str::getStr();
        $this->d3GetConfig()->getActiveView()->addTplParam('user', $oStr->htmlspecialchars($userId));
        $this->d3GetConfig()->getActiveView()->addTplParam('profile', $oStr->htmlspecialchars($selectedProfile));

        return 'login';
    }

    /**
     * @throws WebauthnGetException
     */
    public function handleErrorMessage()
    {
        $error = $this->getErrorMsg();

        if (strlen((string)$error)) {
            /** @var WebauthnGetException $e */
            $e = oxNew(WebauthnGetException::class, $error);
            throw $e;
        }
    }

    /**
     * @throws WebauthnGetException|WebauthnException
     */
    public function assertAuthn(): void
    {
        $credential = $this->getCredential();
        $webAuthn = $this->d3GetWebauthnObject();
        $webAuthn->assertAuthn($credential);
    }

    /**
     * @param $userId
     * @return Session
     */
    public function setAdminSession($userId): Session
    {
        $session = $this->d3GetSession();
        $adminProfiles = $session->getVariable("aAdminProfiles");
        $session->initNewSession();
        $session->setVariable("aAdminProfiles", $adminProfiles);
        $session->setVariable(WebauthnConf::OXID_ADMIN_AUTH, $userId);
        return $session;
    }

    /**
     * @param User $user
     * @return void
     */
    public function setSessionCookie(User $user)
    {
        if ($this->d3GetConfig()->getConfigParam('blShowRememberMe')) {
            $this->getUtilsServer()->setUserCookie(
                $user->getFieldData('oxusername'),
                $user->getFieldData('oxpassword'),
                $this->d3GetConfig()->getShopId()
            );
        }
    }

    /**
     * @param $userId
     * @param bool $isBackend
     * @return User
     * @throws UserException
     */
    public function assertUser($userId, bool $isBackend = false): User
    {
        $user = $this->d3GetUserObject();
        $user->load($userId);
        if (!$user->isLoaded() ||
            ($isBackend && $user->getFieldData('oxrights') === 'user')
        ) {
            /** @var UserException $exc */
            $exc = oxNew(UserException::class, 'ERROR_MESSAGE_USER_NOVALIDLOGIN');
            throw $exc;
        }

        return $user;
    }

    /**
     * @return void
     * @throws CookieException
     */
    public function handleBackendCookie(): void
    {
        $cookie = $this->getUtilsServer()->getOxCookie();
        if ($cookie === null) {
            /** @var CookieException $exc */
            $exc = oxNew(CookieException::class, 'ERROR_MESSAGE_COOKIE_NOCOOKIE');
            throw $exc;
        }
    }

    /**
     * @param User $user
     * @param Session $session
     * @return void
     */
    public function handleBackendSubshopRights(User $user, Session $session): void
    {
        $iSubshop = (int)$user->getFieldData('oxrights');

        if ($iSubshop) {
            $session->setVariable("shp", $iSubshop);
            $session->setVariable('currentadminshop', $iSubshop);
            $this->d3GetConfig()->setShopId($iSubshop);
        }
    }

    /**
     * @return void
     */
    public function regenerateSessionId(): void
    {
        $oSession = $this->d3GetSession();
        if ($oSession->isSessionStarted()) {
            $oSession->regenerateSessionId();
        }
    }

    public function handleBlockedUser(User $user)
    {
        // this user is blocked, deny him
        if ($user->inGroup('oxidblocked')) {
            $sUrl = $this->d3GetConfig()->getShopHomeUrl() . 'cl=content&tpl=user_blocked.tpl';
            $this->d3GetUtilsObject()->redirect($sUrl, true, 302);
        }
    }

    /**
     * @return void
     */
    public function updateBasket(): void
    {
        if ($oBasket = $this->d3GetSession()->getBasket()) {
            $oBasket->onUpdate();
        }
    }

    /**
     * @return SystemEventHandler
     */
    public function d3WebauthnGetEventHandler(): SystemEventHandler
    {
        return oxNew(SystemEventHandler::class);
    }

    /**
     * @return WebauthnAfterLogin
     */
    public function getAfterLogin(): WebauthnAfterLogin
    {
        return oxNew(WebauthnAfterLogin::class);
    }

    /**
     * @return bool
     */
    public function isAdmin(): bool
    {
        return isAdmin();
    }

    /**
     * @return string
     */
    public function getUserId(): string
    {
        return $this->isAdmin() ?
            $this->d3GetSession()->getVariable(WebauthnConf::WEBAUTHN_ADMIN_SESSION_CURRENTUSER) :
            $this->d3GetSession()->getVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER);
    }

    /**
     * @return Config
     */
    public function d3GetConfig(): Config
    {
        return Registry::getConfig();
    }

    /**
     * @return UtilsServer
     */
    public function getUtilsServer(): UtilsServer
    {
        return Registry::getUtilsServer();
    }

    /**
     * @param User $user
     * @return void
     * @throws WebauthnGetException
     */
    public function setFrontendSession(User $user): void
    {
        $this->d3GetSession()->setVariable(WebauthnConf::WEBAUTHN_SESSION_AUTH, $this->getCredential());
        $this->d3GetSession()->setVariable(WebauthnConf::OXID_FRONTEND_AUTH, $user->getId());
    }
}