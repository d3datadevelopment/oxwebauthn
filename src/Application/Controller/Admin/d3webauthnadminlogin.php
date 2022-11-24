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

namespace D3\Webauthn\Application\Controller\Admin;

use D3\TestingTools\Production\IsMockable;
use D3\Webauthn\Application\Controller\Traits\helpersTrait;
use D3\Webauthn\Application\Model\Exceptions\WebauthnGetException;
use D3\Webauthn\Application\Model\Webauthn;
use D3\Webauthn\Application\Model\WebauthnConf;
use D3\Webauthn\Application\Model\Exceptions\WebauthnException;
use D3\Webauthn\Modules\Application\Controller\Admin\d3_LoginController_Webauthn;
use D3\Webauthn\Modules\Application\Model\d3_User_Webauthn;
use Doctrine\DBAL\Driver\Exception as DoctrineDriverException;
use Doctrine\DBAL\Exception as DoctrineException;
use OxidEsales\Eshop\Application\Controller\Admin\AdminController;
use OxidEsales\Eshop\Application\Controller\Admin\LoginController;
use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Core\Exception\ConnectionException;
use OxidEsales\Eshop\Core\Exception\CookieException;
use OxidEsales\Eshop\Core\Exception\UserException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\SystemEventHandler;
use OxidEsales\Eshop\Core\Utils;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class d3webauthnadminlogin extends AdminController
{
    use helpersTrait;
    use IsMockable;

    protected $_sThisTemplate = 'd3webauthnadminlogin.tpl';

    /**
     * @return bool
     */
    protected function _authorize(): bool
    {
        return true;
    }

    /**
     * @return string
     * @throws ContainerExceptionInterface
     * @throws DoctrineDriverException
     * @throws DoctrineException
     * @throws NotFoundExceptionInterface
     */
    public function render(): string
    {
        if ($this->d3GetSession()->hasVariable(WebauthnConf::WEBAUTHN_ADMIN_SESSION_AUTH) ||
            !$this->d3GetSession()->hasVariable(WebauthnConf::WEBAUTHN_ADMIN_SESSION_CURRENTUSER)
        ) {
            $this->getUtils()->redirect('index.php?cl=admin_start');
        }

        /** @var d3_LoginController_Webauthn $loginController */
        $loginController = oxNew(LoginController::class);
        $loginController->d3WebauthnAfterLoginChangeLanguage();

        $this->generateCredentialRequest();

        $this->addTplParam('navFormParams', $this->d3GetSession()->getVariable(WebauthnConf::WEBAUTHN_SESSION_NAVFORMPARAMS));
        $this->addTplParam('currentProfile', $this->d3GetSession()->getVariable(WebauthnConf::WEBAUTHN_ADMIN_PROFILE));
        $this->d3GetSession()->deleteVariable(WebauthnConf::WEBAUTHN_ADMIN_PROFILE);
        $this->addTplParam('currentChLanguage', $this->d3GetSession()->getVariable(WebauthnConf::WEBAUTHN_ADMIN_CHLANGUAGE));
        $this->d3GetSession()->deleteVariable(WebauthnConf::WEBAUTHN_ADMIN_CHLANGUAGE);

        return $this->d3CallMockableParent('render');
    }

    /**
     * @return void
     * @throws DoctrineDriverException
     * @throws DoctrineException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function generateCredentialRequest(): void
    {
        $userId = $this->d3GetSession()->getVariable(WebauthnConf::WEBAUTHN_ADMIN_SESSION_CURRENTUSER);
        try {
            /** @var Webauthn $webauthn */
            $webauthn = $this->d3GetWebauthnObject();
            $publicKeyCredentialRequestOptions = $webauthn->getRequestOptions($userId);
            $this->d3GetSession()->setVariable(WebauthnConf::WEBAUTHN_ADMIN_LOGIN_OBJECT, $publicKeyCredentialRequestOptions);
            $this->addTplParam('webauthn_publickey_login', $publicKeyCredentialRequestOptions);
            $this->addTplParam('isAdmin', isAdmin());
        } catch (WebauthnException $e) {
            $this->d3GetSession()->setVariable(WebauthnConf::GLOBAL_SWITCH, true);
            Registry::getUtilsView()->addErrorToDisplay($e);
            $this->d3GetLoggerObject()->error($e->getDetailedErrorMessage(), ['UserId'   => $userId]);
            $this->d3GetLoggerObject()->debug($e->getTraceAsString());
            $this->getUtils()->redirect('index.php?cl=login');
        }
    }

    /**
     * @return string|null
     */
    public function d3AssertAuthn(): ?string
    {
        $myUtilsView = Registry::getUtilsView();
        /** @var d3_User_Webauthn $user */
        $user = $this->d3GetUserObject();
        $userId = $this->d3GetSession()->getVariable(WebauthnConf::WEBAUTHN_ADMIN_SESSION_CURRENTUSER);
        $selectedProfile = Registry::getRequest()->getRequestEscapedParameter('profile');

        try {
            $error = Registry::getRequest()->getRequestEscapedParameter('error');
            if (strlen((string) $error)) {
                /** @var WebauthnGetException $e */
                $e = oxNew(WebauthnGetException::class, $error);
                throw $e;
            }

            $credential = Registry::getRequest()->getRequestEscapedParameter('credential');
            if (strlen((string) $credential)) {
                $webAuthn = $this->d3GetWebauthnObject();
                $webAuthn->assertAuthn($credential);

                $user->load($userId);
                $session = $this->d3GetSession();
                $adminProfiles = $session->getVariable("aAdminProfiles");
                $session->initNewSession();
                $session->setVariable("aAdminProfiles", $adminProfiles);
                $session->setVariable(WebauthnConf::OXID_ADMIN_AUTH, $userId);

                $cookie = Registry::getUtilsServer()->getOxCookie();
                if ($cookie === null) {
                    throw oxNew(CookieException::class, 'ERROR_MESSAGE_COOKIE_NOCOOKIE');
                }

                if ($user->oxuser__oxrights->value === 'user') {
                    throw oxNew(UserException::class, 'ERROR_MESSAGE_USER_NOVALIDLOGIN');
                }
                $iSubshop = (int) $user->oxuser__oxrights->value;
                if ($iSubshop) {
                    Registry::getSession()->setVariable("shp", $iSubshop);
                    Registry::getSession()->setVariable('currentadminshop', $iSubshop);
                    Registry::getConfig()->setShopId($iSubshop);
                }

                //execute onAdminLogin() event
                $oEvenHandler = oxNew(SystemEventHandler::class);
                $oEvenHandler->onAdminLogin(Registry::getConfig()->getShopId());

                /** @var d3_LoginController_Webauthn $loginController */
                $loginController = oxNew(LoginController::class);
                $loginController->d3webauthnAfterLogin();

                return "admin_start";
            }
        } catch (UserException $oEx) {
            $myUtilsView->addErrorToDisplay('LOGIN_ERROR');
            $oStr = getStr();
            $this->addTplParam('user', $oStr->htmlspecialchars($userId));
            $this->addTplParam('profile', $oStr->htmlspecialchars($selectedProfile));

            return null;
        } catch (CookieException $oEx) {
            $myUtilsView->addErrorToDisplay('LOGIN_NO_COOKIE_SUPPORT');
            $oStr = getStr();
            $this->addTplParam('user', $oStr->htmlspecialchars($userId));
            $this->addTplParam('profile', $oStr->htmlspecialchars($selectedProfile));

            return null;
        } catch (ConnectionException $oEx) {
            $myUtilsView->addErrorToDisplay($oEx);
        } catch (WebauthnException $e) {
            $this->d3GetUtilsViewObject()->addErrorToDisplay($e);
            $this->d3GetLoggerObject()->error($e->getDetailedErrorMessage(), ['UserId'   => $userId]);
            $this->d3GetLoggerObject()->debug($e->getTraceAsString());
            $user->logout();
        }

        return 'login';
    }

    /**
     * @return Utils
     */
    public function getUtils(): Utils
    {
        return Registry::getUtils();
    }

    /**
     * @return string|null
     */
    public function getPreviousClass(): ?string
    {
        return $this->d3GetSession()->getVariable(WebauthnConf::WEBAUTHN_ADMIN_SESSION_CURRENTCLASS);
    }

    /**
     * @return bool
     */
    public function previousClassIsOrderStep(): bool
    {
        $sClassKey = $this->getPreviousClass();
        $resolvedClass = $this->d3GetControllerClassNameResolver()->getClassNameById($sClassKey);
        $resolvedClass = $resolvedClass ?: 'start';

        /** @var FrontendController $oController */
        $oController = oxNew($resolvedClass);
        return $oController->getIsOrderStep();
    }

    /**
     * @return bool
     */
    public function getIsOrderStep(): bool
    {
        return $this->previousClassIsOrderStep();
    }
}