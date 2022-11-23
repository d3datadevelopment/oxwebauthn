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

namespace D3\Webauthn\Application\Controller;

use D3\TestingTools\Production\IsMockable;
use D3\Webauthn\Application\Controller\Traits\helpersTrait;
use D3\Webauthn\Application\Model\Exceptions\WebauthnGetException;
use D3\Webauthn\Application\Model\Webauthn;
use D3\Webauthn\Application\Model\WebauthnConf;
use D3\Webauthn\Application\Model\Exceptions\WebauthnException;
use D3\Webauthn\Modules\Application\Model\d3_User_Webauthn;
use Doctrine\DBAL\Driver\Exception as DoctrineDriverException;
use Doctrine\DBAL\Exception as DoctrineException;
use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Utils;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class d3webauthnlogin extends FrontendController
{
    use IsMockable;
    use helpersTrait;

    protected $_sThisTemplate = 'd3webauthnlogin.tpl';

    /**
     * @return array
     */
    public function getNavigationParams(): array
    {;
        $navparams = $this->d3GetSession()->getVariable(
            WebauthnConf::WEBAUTHN_SESSION_NAVPARAMS
        );

        return array_merge(
            $this->d3CallMockableParent('getNavigationParams'),
            $navparams,
            ['cl' => $navparams['actcontrol']]
        );
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
        if ($this->d3GetSession()->hasVariable(WebauthnConf::WEBAUTHN_SESSION_AUTH) ||
            !$this->d3GetSession()->hasVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER)
        ) {
            $this->getUtils()->redirect('index.php?cl=start');
            if (!defined('OXID_PHP_UNIT')) {
                // @codeCoverageIgnoreStart
                exit;
                // @codeCoverageIgnoreEnd
            }
        }

        $this->generateCredentialRequest();

        $this->addTplParam('navFormParams', $this->d3GetSession()->getVariable(WebauthnConf::WEBAUTHN_SESSION_NAVFORMPARAMS));

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
        $userId = $this->d3GetSession()->getVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER);

        try {
            /** @var Webauthn $webauthn */
            $webauthn = $this->d3GetWebauthnObject();
            $publicKeyCredentialRequestOptions = $webauthn->getRequestOptions($userId);
            $this->addTplParam('webauthn_publickey_login', $publicKeyCredentialRequestOptions);
            $this->addTplParam('isAdmin', isAdmin());
        } catch (WebauthnException $e) {
            $this->d3GetSession()->setVariable(WebauthnConf::GLOBAL_SWITCH, true);
            $this->d3GetLoggerObject()->error($e->getDetailedErrorMessage(), ['UserId' => $userId]);
            $this->d3GetLoggerObject()->debug($e->getTraceAsString());
            Registry::getUtilsView()->addErrorToDisplay($e);
            $this->getUtils()->redirect('index.php?cl=start');
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
    public function getPreviousClass(): ?string
    {
        return $this->d3GetSession()->getVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTCLASS);
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

    /**
     * @return void
     */
    public function d3AssertAuthn(): void
    {
        /** @var d3_User_Webauthn $user */
        $user = $this->d3GetUserObject();
        $userId = $this->d3GetSession()->getVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER);

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

                // relogin, don't extract from this try block
                $setSessionCookie = Registry::getRequest()->getRequestParameter('lgn_cook');
                $this->d3GetSession()->setVariable(WebauthnConf::WEBAUTHN_SESSION_AUTH, $credential);
                $this->d3GetSession()->setVariable(WebauthnConf::OXID_FRONTEND_AUTH, $user->getId());
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
        } catch (WebauthnException $e) {
            $this->d3GetUtilsViewObject()->addErrorToDisplay($e);
            $this->d3GetLoggerObject()->error($e->getDetailedErrorMessage(), ['UserId'   => $userId]);
            $this->d3GetLoggerObject()->debug($e->getTraceAsString());
            $user->logout();
        }
    }

    /**
     * @return array
     */
    public function getBreadCrumb(): array
    {
        $aPaths = [];
        $aPath = [];
        $iBaseLanguage = Registry::getLang()->getBaseLanguage();
        $aPath['title'] = Registry::getLang()->translateString('D3_WEBAUTHN_BREADCRUMB', $iBaseLanguage, false);
        $aPath['link'] = $this->getLink();

        $aPaths[] = $aPath;

        return $aPaths;
    }
}