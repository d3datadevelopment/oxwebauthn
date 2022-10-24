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

use D3\Webauthn\Application\Model\Credential\d3PublicKeyCredentialList;
use D3\Webauthn\Application\Model\d3webauthn;
use D3\Webauthn\Application\Model\Webauthn\d3PublicKeyCredentialUserEntity;
use D3\Webauthn\Modules\Application\Model\d3_User_Webauthn;
use Exception;
use OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\UtilsView;

class d3user_webauthn extends AdminDetailsController
{
    protected $_sSaveError = null;

    protected $_sThisTemplate = 'd3user_webauthn.tpl';

    /**
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function render()
    {
        parent::render();

        $soxId = $this->getEditObjectId();

        if (isset($soxId) && $soxId != "-1") {
            /** @var d3_User_Webauthn $oUser */
            $oUser = $this->getUserObject();
            if ($oUser->load($soxId)) {
                $this->addTplParam("oxid", $oUser->getId());
            } else {
                $this->addTplParam("oxid", '-1');
            }
            $this->addTplParam("edit", $oUser);
        }

        if ($this->_sSaveError) {
            $this->addTplParam("sSaveError", $this->_sSaveError);
        }

        $this->setAuthnRegister();

        return $this->_sThisTemplate;
    }

    /**
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function setAuthnRegister()
    {
        $publicKeyCredentialCreationOptions = $this->getWebauthnObject()->setAuthnRegister($this->getEditObjectId());
        $this->addTplParam(
            'webauthn_publickey_register',
            json_encode($publicKeyCredentialCreationOptions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * @param $userId
     * @return d3PublicKeyCredentialList|object
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getCredentialList($userId)
    {
        $credentialList = oxNew(d3PublicKeyCredentialList::class);

        $oUser = $this->getUserObject();
        $oUser->load($userId);
        if ($oUser && $oUser->getId()) {
            /** @var d3PublicKeyCredentialUserEntity $userEntity */
            $userEntity = oxNew(d3PublicKeyCredentialUserEntity::class, $oUser);
            $credentialList->loadAllForUserEntity($userEntity);
        }

        return $credentialList;
    }

    /**
     * @return User
     */
    public function getUserObject()
    {
        return oxNew(User::class);
    }

    /**
     * @return d3webauthn
     */
    public function getWebauthnObject()
    {
        return oxNew(d3webauthn::class);
    }

    public function registerNewKey()
    {
        $this->getWebauthnObject()->registerNewKey(Registry::getRequest()->getRequestParameter('authn'));
    }

    /**
     * @throws Exception
     */
    public function save()
    {
        parent::save();

        $aParams = Registry::getRequest()->getRequestEscapedParameter("editval");

        try {
            /** @var d3webauthn $oWebauthn */
            $oWebauthn = $this->getWebauthnObject();
/*
            if ($oWebauthn->checkIfAlreadyExist($this->getEditObjectId())) {
                $oException = oxNew(StandardException::class, 'D3_TOTP_ALREADY_EXIST');
                throw $oException;
            };

            if ($aParams['d3totp__oxid']) {
                $oWebauthn->load($aParams['d3totp__oxid']);
            } else {
                $aParams['d3totp__usetotp'] = 1;
                $seed = Registry::getRequest()->getRequestEscapedParameter("secret");
                $otp = Registry::getRequest()->getRequestEscapedParameter("otp");

                $oWebauthn->saveSecret($seed);
                $oWebauthn->assign($aParams);
                $oWebauthn->verify($otp, $seed);
                $oWebauthn->setId();
            }
            $oWebauthn->save();
*/
        } catch (Exception $oExcp) {
            $this->_sSaveError = $oExcp->getMessage();
        }
    }

    /**
     * @throws DatabaseConnectionException
     */
    public function delete()
    {
        $aParams = Registry::getRequest()->getRequestEscapedParameter("editval");

        /** @var d3webauthn $oWebauthn */
        $oWebauthn = $this->getWebauthnObject();
        if ($aParams['d3totp__oxid']) {
            $oWebauthn->load($aParams['d3totp__oxid']);
            $oWebauthn->delete();
            Registry::get(UtilsView::class)->addErrorToDisplay('D3_TOTP_REGISTERDELETED');
        }
    }
}