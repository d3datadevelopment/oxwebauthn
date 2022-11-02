<?php

declare(strict_types=1);

namespace D3\Webauthn\Application\Model;

use Assert\AssertionFailedException;
use D3\Webauthn\Application\Model\Credential\PublicKeyCredential;
use D3\Webauthn\Application\Model\Credential\PublicKeyCredentialList;
use D3\Webauthn\Modules\Application\Model\d3_User_Webauthn;
use Doctrine\DBAL\Driver\Exception as DoctrineDriverException;
use Doctrine\DBAL\Exception as DoctrineException;
use Exception;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Registry;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\Server;

class Webauthn
{
    public const SESSION_CREATIONS_OPTIONS = 'd3WebAuthnCreationOptions';
    public const SESSION_ASSERTION_OPTIONS = 'd3WebAuthnAssertionOptions';

    public function isAvailable(): bool
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
     * @param User $user
     * @return false|string
     * @throws ContainerExceptionInterface
     * @throws DoctrineDriverException
     * @throws DoctrineException
     * @throws NotFoundExceptionInterface
     */
    public function getCreationOptions(User $user)
    {
        $userEntity = oxNew(UserEntity::class, $user);

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

    /**
     * @return false|string
     * @throws DoctrineDriverException
     * @throws DoctrineException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getRequestOptions(string $userId)
    {
        /** @var d3_User_Webauthn $user */
        $user = oxNew(User::class);
        $user->load($userId);
        $userEntity = oxNew(UserEntity::class, $user);

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
    public function getServer(): Server
    {
        $rpEntity = oxNew(RelyingPartyEntity::class);
        $server = oxNew(Server::class, $rpEntity, oxNew(PublicKeyCredentialList::class));
        $server->setLogger(Registry::getLogger());
        return $server;
    }

    /**
     * @param string      $credential
     * @param string|null $keyName
     *
     * @throws ContainerExceptionInterface
     * @throws DoctrineDriverException
     * @throws DoctrineException
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function saveAuthn(string $credential, string $keyName = null)
    {
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
    }

    /**
     * @param string $response
     *
     * @return bool
     */
    public function assertAuthn(string $response): bool
    {
        $psr17Factory = new Psr17Factory();
        $creator = new ServerRequestCreator(
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
            $psr17Factory
        );
        $serverRequest = $creator->fromGlobals();

        $user = oxNew(User::class);
        $user->load(Registry::getSession()->getVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER));
        $userEntity = oxNew(UserEntity::class, $user);

        $this->getServer()->loadAndCheckAssertionResponse(
            html_entity_decode($response),
            Registry::getSession()->getVariable(self::SESSION_ASSERTION_OPTIONS),
            $userEntity,
            $serverRequest
        );

        return true;
    }

    /**
     * @param $userId
     * @return bool
     * @throws ContainerExceptionInterface
     * @throws DoctrineDriverException
     * @throws DoctrineException
     * @throws NotFoundExceptionInterface
     */
    public function isActive($userId): bool
    {
        return !Registry::getConfig()->getConfigParam(WebauthnConf::GLOBAL_SWITCH)
            && !Registry::getSession()->getVariable(WebauthnConf::GLOBAL_SWITCH)
            && $this->UserUseWebauthn($userId);
    }

    /**
     * @param $userId
     * @return bool
     * @throws ContainerExceptionInterface
     * @throws DoctrineDriverException
     * @throws DoctrineException
     * @throws NotFoundExceptionInterface
     */
    public function UserUseWebauthn($userId): bool
    {
        $user = oxNew(User::class);
        $user->load($userId);
        $entity = oxNew(UserEntity::class, $user);

        $credentialList = oxNew(PublicKeyCredentialList::class);
        $list = $credentialList->findAllForUserEntity($entity);

        return is_array($list) && count($list);
    }
}