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
use OxidEsales\Eshop\Core\UtilsView;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;

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

        $this->addTplParam('user', $this->getUser());
        $this->addTplParam('readonly',  (bool) !($this->getWebauthnObject()->isAvailable()));

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
        $credentialList = $this->getPublicKeyCredentialListObject();
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
            $this->getLogger()->error($e->getDetailedErrorMessage(), ['UserId: ' => $this->getUser()->getId()]);
            $this->getLogger()->debug($e->getTraceAsString());
            $this->getUtilsViewObject()->addErrorToDisplay($e);
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
        $publicKeyCredentialCreationOptions = $this->getWebauthnObject()->getCreationOptions($this->getUser());

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
            $error = Registry::getRequest()->getRequestEscapedParameter('error');
            if (strlen((string) $error)) {
                /** @var WebauthnCreateException $e */
                $e = oxNew( WebauthnCreateException::class, $error);
                throw $e;
            }

            $credential = Registry::getRequest()->getRequestEscapedParameter('credential');
            if (strlen((string) $credential)) {
                /** @var Webauthn $webauthn */
                $webauthn = $this->getWebauthnObject();
                $webauthn->saveAuthn($credential, Registry::getRequest()->getRequestEscapedParameter('keyname'));
            }
        } catch (WebauthnException $e) {
            $this->getUtilsViewObject()->addErrorToDisplay( $e );
        }
    }

    /**
     * @return void
     */
    public function deleteKey(): void
    {
        $deleteId = Registry::getRequest()->getRequestEscapedParameter('deleteoxid');
        if ($deleteId) {
            /** @var PublicKeyCredential $credential */
            $credential = $this->getPublicKeyCredentialObject();
            $credential->delete($deleteId);
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

    /**
     * @return Webauthn
     */
    public function getWebauthnObject(): Webauthn
    {
        return oxNew(Webauthn::class);
    }

    /**
     * @return PublicKeyCredential
     */
    public function getPublicKeyCredentialObject(): PublicKeyCredential
    {
        return oxNew(PublicKeyCredential::class);
    }

    /**
     * @return PublicKeyCredentialList
     */
    public function getPublicKeyCredentialListObject(): PublicKeyCredentialList
    {
        return oxNew(PublicKeyCredentialList::class);
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return Registry::getLogger();
    }

    /**
     * @return UtilsView
     */
    public function getUtilsViewObject(): UtilsView
    {
        return Registry::getUtilsView();
    }
}