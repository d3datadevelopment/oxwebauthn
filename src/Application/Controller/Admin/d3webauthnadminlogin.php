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
use D3\Webauthn\Application\Model\WebauthnAfterLogin;
use D3\Webauthn\Application\Model\WebauthnConf;
use D3\Webauthn\Application\Model\Exceptions\WebauthnException;
use D3\Webauthn\Application\Model\WebauthnLogin;
use Doctrine\DBAL\Driver\Exception as DoctrineDriverException;
use Doctrine\DBAL\Exception as DoctrineException;
use OxidEsales\Eshop\Application\Controller\Admin\AdminController;
use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Core\SystemEventHandler;
use OxidEsales\Eshop\Core\Utils;
use OxidEsales\Eshop\Core\UtilsServer;
use OxidEsales\Eshop\Core\UtilsView;
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
        if ($this->d3GetSession()->hasVariable(WebauthnConf::WEBAUTHN_ADMIN_SESSION_AUTH)) {
            $this->getUtils()->redirect('index.php?cl=admin_start');
        } elseif (!$this->d3GetSession()->hasVariable(WebauthnConf::WEBAUTHN_ADMIN_SESSION_CURRENTUSER)) {
            $this->getUtils()->redirect('index.php?cl=login');
        }

        $this->generateCredentialRequest();

        $this->addTplParam('navFormParams', $this->d3GetSession()->getVariable(WebauthnConf::WEBAUTHN_SESSION_NAVFORMPARAMS));
        $this->addTplParam('currentProfile', $this->d3GetSession()->getVariable(WebauthnConf::WEBAUTHN_ADMIN_PROFILE));
        $this->d3GetSession()->deleteVariable(WebauthnConf::WEBAUTHN_ADMIN_PROFILE);
        $this->addTplParam('currentChLanguage', $this->d3GetSession()->getVariable(WebauthnConf::WEBAUTHN_ADMIN_CHLANGUAGE));

        $afterLogin = $this->d3WebauthnGetAfterLogin();
        $afterLogin->changeLanguage();

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
            $webauthn = $this->d3GetWebauthnObject();
            $publicKeyCredentialRequestOptions = $webauthn->getRequestOptions($userId);
            $this->d3GetSession()->setVariable(WebauthnConf::WEBAUTHN_ADMIN_LOGIN_OBJECT, $publicKeyCredentialRequestOptions);
            $this->addTplParam('webauthn_publickey_login', $publicKeyCredentialRequestOptions);
            $this->addTplParam('isAdmin', isAdmin());
        } catch (WebauthnException $e) {
            $this->d3GetSession()->setVariable(WebauthnConf::GLOBAL_SWITCH, true);
            $this->d3GetUtilsViewObject()->addErrorToDisplay($e);
            $this->d3GetLoggerObject()->error($e->getDetailedErrorMessage(), ['UserId'   => $userId]);
            $this->d3GetLoggerObject()->debug($e->getTraceAsString());
            $this->getUtils()->redirect('index.php?cl=login');
        }
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
     * @return string|null
     */
    public function d3AssertAuthn(): ?string
    {
        try {
            $login = $this->getWebauthnLoginObject(
                $this->d3WebAuthnGetRequest()->getRequestEscapedParameter('credential'),
                $this->d3WebAuthnGetRequest()->getRequestEscapedParameter('error')
            );
            return $login->adminLogin(
                $this->d3WebAuthnGetRequest()->getRequestEscapedParameter('profile')
            );
        } catch (WebauthnGetException $e) {
            $this->d3GetUtilsViewObject()->addErrorToDisplay($e);
            return 'login';
        }
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
    public function d3GetPreviousClass(): ?string
    {
        return $this->d3GetSession()->getVariable(WebauthnConf::WEBAUTHN_ADMIN_SESSION_CURRENTCLASS);
    }

    /**
     * @return bool
     */
    public function previousClassIsOrderStep(): bool
    {
        $sClassKey = $this->d3GetPreviousClass();
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

    /**
     * @return WebauthnAfterLogin
     */
    public function d3WebauthnGetAfterLogin(): WebauthnAfterLogin
    {
        return oxNew(WebauthnAfterLogin::class);
    }

    /**
     * @return SystemEventHandler
     */
    public function d3WebauthnGetEventHandler(): SystemEventHandler
    {
        return oxNew(SystemEventHandler::class);
    }

    /**
     * @return Request
     */
    public function d3WebAuthnGetRequest(): Request
    {
        return Registry::getRequest();
    }

    /**
     * @return UtilsServer
     */
    public function d3WebauthnGetUtilsServer(): UtilsServer
    {
        return Registry::getUtilsServer();
    }
}