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

namespace D3\Webauthn\Application\Controller\Admin;

use Assert\AssertionFailedException;
use D3\Webauthn\Application\Model\Exceptions\WebauthnGetException;
use D3\Webauthn\Application\Model\Webauthn;
use D3\Webauthn\Application\Model\WebauthnConf;
use D3\Webauthn\Application\Model\Exceptions\WebauthnException;
use D3\Webauthn\Modules\Application\Component\d3_webauthn_UserComponent;
use D3\Webauthn\Modules\Application\Model\d3_User_Webauthn;
use Doctrine\DBAL\Driver\Exception as DoctrineDriverException;
use Doctrine\DBAL\Exception as DoctrineException;
use OxidEsales\Eshop\Application\Controller\Admin\AdminController;
use OxidEsales\Eshop\Application\Controller\Admin\LoginController;
use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Utils;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class d3webauthnadminlogin extends AdminController
{
    protected $_sThisTemplate = 'd3webauthnadminlogin.tpl';

    protected function _authorize(): bool
    {
        return true;
    }

    /**
     * @return null
     * @throws ContainerExceptionInterface
     * @throws DoctrineDriverException
     * @throws DoctrineException
     * @throws NotFoundExceptionInterface
     */
    public function render()
    {
        if (Registry::getSession()->hasVariable(WebauthnConf::WEBAUTHN_SESSION_AUTH) ||
            !Registry::getSession()->hasVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER)
        ) {
            $this->getUtils()->redirect('index.php?cl=admin_start');
            if (!defined('OXID_PHP_UNIT')) {
                // @codeCoverageIgnoreStart
                exit;
                // @codeCoverageIgnoreEnd
            }
        }

        $this->generateCredentialRequest();

        return parent::render();
    }

    /**
     * @return void
     * @throws DoctrineDriverException
     * @throws DoctrineException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function generateCredentialRequest()
    {
        $userId = Registry::getSession()->getVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER);
        try {
            /** @var Webauthn $webauthn */
            $webauthn = oxNew(Webauthn::class);
            $publicKeyCredentialRequestOptions = $webauthn->getRequestOptions($userId);
            Registry::getSession()->setVariable(WebauthnConf::WEBAUTHN_LOGIN_OBJECT, $publicKeyCredentialRequestOptions);
            $this->addTplParam('webauthn_publickey_login', $publicKeyCredentialRequestOptions);
            $this->addTplParam('isAdmin', isAdmin());
        } catch (WebauthnException $e) {
            Registry::getSession()->setVariable(WebauthnConf::GLOBAL_SWITCH, true);
            Registry::getUtilsView()->addErrorToDisplay($e);
            Registry::getLogger()->error('webauthn request options: '.$e->getDetailedErrorMessage(), ['UserId'   => $userId]);
            $this->getUtils()->redirect('index.php?cl=login');
        }
    }

    public function d3AssertAuthn()
    {
        /** @var d3_User_Webauthn $user */
        $user = oxNew(User::class);
        $userId = Registry::getSession()->getVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER);

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
                $user->load($userId);
                Registry::getSession()->setVariable(WebauthnConf::WEBAUTHN_SESSION_AUTH, true);

                /** @var d3_webauthn_UserComponent $userCmp */
                $loginController = oxNew(LoginController::class);
                return $loginController->checklogin();
            }
        } catch (WebauthnException $e) {
            Registry::getUtilsView()->addErrorToDisplay($e);
            Registry::getLogger()->error('Webauthn: '.$e->getDetailedErrorMessage(), ['UserId'   => $userId]);
            $user->logout();
            $this->getUtils()->redirect('index.php?cl=login');
        }

        return null;
    }

    /**
     * @return Utils
     */
    public function getUtils(): Utils
    {
        return Registry::getUtils();
    }

    public function getPreviousClass()
    {
        return Registry::getSession()->getVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTCLASS);
    }

    public function previousClassIsOrderStep(): bool
    {
        $sClassKey = Registry::getSession()->getVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTCLASS);
        $resolvedClass = Registry::getControllerClassNameResolver()->getClassNameById($sClassKey);
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
     * Returns Bread Crumb - you are here page1/page2/page3...
     *
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