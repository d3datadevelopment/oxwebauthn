<?php

namespace D3\Webauthn\Application\Controller\Traits;

use D3\Webauthn\Application\Model\Credential\PublicKeyCredential;
use D3\Webauthn\Application\Model\Credential\PublicKeyCredentialList;
use D3\Webauthn\Application\Model\Webauthn;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Routing\ControllerClassNameResolver;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\Eshop\Core\Utils;
use OxidEsales\Eshop\Core\UtilsView;
use Psr\Log\LoggerInterface;

trait helpersTrait
{
    /**
     * @return User
     */
    public function d3GetUserObject(): User
    {
        return oxNew(User::class);
    }

    /**
     * @return Webauthn
     */
    public function d3GetWebauthnObject(): Webauthn
    {
        return oxNew(Webauthn::class);
    }

    /**
     * @return LoggerInterface
     */
    public function d3GetLoggerObject(): LoggerInterface
    {
        return Registry::getLogger();
    }

    /**
     * @return Utils
     */
    public function d3GetUtilsObject(): Utils
    {
        return Registry::getUtils();
    }

    /**
     * @return UtilsView
     */
    public function d3GetUtilsViewObject(): UtilsView
    {
        return Registry::getUtilsView();
    }

    /**
     * @return PublicKeyCredentialList
     */
    public function d3GetPublicKeyCredentialListObject(): PublicKeyCredentialList
    {
        return oxNew(PublicKeyCredentialList::class);
    }

    /**
     * @return PublicKeyCredential
     */
    public function d3GetPublicKeyCredentialObject(): PublicKeyCredential
    {
        return oxNew(PublicKeyCredential::class);
    }

    /**
     * @return Session
     */
    public function d3GetSession(): Session
    {
        return Registry::getSession();
    }

    /**
     * @return ControllerClassNameResolver
     */
    public function d3GetControllerClassNameResolver(): ControllerClassNameResolver
    {
        return Registry::getControllerClassNameResolver();
    }
}