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

namespace D3\Webauthn\Application\Model;

use Assert\AssertionFailedException;
use D3\TestingTools\Production\IsMockable;
use D3\Webauthn\Application\Model\Credential\PublicKeyCredential;
use D3\Webauthn\Application\Model\Credential\PublicKeyCredentialList;
use D3\Webauthn\Application\Model\Exceptions\WebauthnException;
use D3\Webauthn\Application\Model\Exceptions\WebauthnGetException;
use D3\Webauthn\Modules\Application\Model\d3_User_Webauthn;
use Doctrine\DBAL\Driver\Exception as DoctrineDriverException;
use Doctrine\DBAL\Exception as DoctrineException;
use Exception;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\Eshop\Core\UtilsView;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\Server;

class Webauthn
{
    use IsMockable;

    public const SESSION_CREATIONS_OPTIONS = 'd3WebAuthnCreationOptions';
    public const SESSION_ASSERTION_OPTIONS = 'd3WebAuthnAssertionOptions';

    /**
     * @return bool
     */
    public function isAvailable(): bool
    {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ||       // is HTTPS
            !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ||
            !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on' ||
            in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']) ||      // is localhost
            (isset($_SERVER['REMOTE_ADDR']) && preg_match('/.*\.localhost$/mi', $_SERVER['REMOTE_ADDR']) )  // localhost is TLD
        ) {
            return true;
        }

        $e = oxNew(WebauthnException::class, 'D3_WEBAUTHN_ERR_UNSECURECONNECTION');
        $this->d3GetMockableLogger()->info($e->getDetailedErrorMessage());
        $this->d3GetMockableRegistryObject(UtilsView::class)->addErrorToDisplay($e);

