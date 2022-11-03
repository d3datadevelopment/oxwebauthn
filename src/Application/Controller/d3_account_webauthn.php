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
     */
    public function requestNewCredential()
    {
        try {
            $this->setAuthnRegister();
            $this->setPageType('requestnew');
        } catch (WebauthnException $e) {
            Registry::getLogger()->error('webauthn register: '.$e->getDetailedErrorMessage(), ['UserId: ' => $this->getUser()->getId()]);
            Registry::getLogger()->debug($e->getTraceAsString());
            Registry::getUtilsView()->addErrorToDisplay($e);
        }
    }

    public function setPageType($pageType)
    {
        $this->addTplParam('pageType', $pageType);
    }

    /**
     * @throws WebauthnException
     * @throws DoctrineDriverException
     * @throws DoctrineException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function setAuthnRegister()
    {
        $authn = oxNew(Webauthn::class);
        $publicKeyCredentialCreationOptions = $authn->getCreationOptions($this->getUser());

        $this->addTplParam(
            'webauthn_publickey_create',
            $publicKeyCredentialCreationOptions
        );

        $this->addTplParam('isAdmin', isAdmin());
        $this->addTplParam('keyname', Registry::getRequest()->getRequestEscapedParameter('credenialname'));
    }

    public function saveAuthn()
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

    public function deleteKey()
    {
        if (Registry::getRequest()->getRequestEscapedParameter('deleteoxid')) {
            /** @var PublicKeyCredential $credential */
            $credential = oxNew(PublicKeyCredential::class);
            $credential->delete(Registry::getRequest()->getRequestEscapedParameter('deleteoxid'));
        }
    }
}