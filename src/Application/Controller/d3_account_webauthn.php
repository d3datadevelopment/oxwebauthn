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

use D3\Webauthn\Application\Controller\Traits\accountTrait;
use D3\Webauthn\Application\Model\Credential\PublicKeyCredential;
use D3\Webauthn\Application\Model\Credential\PublicKeyCredentialList;
use D3\Webauthn\Application\Model\Exceptions\WebauthnCreateException;
use D3\Webauthn\Application\Model\Webauthn;
use D3\Webauthn\Application\Model\WebauthnConf;
use D3\Webauthn\Application\Model\WebauthnErrors;
use D3\Webauthn\Application\Model\Exceptions\WebauthnException;
use Doctrine\DBAL\Driver\Exception as DoctrineDriverException;
use Doctrine\DBAL\Exception as DoctrineException;
use OxidEsales\Eshop\Application\Controller\AccountController;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\SeoEncoder;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class d3_account_webauthn extends AccountController
{
    use accountTrait;

    protected $_sThisTemplate = 'd3_account_webauthn.tpl';

    /**
     * @return string
     */
    public function render(): string
    {
        $sRet = parent::render();

        // is logged in ?
        $oUser = $this->getUser();
        if (!$oUser) {
            return $this->_sThisTemplate = $this->_sThisLoginTemplate;
        }

        $this->addTplParam('user', $this->getUser());
        $this->addTplParam('readonly',  (bool) !(oxNew(Webauthn::class)->isAvailable()));

        return $sRet;
    }

    /**
     * @return publicKeyCredentialList
     * @throws ContainerExceptionInterface
     * @throws DoctrineDriverException
     * @throws DoctrineException
     * @throws NotFoundExceptionInterface
     */
    public function getCredentialList(): PublicKeyCredentialList
    {
        $oUser = $this->getUser();
        $credentialList = oxNew(PublicKeyCredentialList::class);
        return $credentialList->getAllFromUser($oUser);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws DoctrineDriverException
     * @throws DoctrineException
     * @return void
     */
    public function requestNewCredential(): void
    {
        try {
            $this->setAuthnRegister();
            $this->setPageType('requestnew');
        } catch (WebauthnException $e) {
            Registry::getLogger()->error($e->getDetailedErrorMessage(), ['UserId: ' => $this->getUser()->getId()]);
            Registry::getLogger()->debug($e->getTraceAsString());
            Registry::getUtilsView()->addErrorToDisplay($e);
        }
    }

    /**
     * @param $pageType
     * @return void
     */
    public function setPageType($pageType): void
    {
        $this->addTplParam('pageType', $pageType);
    }

    /**
     * @throws WebauthnException
     * @throws DoctrineDriverException
     * @throws DoctrineException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @return void
     */
    public function setAuthnRegister(): void
    {
        $authn = oxNew(Webauthn::class);
        $publicKeyCredentialCreationOptions = $authn->getCreationOptions($this->getUser());

        $this->addTplParam('webauthn_publickey_create', $publicKeyCredentialCreationOptions);
        $this->addTplParam('isAdmin', isAdmin());
        $this->addTplParam('keyname', Registry::getRequest()->getRequestEscapedParameter('credenialname'));
    }

    /**
     * @return void
     * @throws ContainerExceptionInterface
     * @throws DoctrineDriverException
     * @throws DoctrineException
     * @throws NotFoundExceptionInterface
     */
    public function saveAuthn(): void
    {
        try {
            if ( strlen( Registry::getRequest()->getRequestEscapedParameter( 'error' ) ) ) {
                /** @var WebauthnCreateException $e */
                $e = oxNew( WebauthnCreateException::class, Registry::getRequest()->getRequestEscapedParameter( 'error' ) );
                throw $e;
            }

            if ( strlen( Registry::getRequest()->getRequestEscapedParameter( 'credential' ) ) ) {
                /** @var Webauthn $webauthn */
                $webauthn = oxNew( Webauthn::class );
                $webauthn->saveAuthn( Registry::getRequest()->getRequestEscapedParameter( 'credential' ), Registry::getRequest()->getRequestEscapedParameter( 'keyname' ) );
            }
        } catch (WebauthnException $e) {
            Registry::getUtilsView()->addErrorToDisplay( $e );
        }
    }

    /**
     * @return void
     */
    public function deleteKey(): void
    {
        if (Registry::getRequest()->getRequestEscapedParameter('deleteoxid')) {
            /** @var PublicKeyCredential $credential */
            $credential = oxNew(PublicKeyCredential::class);
            $credential->delete(Registry::getRequest()->getRequestEscapedParameter('deleteoxid'));
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
        /** @var SeoEncoder $oSeoEncoder */
        $oSeoEncoder = Registry::getSeoEncoder();
        $aPath['title'] = Registry::getLang()->translateString('MY_ACCOUNT', $iBaseLanguage, false);
        $aPath['link'] = $oSeoEncoder->getStaticUrl($this->getViewConfig()->getSelfLink() . "cl=account");
        $aPaths[] = $aPath;

        $aPath['title'] = Registry::getLang()->translateString('D3_WEBAUTHN_ACCOUNT', $iBaseLanguage, false);
        $aPath['link'] = $this->getLink();
        $aPaths[] = $aPath;

        return $aPaths;
    }
}