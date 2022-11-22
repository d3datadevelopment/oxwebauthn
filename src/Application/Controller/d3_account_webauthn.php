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
use D3\Webauthn\Application\Controller\Traits\helpersTrait;
use D3\Webauthn\Application\Model\Credential\PublicKeyCredentialList;
use D3\Webauthn\Application\Model\Exceptions\WebauthnCreateException;
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
    use helpersTrait;

    protected $_sThisTemplate = 'd3_account_webauthn.tpl';

    /**
     * @return string
     */
    public function render(): string
    {
        $sRet = parent::render();

        $this->addTplParam('user', $this->getUser());
        $this->addTplParam('readonly', !($this->d3GetWebauthnObject()->isAvailable()));

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
        $credentialList = $this->d3GetPublicKeyCredentialListObject();
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
            $this->d3GetLoggerObject()->error($e->getDetailedErrorMessage(), ['UserId: ' => $this->getUser()->getId()]);
            $this->d3GetLoggerObject()->debug($e->getTraceAsString());
            $this->d3GetUtilsViewObject()->addErrorToDisplay($e);
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
        $publicKeyCredentialCreationOptions = $this->d3GetWebauthnObject()->getCreationOptions($this->getUser());

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
                $webauthn = $this->d3GetWebauthnObject();
                $webauthn->saveAuthn($credential, Registry::getRequest()->getRequestEscapedParameter('keyname'));
            }
        } catch (WebauthnException $e) {
            $this->d3GetUtilsViewObject()->addErrorToDisplay( $e );
        }
    }

    /**
     * @return void
     */
    public function deleteKey(): void
    {
        $deleteId = Registry::getRequest()->getRequestEscapedParameter('deleteoxid');
        if ($deleteId) {
            $credential = $this->d3GetPublicKeyCredentialObject();
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
}