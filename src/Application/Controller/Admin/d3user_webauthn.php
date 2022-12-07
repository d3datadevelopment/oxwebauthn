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
        $this->addTplParam('readonly', !$this->d3GetMockableOxNewObject(Webauthn::class)->isAvailable());

        $this->d3CallMockableFunction([AdminDetailsController::class, 'render']);

        $soxId = $this->getEditObjectId();

        if (isset($soxId) && $soxId != "-1") {
            /** @var d3_User_Webauthn $oUser */
            $oUser = $this->d3GetMockableOxNewObject(User::class);
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
            $this->setPageType( 'requestnew' );
            $this->setAuthnRegister();
        } catch (Exception|ContainerExceptionInterface|NotFoundExceptionInterface|DoctrineDriverException $e) {
            $this->d3GetMockableRegistryObject(UtilsView::class)->addErrorToDisplay($e);
            $this->d3GetMockableLogger()->error($e->getMessage(), ['UserId' => $this->getEditObjectId()]);
            $this->d3GetMockableLogger()->debug($e->getTraceAsString());
            $this->d3GetMockableRegistryObject(Utils::class)->redirect('index.php?cl=d3user_webauthn');
        }
    }

    /**
     * @return void
     */
    public function saveAuthn(): void
    {
        try {
            $error = Registry::getRequest()->getRequestEscapedParameter('error');
            if ( strlen((string) $error) ) {
                /** @var WebauthnCreateException $e */
                $e = oxNew(WebauthnCreateException::class, $error);
                throw $e;
            }

            $credential = Registry::getRequest()->getRequestEscapedParameter('credential');
            if ( strlen((string) $credential) ) {
                $webauthn = $this->d3GetMockableOxNewObject(Webauthn::class);
                $webauthn->saveAuthn($credential, Registry::getRequest()->getRequestEscapedParameter( 'keyname' ) );
            }
        } catch (WebauthnException|Exception|NotFoundExceptionInterface|ContainerExceptionInterface|DoctrineDriverException $e) {
            $this->d3GetMockableLogger()->error($e->getDetailedErrorMessage(), ['UserId' => $this->getEditObjectId()]);
            $this->d3GetMockableLogger()->debug($e->getTraceAsString());
            $this->d3GetMockableRegistryObject(UtilsView::class)->addErrorToDisplay($e);
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
     */
    public function setAuthnRegister(): void
    {
        $authn = $this->d3GetMockableOxNewObject(Webauthn::class);

        $user = $this->d3GetMockableOxNewObject(User::class);
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
        $oUser = $this->d3GetMockableOxNewObject(User::class);
        $oUser->load($userId);

        $publicKeyCredentials = $this->d3GetMockableOxNewObject(PublicKeyCredentialList::class);
        return $publicKeyCredentials->getAllFromUser($oUser)->getArray();
    }

    /**
     * @return void
     */
    public function deleteKey(): void
    {
        $credential = $this->d3GetMockableOxNewObject(PublicKeyCredential::class);
        $credential->delete(Registry::getRequest()->getRequestEscapedParameter('deleteoxid'));
    }
}