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

use D3\Webauthn\Application\Model\Credential\PublicKeyCredential;
use D3\Webauthn\Application\Model\Credential\PublicKeyCredentialList;
use D3\Webauthn\Application\Model\Exceptions\WebauthnCreateException;
use D3\Webauthn\Application\Model\Exceptions\WebauthnException;
use D3\Webauthn\Application\Model\Webauthn;
use D3\Webauthn\Modules\Application\Model\d3_User_Webauthn;
use Doctrine\DBAL\Driver\Exception as DoctrineDriverException;
use Doctrine\DBAL\Exception as DoctrineException;
use Exception;
use OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Registry;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class d3user_webauthn extends AdminDetailsController
{
    protected $_sSaveError = null;

    protected $_sThisTemplate = 'd3user_webauthn.tpl';

    /**
     * @return string
     */
    public function render(): string
    {
        $this->addTplParam('readonly', !(oxNew(Webauthn::class)->isAvailable()));

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

        return $this->_sThisTemplate;
    }

    public function requestNewCredential()
    {
        try {
            $this->setPageType( 'requestnew' );
            $this->setAuthnRegister();
        } catch (Exception|ContainerExceptionInterface|NotFoundExceptionInterface|DoctrineDriverException $e) {
            Registry::getUtilsView()->addErrorToDisplay($e);
            Registry::getLogger()->error('webauthn creation request: '.$e->getMessage(), ['UserId' => $this->getEditObjectId()]);
            Registry::getLogger()->debug($e->getTraceAsString());
            Registry::getUtils()->redirect('index.php?cl=d3user_webauthn');
        }
    }

    public function saveAuthn()
    {
        try {
            if ( strlen( Registry::getRequest()->getRequestEscapedParameter( 'error' ) ) ) {
                /** @var WebauthnCreateException $e */
                $e = oxNew(WebauthnCreateException::class, Registry::getRequest()->getRequestEscapedParameter( 'error' ));
                throw $e;
            }

            if ( strlen( Registry::getRequest()->getRequestEscapedParameter( 'credential' ) ) ) {
                /** @var Webauthn $webauthn */
                $webauthn = oxNew( Webauthn::class );
                $webauthn->saveAuthn( Registry::getRequest()->getRequestEscapedParameter( 'credential' ), Registry::getRequest()->getRequestEscapedParameter( 'keyname' ) );
            }
        } catch (WebauthnException|Exception|NotFoundExceptionInterface|ContainerExceptionInterface|DoctrineDriverException $e) {
            Registry::getLogger()->error($e->getDetailedErrorMessage(), ['UserId' => $this->getEditObjectId()]);
            Registry::getLogger()->debug($e->getTraceAsString());
            Registry::getUtilsView()->addErrorToDisplay($e);
        }
    }

    public function setPageType($pageType)
    {
        $this->addTplParam('pageType', $pageType);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws DoctrineDriverException
     * @throws NotFoundExceptionInterface
     * @throws DoctrineException
     */
    public function setAuthnRegister()
    {
        $authn = oxNew(Webauthn::class);

        $user = $this->getUserObject();
        $user->load($this->getEditObjectId());
        $publicKeyCredentialCreationOptions = $authn->getCreationOptions($user);

        $this->addTplParam(
            'webauthn_publickey_create',
            $publicKeyCredentialCreationOptions
        );

        $this->addTplParam('isAdmin', isAdmin());
        $this->addTplParam('keyname', Registry::getRequest()->getRequestEscapedParameter('credenialname'));
    }

    /**
     * @param $userId
     *
     * @return array
     * @throws ContainerExceptionInterface
     * @throws DoctrineDriverException
     * @throws DoctrineException
     * @throws NotFoundExceptionInterface
     */
    public function getCredentialList($userId): array
    {
        $oUser = $this->getUserObject();
        $oUser->load($userId);

        $publicKeyCrendetials = oxNew(PublicKeyCredentialList::class);
        return $publicKeyCrendetials->getAllFromUser($oUser)->getArray();
    }

    /**
     * @return User
     */
    public function getUserObject(): User
    {
        return oxNew(User::class);
    }

    public function deleteKey()
    {
        /** @var PublicKeyCredential $credential */
        $credential = oxNew(PublicKeyCredential::class);
        $credential->delete(Registry::getRequest()->getRequestEscapedParameter('deleteoxid'));
    }
}