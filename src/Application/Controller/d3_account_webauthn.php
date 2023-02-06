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

use Assert\Assert;
use Assert\AssertionFailedException;
use D3\TestingTools\Production\IsMockable;
use D3\Webauthn\Application\Controller\Traits\accountTrait;
use D3\Webauthn\Application\Model\Credential\PublicKeyCredential;
use D3\Webauthn\Application\Model\Credential\PublicKeyCredentialList;
use D3\Webauthn\Application\Model\Exceptions\WebauthnCreateException;
use D3\Webauthn\Application\Model\Exceptions\WebauthnException;
use D3\Webauthn\Application\Model\Webauthn;
use Doctrine\DBAL\Driver\Exception as DoctrineDriverException;
use Doctrine\DBAL\Exception as DoctrineException;
use OxidEsales\Eshop\Application\Controller\AccountController;
use OxidEsales\Eshop\Core\Language;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Core\SeoEncoder;
use OxidEsales\Eshop\Core\UtilsView;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class d3_account_webauthn extends AccountController
{
    use accountTrait;
    use IsMockable;

    protected $_sThisTemplate = 'd3_account_webauthn.tpl';

    /**
     * @return string
     */
    public function render(): string
    {
        $sRet = parent::render();

        $this->addTplParam('user', $this->getUser());
        $this->addTplParam('readonly', !(d3GetOxidDIC()->get(Webauthn::class)->isAvailable()));

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
        $credentialList = d3GetOxidDIC()->get(PublicKeyCredentialList::class);
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
            d3GetOxidDIC()->get('d3ox.webauthn.'.LoggerInterface::class)->error($e->getDetailedErrorMessage(), ['UserId: ' => $this->getUser()->getId()]);
            d3GetOxidDIC()->get('d3ox.webauthn.'.LoggerInterface::class)->debug($e->getTraceAsString());
            d3GetOxidDIC()->get('d3ox.webauthn.'.UtilsView::class)->addErrorToDisplay($e);
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
     * @throws DoctrineDriverException
     * @throws DoctrineException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @return void
     */
    public function setAuthnRegister(): void
    {
        $publicKeyCredentialCreationOptions = d3GetOxidDIC()->get(Webauthn::class)
            ->getCreationOptions($this->getUser());

        d3GetOxidDIC()->get('d3ox.webauthn.'.LoggerInterface::class)->debug((string) $publicKeyCredentialCreationOptions);

        $this->addTplParam('webauthn_publickey_create', $publicKeyCredentialCreationOptions);
        $this->addTplParam('isAdmin', isAdmin());
        $this->addTplParam('keyname', Registry::getRequest()->getRequestEscapedParameter('credenialname'));
    }

    /**
     * @return void
     * @throws DoctrineDriverException
     * @throws DoctrineException
     * @throws Throwable
     */
    public function saveAuthn(): void
    {
        try {
            /** @var Request $request */
            $request = d3GetOxidDIC()->get('d3ox.webauthn.'.Request::class);
            $error = $request->getRequestEscapedParameter('error');
            if (strlen((string) $error)) {
                d3GetOxidDIC()->get('d3ox.webauthn.'.LoggerInterface::class)->debug($error);
                /** @var WebauthnCreateException $e */
                $e = oxNew(WebauthnCreateException::class, $error);
                throw $e;
            }

            $credential = d3GetOxidDIC()->get('d3ox.webauthn.'.Request::class)->getRequestEscapedParameter('credential');
            Assert::that($credential)->minLength(1, 'Credential should not be empty.');
            d3GetOxidDIC()->get('d3ox.webauthn.'.LoggerInterface::class)->debug($credential);
            $webauthn = d3GetOxidDIC()->get(Webauthn::class);
            $webauthn->saveAuthn($credential, d3GetOxidDIC()->get('d3ox.webauthn.'.Request::class)->getRequestEscapedParameter('keyname'));
        } catch (WebauthnException $e) {
            d3GetOxidDIC()->get('d3ox.webauthn.'.LoggerInterface::class)->error(
                $e->getDetailedErrorMessage(),
                ['UserId' => $this->getUser()->getId()]
            );
            d3GetOxidDIC()->get('d3ox.webauthn.'.LoggerInterface::class)->debug($e->getTraceAsString());
            d3GetOxidDIC()->get('d3ox.webauthn.'.UtilsView::class)->addErrorToDisplay($e);
        } catch (AssertionFailedException $e) {
            /** @var Language $language */
            $language = d3GetOxidDIC()->get('d3ox.webauthn.'.Language::class);
            d3GetOxidDIC()->get('d3ox.webauthn.'.LoggerInterface::class)->error(
                $e->getMessage(),
                ['UserId' => $this->getUser()->getId()]
            );
            d3GetOxidDIC()->get('d3ox.webauthn.'.LoggerInterface::class)->debug($e->getTraceAsString());
            d3GetOxidDIC()->get('d3ox.webauthn.'.UtilsView::class)->addErrorToDisplay(
                $language->translateString('D3_WEBAUTHN_ERR_NOTCREDENTIALNOTSAVEABLE')
            );
        }
    }

    /**
     * @return void
     */
    public function deleteKey(): void
    {
        $deleteId = d3GetOxidDIC()->get('d3ox.webauthn.'.Request::class)->getRequestEscapedParameter('deleteoxid');
        if ($deleteId) {
            $credential = d3GetOxidDIC()->get(PublicKeyCredential::class);
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
        $aPath['title'] = Registry::getLang()->translateString(
            'MY_ACCOUNT',
            (int) $iBaseLanguage,
            false
        );
        $aPath['link'] = $oSeoEncoder->getStaticUrl($this->getViewConfig()->getSelfLink() . "cl=account");
        $aPaths[] = $aPath;

        $aPath['title'] = Registry::getLang()->translateString(
            'D3_WEBAUTHN_ACCOUNT',
            (int) $iBaseLanguage,
            false
        );
        $aPath['link'] = $this->getLink();
        $aPaths[] = $aPath;

        return $aPaths;
    }
}
