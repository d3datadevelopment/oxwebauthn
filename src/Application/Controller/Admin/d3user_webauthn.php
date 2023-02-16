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

namespace D3\Webauthn\Application\Controller\Admin;

use Assert\Assert;
use Assert\AssertionFailedException;
use Assert\InvalidArgumentException;
use D3\TestingTools\Production\IsMockable;
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
use OxidEsales\Eshop\Core\Utils;
use OxidEsales\Eshop\Core\UtilsView;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class d3user_webauthn extends AdminDetailsController
{
    use IsMockable;

    protected $_sSaveError = null;

    protected $_sThisTemplate = 'd3user_webauthn.tpl';

    /**
     * @return string
     */
    public function render(): string
    {
        /** @var Webauthn $webauthn */
        $webauthn = d3GetOxidDIC()->get(Webauthn::class);
        $this->addTplParam('readonly', !$webauthn->isAvailable());

        $this->d3CallMockableFunction([AdminDetailsController::class, 'render']);

        $soxId = $this->getEditObjectId();

        if ($soxId != "-1") {
            /** @var d3_User_Webauthn $oUser */
            $oUser = d3GetOxidDIC()->get('d3ox.webauthn.'.User::class);
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

    /**
     * @return void
     */
    public function requestNewCredential(): void
    {
        try {
            $this->setPageType('requestnew');
            $this->setAuthnRegister();
        } catch (AssertionFailedException|ContainerExceptionInterface|NotFoundExceptionInterface|DoctrineDriverException $e) {
            d3GetOxidDIC()->get('d3ox.webauthn.'.UtilsView::class)->addErrorToDisplay($e->getMessage());
            d3GetOxidDIC()->get('d3ox.webauthn.'.LoggerInterface::class)->error($e->getMessage(), ['UserId' => $this->getEditObjectId()]);
            d3GetOxidDIC()->get('d3ox.webauthn.'.LoggerInterface::class)->debug($e->getTraceAsString());
            d3GetOxidDIC()->get('d3ox.webauthn.'.Utils::class)->redirect('index.php?cl=d3user_webauthn');
        }
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function saveAuthn(): void
    {
        try {
            $error = Registry::getRequest()->getRequestEscapedParameter('error');
            if (strlen((string) $error)) {
                d3GetOxidDIC()->get('d3ox.webauthn.'.LoggerInterface::class)->debug($error);
                /** @var WebauthnCreateException $e */
                $e = oxNew(WebauthnCreateException::class, $error);
                throw $e;
            }

            $credential = Registry::getRequest()->getRequestEscapedParameter('credential');
            Assert::that($credential)->minLength(1, 'Credential should not be empty.');

            $keyname = Registry::getRequest()->getRequestEscapedParameter('keyname');
            Assert::that($keyname)->minLength(1, 'Key name should not be empty.');

            d3GetOxidDIC()->get('d3ox.webauthn.'.LoggerInterface::class)->debug($credential);
            /** @var Webauthn $webauthn */
            $webauthn = d3GetOxidDIC()->get(Webauthn::class);
            $webauthn->saveAuthn($credential, $keyname);
        } catch (WebauthnException $e) {
            d3GetOxidDIC()->get('d3ox.webauthn.'.LoggerInterface::class)->error($e->getDetailedErrorMessage(), ['UserId' => $this->getEditObjectId()]);
            d3GetOxidDIC()->get('d3ox.webauthn.'.LoggerInterface::class)->debug($e->getTraceAsString());
            d3GetOxidDIC()->get('d3ox.webauthn.'.UtilsView::class)->addErrorToDisplay($e);
        } catch (Exception|NotFoundExceptionInterface|ContainerExceptionInterface|DoctrineDriverException|AssertionFailedException $e) {
            d3GetOxidDIC()->get('d3ox.webauthn.'.LoggerInterface::class)->error($e->getMessage(), ['UserId' => $this->getEditObjectId()]);
            d3GetOxidDIC()->get('d3ox.webauthn.'.LoggerInterface::class)->debug($e->getTraceAsString());
            d3GetOxidDIC()->get('d3ox.webauthn.'.UtilsView::class)->addErrorToDisplay($e->getMessage());
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
     * @throws InvalidArgumentException
     */
    public function setAuthnRegister(): void
    {
        /** @var Webauthn $authn */
        $authn = d3GetOxidDIC()->get(Webauthn::class);

        /** @var User $user */
        $user = d3GetOxidDIC()->get('d3ox.webauthn.'.User::class);
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
        /** @var User $oUser */
        $oUser = d3GetOxidDIC()->get('d3ox.webauthn.'.User::class);
        $oUser->load($userId);

        $publicKeyCredentials = d3GetOxidDIC()->get(PublicKeyCredentialList::class);
        return $publicKeyCredentials->getAllFromUser($oUser)->getArray();
    }

    /**
     * @return void
     */
    public function deleteKey(): void
    {
        $credential = d3GetOxidDIC()->get(PublicKeyCredential::class);
        $credential->delete(Registry::getRequest()->getRequestEscapedParameter('deleteoxid'));
    }
}