        return false;
    }

    /**
     * @param User $user
     * @return string
     * @throws ContainerExceptionInterface
     * @throws DoctrineDriverException
     * @throws DoctrineException
     * @throws NotFoundExceptionInterface
     */
    public function getCreationOptions(User $user): string
    {
        $userEntity = $this->d3GetMockableOxNewObject(UserEntity::class, $user);

        $publicKeyCredentialCreationOptions = $this->getServer()->generatePublicKeyCredentialCreationOptions(
            $userEntity,
            PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            $this->getExistingCredentials($userEntity)
        );

        $this->d3GetMockableRegistryObject(Session::class)
            ->setVariable(self::SESSION_CREATIONS_OPTIONS, $publicKeyCredentialCreationOptions);

        $json = $this->jsonEncode($publicKeyCredentialCreationOptions);

        if ($json === false) {
            throw oxNew(Exception::class, "can't encode creation options");
        }

        return $json;
    }

    /**
     * @param UserEntity $userEntity
     * @return PublicKeyCredentialDescriptor[]
     * @throws DoctrineDriverException
     * @throws DoctrineException
     */
    public function getExistingCredentials(UserEntity $userEntity): array
    {
        // Get the list of authenticators associated to the user
        /** @var PublicKeyCredentialList $credentialSourceRepository */
        $credentialList = $this->d3GetMockableOxNewObject(PublicKeyCredentialList::class);
        $credentialSources = $credentialList->findAllForUserEntity($userEntity);

        // Convert the Credential Sources into Public Key Credential Descriptors
        return array_map(function (PublicKeyCredentialSource $credential) {
            return $credential->getPublicKeyCredentialDescriptor();
        }, $credentialSources);
    }

    /**
     * @param PublicKeyCredentialCreationOptions|PublicKeyCredentialRequestOptions $creationOptions
     * @return false|string
     */
    protected function jsonEncode($creationOptions)
    {
        return json_encode($creationOptions,JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param string $userId
     * @return string
     * @throws DoctrineDriverException
     * @throws DoctrineException
     */
    public function getRequestOptions(string $userId): string
    {
        /** @var d3_User_Webauthn $user */
        $user = $this->d3GetMockableOxNewObject(User::class);
        $user->load($userId);
        $userEntity = $this->d3GetMockableOxNewObject(UserEntity::class, $user);

        // We generate the set of options.
        $publicKeyCredentialRequestOptions = $this->getServer()->generatePublicKeyCredentialRequestOptions(
            PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_PREFERRED, // Default value
            $this->getExistingCredentials($userEntity)
        );

        $this->d3GetMockableRegistryObject(Session::class)
            ->setVariable(self::SESSION_ASSERTION_OPTIONS, $publicKeyCredentialRequestOptions);

        $json = $this->jsonEncode($publicKeyCredentialRequestOptions);

        if ($json === false) {
            throw oxNew(Exception::class, "can't encode request options");
        }

        return $json;
    }

    /**
     * @return Server
     */
    protected function getServer(): Server
    {
        /** @var RelyingPartyEntity $rpEntity */
        $rpEntity = $this->d3GetMockableOxNewObject(RelyingPartyEntity::class);
        /** @var Server $server */
        $server = $this->d3GetMockableOxNewObject(
            Server::class,
            $rpEntity,
            $this->d3GetMockableOxNewObject(PublicKeyCredentialList::class)
        );
        $server->setLogger($this->d3GetMockableLogger());
        return $server;
    }

    /**
     * @param string $credential
     * @param string|null $keyName
     *
     * @throws AssertionFailedException
     * @throws DoctrineDriverException
     * @throws DoctrineException
     * @throws Throwable
     */
    public function saveAuthn(string $credential, string $keyName = null): void
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
            $this->d3GetMockableRegistryObject(Session::class)->getVariable(self::SESSION_CREATIONS_OPTIONS),
            $serverRequest
        );

        $pkCredential = $this->d3GetMockableOxNewObject(PublicKeyCredential::class);
        $pkCredential->saveCredentialSource($publicKeyCredentialSource, $keyName);
    }

    /**
     * @param string $response
     *
     * @return bool
     * @throws WebauthnException
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

        $userEntity = $this->getUserEntityFrom($this->getSavedUserIdFromSession());

        try {
            $this->getServer()->loadAndCheckAssertionResponse(
                html_entity_decode( $response ),
                $this->d3GetMockableRegistryObject(Session::class)
                    ->getVariable( self::SESSION_ASSERTION_OPTIONS ),
                $userEntity,
                $serverRequest
            );
        } catch (AssertionFailedException $e) {
            /** @var WebauthnGetException $exc */
            $exc = oxNew(WebauthnGetException::class, $e->getMessage(), 0, $e);
            throw $exc;
        }

        return true;
    }

    /**
     * @param $userId
     * @return UserEntity
     */
    protected function getUserEntityFrom($userId): UserEntity
    {
        /** @var User $user */
        $user = $this->d3GetMockableOxNewObject(User::class);
        $user->load($userId);
        /** @var UserEntity $userEntity */
        return $this->d3GetMockableOxNewObject(UserEntity::class, $user);
    }

    /**
     * @return string|null
     */
    protected function getSavedUserIdFromSession(): ?string
    {
        $session = $this->d3GetMockableRegistryObject(Session::class);

        return $this->isAdmin() ?
            $session->getVariable(WebauthnConf::WEBAUTHN_ADMIN_SESSION_CURRENTUSER) :
            $session->getVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER);
    }

    /**
     * @return bool
     */
    public function isAdmin(): bool
    {
        return isAdmin();
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
        return !$this->d3GetMockableRegistryObject(Config::class)
                ->getConfigParam(WebauthnConf::GLOBAL_SWITCH)
            && !$this->d3GetMockableRegistryObject(Session::class)
                ->getVariable(WebauthnConf::GLOBAL_SWITCH)
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
        $entity = $this->getUserEntityFrom($userId);

        /** @var PublicKeyCredentialList $credentialList */
        $credentialList = $this->d3GetMockableOxNewObject(PublicKeyCredentialList::class);
        $list = $credentialList->findAllForUserEntity($entity);

        return is_array($list) && count($list);
    }
}