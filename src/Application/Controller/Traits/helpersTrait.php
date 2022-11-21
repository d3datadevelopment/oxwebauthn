<?php

namespace D3\Webauthn\Application\Controller\Traits;

use D3\Webauthn\Application\Model\Credential\PublicKeyCredential;
use D3\Webauthn\Application\Model\Credential\PublicKeyCredentialList;
use D3\Webauthn\Application\Model\Webauthn;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Utils;
use OxidEsales\Eshop\Core\UtilsView;
use Psr\Log\LoggerInterface;

trait helpersTrait
{
    /**
     * @return User
     */
    public function getUserObject(): User
    {
        return oxNew(User::class);
    }

    /**
     * @return Webauthn
     */
    public function getWebauthnObject(): Webauthn
    {
        return oxNew(Webauthn::class);
    }

    /**
     * @return LoggerInterface
     */
    public function getLoggerObject(): LoggerInterface
    {
        return Registry::getLogger();
    }

    /**
     * @return Utils
     */
    public function getUtilsObject(): Utils
    {
        return Registry::getUtils();
    }

    /**
     * @return UtilsView
     */
    public function getUtilsViewObject(): UtilsView
    {
        return Registry::getUtilsView();
    }

    /**
     * @return PublicKeyCredentialList
     */
    public function getPublicKeyCredentialListObject(): PublicKeyCredentialList
    {
        return oxNew(PublicKeyCredentialList::class);
    }

    /**
     * @return PublicKeyCredential
     */
    public function getPublicKeyCredentialObject(): PublicKeyCredential
    {
        return oxNew(PublicKeyCredential::class);
    }
}