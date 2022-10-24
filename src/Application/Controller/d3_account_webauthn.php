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

use D3\Webauthn\Application\Model\Credential\d3PublicKeyCredential;
use D3\Webauthn\Application\Model\Credential\d3PublicKeyCredentialList;
use D3\Webauthn\Application\Model\d3webauthn;
use D3\Webauthn\Application\Model\Webauthn\d3PublicKeyCredentialUserEntity;
use OxidEsales\Eshop\Application\Controller\AccountController;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Registry;

class d3_account_webauthn extends AccountController
{
    protected $_sThisTemplate = 'd3_account_webauthn.tpl';

    /**
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function render()
    {
        if (Registry::getRequest()->getRequestEscapedParameter('error')) {
dumpvar(Registry::getRequest()->getRequestEscapedParameter('error'));
            Registry::getUtilsView()->addErrorToDisplay('error occured');
        }

        $sRet = parent::render();

        // is logged in ?
        $oUser = $this->getUser();
        if (!$oUser) {
            return $this->_sThisTemplate = $this->_sThisLoginTemplate;
        }

        $this->addTplParam('user', $this->getUser());

        $this->setAuthnRegister();

        return $sRet;
    }

    /**
     * @return d3PublicKeyCredentialList|object
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getCredentialList()
    {
        $credentialList = oxNew(d3PublicKeyCredentialList::class);

        $oUser = $this->getUser();
        if ($oUser) {
            /** @var d3PublicKeyCredentialUserEntity $userEntity */
            $userEntity = oxNew(d3PublicKeyCredentialUserEntity::class, $oUser);
            $credentialList->loadAllForUserEntity($userEntity);
        }

        return $credentialList;
    }

    /**
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function setAuthnRegister()
    {
        $webauthn = oxNew(d3webauthn::class);
        $publicKeyCredentialCreationOptions = $webauthn->setAuthnRegister('36944b76d6e583fe2.12734046');

        $this->addTplParam(
            'webauthn_publickey_register',
            json_encode($publicKeyCredentialCreationOptions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }

    public function registerNewKey()
    {
        $webauthn = oxNew(d3webauthn::class);
        $webauthn->registerNewKey(Registry::getRequest()->getRequestParameter('authn'));
    }

    public function deleteKey()
    {
        if (Registry::getRequest()->getRequestEscapedParameter('oxid')) {
            $credential = oxNew(d3PublicKeyCredential::class);
            $credential->delete(Registry::getRequest()->getRequestEscapedParameter('oxid'));
        }
    }
}