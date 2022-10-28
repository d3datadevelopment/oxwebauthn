<?php

declare(strict_types=1);

namespace D3\Webauthn\Application\Model;

use D3\Webauthn\Application\Model\Credential\PublicKeyCredential;
use D3\Webauthn\Application\Model\Credential\PublicKeyCredentialList;
use D3\Webauthn\Modules\Application\Model\d3_User_Webauthn;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Registry;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\Server;

class Webauthn
{
    public const SESSION_CREATIONS_OPTIONS = 'd3WebAuthnCreationOptions';
    public const SESSION_ASSERTION_OPTIONS = 'd3WebAuthnAssertionOptions';

    public function isAvailable()
    {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ||       // is HTTPS
            !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ||
            !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on' ||
            in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])         // is localhost
        ) {
            return true;
        }

        Registry::getUtilsView()->addErrorToDisplay(
            Registry::getLang()->translateString('D3_WEBAUTHN_ERR_UNSECURECONNECTION', null, true)
        );

        return false;
    }

    /**
     * @return false|string
     */
    public function getCreationOptions(User $user)
    {
        /** @var d3_User_Webauthn $user */
        $userEntity = $user->d3GetWebauthnUserEntity();

        /** @var PublicKeyCredentialList $credentialSourceRepository */
        $credentialSourceRepository = oxNew(PublicKeyCredentialList::class);
        $credentialSources = $credentialSourceRepository->findAllForUserEntity($userEntity);
        $excludeCredentials = array_map(function (PublicKeyCredentialSource $credential) {
            return $credential->getPublicKeyCredentialDescriptor();
        }, $credentialSources);

        $server = $this->getServer();
        $publicKeyCredentialCreationOptions = $server->generatePublicKeyCredentialCreationOptions(
            $userEntity,
            PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            $excludeCredentials
        );

        Registry::getSession()->setVariable(self::SESSION_CREATIONS_OPTIONS, $publicKeyCredentialCreationOptions);

        return json_encode($publicKeyCredentialCreationOptions,JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function getRequestOptions()
    {
        /** @var d3_User_Webauthn $user */
        $user = oxNew(User::class);
        $user->load('oxdefaultadmin');
        $userEntity = $user->d3GetWebauthnUserEntity();

        // Get the list of authenticators associated to the user
        $credentialList = oxNew(PublicKeyCredentialList::class);
        $credentialSources = $credentialList->findAllForUserEntity($userEntity);

        // Convert the Credential Sources into Public Key Credential Descriptors
        $allowedCredentials = array_map(function (PublicKeyCredentialSource $credential) {
            return $credential->getPublicKeyCredentialDescriptor();
        }, $credentialSources);

        $server = $this->getServer();

        // We generate the set of options.
        $publicKeyCredentialRequestOptions = $server->generatePublicKeyCredentialRequestOptions(
            PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_PREFERRED, // Default value
            $allowedCredentials
        );

        Registry::getSession()->setVariable(self::SESSION_ASSERTION_OPTIONS, $publicKeyCredentialRequestOptions);

        return json_encode($publicKeyCredentialRequestOptions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return Server
     */
    public function getServer()
    {
        $rpEntity = new PublicKeyCredentialRpEntity(
            Registry::getConfig()->getActiveShop()->getFieldData('oxname'),
            preg_replace('/(^www\.)(.*)/mi', '$2', $_SERVER['HTTP_HOST'])
        );

        return new Server($rpEntity, oxNew(PublicKeyCredentialList::class));
    }

    public function saveAuthn(string $credential, string $keyName = null)
    {
        try {
            $psr17Factory = new Psr17Factory();
            $creator = new ServerRequestCreator(
                $psr17Factory,
                $psr17Factory,
                $psr17Factory,
                $psr17Factory
            );
            $serverRequest = $creator->fromGlobals();

            $publicKeyCredentialSource = $this->getServer()->loadAndCheckAttestationResponse(
                html_entity_decode($credential),
                Registry::getSession()->getVariable(self::SESSION_CREATIONS_OPTIONS),
                $serverRequest
            );

            $pkCredential = oxNew(PublicKeyCredential::class);
            $pkCredential->saveCredentialSource($publicKeyCredentialSource, $keyName);
        } catch (\Exception $e) {
            dumpvar($e->getMessage());
            dumpvar($e);

            die();
        }
    }

    public function assertAuthn(string $response)
    {
        $psr17Factory = new Psr17Factory();
        $creator = new ServerRequestCreator(
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
            $psr17Factory
        );
        $serverRequest = $creator->fromGlobals();

        /** @var d3_User_Webauthn $user */
        $user = oxNew(User::class);
        $user->load('oxdefaultadmin');
        $userEntity = $user->d3GetWebauthnUserEntity();

        $this->getServer()->loadAndCheckAssertionResponse(
            html_entity_decode($response),
            Registry::getSession()->getVariable(self::SESSION_ASSERTION_OPTIONS),
            $userEntity,
            $serverRequest
        );
    }

    /**
     * @param $userId
     * @return bool
     */
    public function isActive($userId): bool
    {
        return false == Registry::getConfig()->getConfigParam('blDisableWebauthnGlobally')
            && $this->UserUseWebauthn($userId);
    }

    /**
     * @param $userId
     * @return bool
     */
    public function UserUseWebauthn($userId): bool
    {
        /** @var d3_User_Webauthn $user */
        $user = oxNew(User::class);
        $user->load($userId);
        $entity = $user->d3GetWebauthnUserEntity();
        $credentionList = oxNew(PublicKeyCredentialList::class);
        $list = $credentionList->findAllForUserEntity($entity);

        return is_array($list) && count($list);
    }
}