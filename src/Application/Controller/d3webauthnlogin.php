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

declare(strict_types=1);

namespace D3\Webauthn\Application\Controller;

use D3\Webauthn\Application\Model\Webauthn;
use D3\Webauthn\Application\Model\WebauthnConf;
use D3\Webauthn\Application\Model\Exceptions\WebauthnException;
use Doctrine\DBAL\Driver\Exception as DoctrineDriverException;
use Doctrine\DBAL\Exception as DoctrineException;
use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Utils;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class d3webauthnlogin extends FrontendController
{
    protected $_sThisTemplate = 'd3webauthnlogin.tpl';

    /**
     * @return array
     */
    public function getNavigationParams(): array
    {
        $navparams = Registry::getSession()->getVariable(
            WebauthnConf::WEBAUTHN_SESSION_NAVPARAMS
        );

        return array_merge(
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
     * @throws DoctrineDriverException
     * @throws DoctrineException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function generateCredentialRequest(): void
    {
        $userId = Registry::getSession()->getVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER);

        try {
            /** @var Webauthn $webauthn */
            $webauthn = oxNew(Webauthn::class);
            $publicKeyCredentialRequestOptions = $webauthn->getRequestOptions($userId);
            $this->addTplParam('webauthn_publickey_login', $publicKeyCredentialRequestOptions);
            $this->addTplParam('isAdmin', isAdmin());
        } catch (WebauthnException $e) {
            Registry::getSession()->setVariable(WebauthnConf::GLOBAL_SWITCH, true);
            Registry::getLogger()->error($e->getDetailedErrorMessage(), ['UserId' => $userId]);
            Registry::getLogger()->debug($e->getTraceAsString());
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
        return Registry::getSession()->getVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTCLASS);
    }

    /**
     * @return bool
     */
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