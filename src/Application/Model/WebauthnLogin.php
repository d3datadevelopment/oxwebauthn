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

use D3\TestingTools\Production\IsMockable;
use D3\Webauthn\Application\Model\Exceptions\WebauthnException;
use D3\Webauthn\Application\Model\Exceptions\WebauthnGetException;
use D3\Webauthn\Application\Model\Exceptions\WebauthnLoginErrorException;
use D3\Webauthn\Modules\Application\Model\d3_User_Webauthn;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Exception\CookieException;
use OxidEsales\Eshop\Core\Exception\UserException;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\Eshop\Core\Str;
use OxidEsales\Eshop\Core\SystemEventHandler;
use OxidEsales\Eshop\Core\Utils;
use OxidEsales\Eshop\Core\UtilsServer;
use OxidEsales\Eshop\Core\UtilsView;
use OxidEsales\EshopCommunity\Application\Component\UserComponent;

class WebauthnLogin
{
    use IsMockable;

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
     */
    public function frontendLogin(UserComponent $usrCmp, bool $setSessionCookie = false)
    {
        $myUtilsView = $this->d3GetMockableRegistryObject(UtilsView::class);
        /** @var d3_User_Webauthn $user */
        $user = $this->d3GetMockableOxNewObject(User::class);
        $userId = $this->getUserId();

        try {
            $this->handleErrorMessage();

            $user = $this->assertUser($userId);
            $this->assertAuthn();

            // relogin, don't extract from this try block
            $usrCmp->setUser($this->d3GetMockableOxNewObject(User::class));
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
            $myUtilsView->addErrorToDisplay($oEx, false, true);
        } catch (CookieException $oEx) {
            $myUtilsView->addErrorToDisplay($oEx);
        } catch (WebauthnException $e) {
            $myUtilsView->addErrorToDisplay($e);
            $this->d3GetMockableLogger()->error($e->getDetailedErrorMessage(), ['UserId'   => $userId]);
            $this->d3GetMockableLogger()->debug($e->getTraceAsString());
        }

        $user->logout();
        throw oxNew(WebauthnLoginErrorException::class);
    }

    /**
     * @param string $selectedProfile
     * @return string
     */
    public function adminLogin(string $selectedProfile): string
    {
        $myUtilsView = $this->d3GetMockableRegistryObject(UtilsView::class);
        /** @var d3_User_Webauthn $user */
        $user = $this->d3GetMockableOxNewObject(User::class);
        $userId = $this->getUserId();

        try {
            $this->handleErrorMessage();
            $this->assertUser($userId, true);
            $this->handleBlockedUser($user);
            $this->assertAuthn();
            $session = $this->setAdminSession($userId);
            $this->handleBackendCookie();
            $this->handleBackendSubshopRights($user, $session);

            $oEventHandler = $this->d3GetMockableOxNewObject(SystemEventHandler::class);
            $oEventHandler->onAdminLogin();

            $afterLogin = $this->d3GetMockableOxNewObject(WebauthnAfterLogin::class);
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
            $this->d3GetMockableLogger()->error($e->getDetailedErrorMessage(), ['UserId'   => $userId]);
            $this->d3GetMockableLogger()->debug($e->getTraceAsString());
        }

        $user->logout();
        $oStr = Str::getStr();
        $this->d3GetMockableRegistryObject(Config::class)->getActiveView()
            ->addTplParam('user', $oStr->htmlspecialchars($userId));
        $this->d3GetMockableRegistryObject(Config::class)->getActiveView()
            ->addTplParam('profile', $oStr->htmlspecialchars($selectedProfile));

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
        $webAuthn = $this->d3GetMockableOxNewObject(Webauthn::class);
        $webAuthn->assertAuthn($credential);
    }

    /**
     * @param $userId
     * @return Session
     */
    public function setAdminSession($userId): Session
    {
        $session = $this->d3GetMockableRegistryObject(Session::class);
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
        if ($this->d3GetMockableRegistryObject(Config::class)->getConfigParam('blShowRememberMe')) {
            $this->d3GetMockableRegistryObject(UtilsServer::class)->setUserCookie(
                $user->getFieldData('oxusername'),
                $user->getFieldData('oxpassword'),
                $this->d3GetMockableRegistryObject(Config::class)->getShopId()
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
        $user = $this->d3GetMockableOxNewObject(User::class);
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
        $cookie = $this->d3GetMockableRegistryObject(UtilsServer::class)->getOxCookie();
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
            $this->d3GetMockableRegistryObject(Config::class)->setShopId((string) $iSubshop);
        }
    }

    /**
     * @return void
     */
    public function regenerateSessionId(): void
    {
        $oSession = $this->d3GetMockableRegistryObject(Session::class);
        if ($oSession->isSessionStarted()) {
            $oSession->regenerateSessionId();
        }
    }

    public function handleBlockedUser(User $user)
    {
        // this user is blocked, deny him
        if ($user->inGroup('oxidblocked')) {
            $sUrl = $this->d3GetMockableRegistryObject(Config::class)->getShopHomeUrl() .
                    'cl=content&tpl=user_blocked.tpl';
            $this->d3GetMockableRegistryObject(Utils::class)->redirect($sUrl);
        }
    }

    /**
     * @return void
     */
    public function updateBasket(): void
    {
        $oBasket = $this->d3GetMockableRegistryObject(Session::class)->getBasket();
        $oBasket->onUpdate();
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
            $this->d3GetMockableRegistryObject(Session::class)
                 ->getVariable(WebauthnConf::WEBAUTHN_ADMIN_SESSION_CURRENTUSER) :
            $this->d3GetMockableRegistryObject(Session::class)
                 ->getVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER);
    }

    /**
     * @param User $user
     * @return void
     * @throws WebauthnGetException
     */
    public function setFrontendSession(User $user): void
    {
        $session = $this->d3GetMockableRegistryObject(Session::class);
        $session->setVariable(WebauthnConf::WEBAUTHN_SESSION_AUTH, $this->getCredential());
        $session->setVariable(WebauthnConf::OXID_FRONTEND_AUTH, $user->getId());
    }
}
