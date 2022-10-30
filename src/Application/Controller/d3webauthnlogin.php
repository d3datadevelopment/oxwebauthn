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

namespace D3\Webauthn\Application\Controller;

use Assert\AssertionFailedException;
use D3\Webauthn\Application\Model\Webauthn;
use D3\Webauthn\Application\Model\WebauthnConf;
use D3\Webauthn\Application\Model\WebauthnErrors;
use D3\Webauthn\Application\Model\WebauthnException;
use D3\Webauthn\Modules\Application\Component\d3_webauthn_UserComponent;
use D3\Webauthn\Modules\Application\Model\d3_User_Webauthn;
use Doctrine\DBAL\Exception as DoctrineException;
use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Utils;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class d3webauthnlogin extends FrontendController
{
    protected $_sThisTemplate = 'd3webauthnlogin.tpl';

    /**
     * @return null
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function render()
    {
        if (Registry::getSession()->hasVariable(WebauthnConf::WEBAUTHN_SESSION_AUTH) ||
            !Registry::getSession()->hasVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER)
        ) {
            $this->getUtils()->redirect('index.php?cl=start');
            if (!defined('OXID_PHP_UNIT')) {
                // @codeCoverageIgnoreStart
                exit;
                // @codeCoverageIgnoreEnd
            }
        }

        $this->generateCredentialRequest();

        $this->addTplParam('navFormParams', Registry::getSession()->getVariable(WebauthnConf::WEBAUTHN_SESSION_NAVFORMPARAMS));

        return parent::render();
    }

    /**
     * @return void
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws DoctrineException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function generateCredentialRequest()
    {
        try {
            /** @var Webauthn $webauthn */
            $webauthn = oxNew(Webauthn::class);
            $userId = Registry::getSession()->getVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER);
            $publicKeyCredentialRequestOptions = $webauthn->getRequestOptions($userId);
            $this->addTplParam('webauthn_publickey_login', $publicKeyCredentialRequestOptions);
        } catch (WebauthnException $e) {
            // ToDo: write exc msg to display and log
        }

        $this->addTplParam('isAdmin', isAdmin());
    }

    public function assertAuthn()
    {
        /** @var d3_User_Webauthn $user */
        $user = oxNew(User::class);

        try {
            if (strlen(Registry::getRequest()->getRequestEscapedParameter('error'))) {
                $errors = oxNew(WebauthnErrors::class);
                throw oxNew(
                    StandardException::class,
                    $errors->translateError(Registry::getRequest()->getRequestEscapedParameter('error'))
                );
            }

            if (strlen(Registry::getRequest()->getRequestEscapedParameter('credential'))) {
                $credential = Registry::getRequest()->getRequestEscapedParameter('credential');
                $webAuthn = oxNew(Webauthn::class);
                $webAuthn->assertAuthn($credential);
                $user->load(Registry::getSession()->getVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER));

                /** @var d3_webauthn_UserComponent $userCmp */
                $userCmp = $this->getComponent('oxcmp_user');
                $userCmp->d3WebauthnRelogin($user, $credential);
            }

        } catch (AssertionFailedException|WebauthnException $e) {
            Registry::getUtilsView()->addErrorToDisplay($e->getMessage());

            $user->logout();
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