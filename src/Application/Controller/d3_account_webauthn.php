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
use D3\Webauthn\Application\Model\Webauthn;
use D3\Webauthn\Application\Model\WebauthnConf;
use D3\Webauthn\Application\Model\WebauthnErrors;
use D3\Webauthn\Application\Model\WebauthnException;
use OxidEsales\Eshop\Application\Controller\AccountController;
use OxidEsales\Eshop\Core\Registry;

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

    public function requestNewCredential()
    {
        $this->setPageType('requestnew');
        $this->setAuthnRegister();
    }

    public function setPageType($pageType)
    {
        $this->addTplParam('pageType', $pageType);
    }

    public function setAuthnRegister()
    {
        try {
            $authn = oxNew(Webauthn::class);
            $publicKeyCredentialCreationOptions = $authn->getCreationOptions($this->getUser());

            $this->addTplParam(
                'webauthn_publickey_create',
                $publicKeyCredentialCreationOptions
            );
        } catch (WebauthnException $e) {
            // ToDo: add exc msg to display and log
        }

        $this->addTplParam('isAdmin', isAdmin());
        $this->addTplParam('keyname', Registry::getRequest()->getRequestEscapedParameter('credenialname'));
    }

    public function saveAuthn()
    {
        if (strlen(Registry::getRequest()->getRequestEscapedParameter('error'))) {
            $errors = oxNew(WebauthnErrors::class);
            Registry::getUtilsView()->addErrorToDisplay(
                $errors->translateError(Registry::getRequest()->getRequestEscapedParameter('error'), WebauthnConf::TYPE_CREATE)
            );
        }

        if (strlen(Registry::getRequest()->getRequestEscapedParameter('credential'))) {
            /** @var Webauthn $webauthn */
            $webauthn = oxNew(Webauthn::class);
            $webauthn->saveAuthn(
                Registry::getRequest()->getRequestEscapedParameter('credential'),
                Registry::getRequest()->getRequestEscapedParameter('keyname')
            );
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