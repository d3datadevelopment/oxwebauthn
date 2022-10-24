<?php

/**
 * This Software is the property of Data Development and is protected
 * by copyright law - it is NOT Freeware.
 * Any unauthorized use of this software without a valid license
 * is a violation of the license agreement and will be prosecuted by
 * civil and criminal law.
 * http://www.shopmodule.com
 *
 * @copyright (C) D3 Data Development (Inh. Thomas Dartsch)
 * @author        D3 Data Development - Daniel Seifert <support@shopmodule.com>
 * @link          http://www.oxidmodule.com
 */

namespace D3\Webauthn\Modules\Application\Controller;

use D3\Webauthn\Application\Model\Credential\d3MetadataStatementRepository;
use D3\Webauthn\Application\Model\Webauthn\d3PublicKeyCredentialRpEntity;
use D3\Webauthn\Application\Model\Webauthn\d3PublicKeyCredentialSourceRepository;
use D3\Webauthn\Application\Model\Webauthn\d3PublicKeyCredentialUserEntity;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Registry;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\Server;

class d3_StartController_Webauthn extends d3_StartController_Webauthn_parent
{
    /**
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function ___render()
    {
        if (!Registry::getRequest()->getRequestEscapedParameter('authn')) {
            /*** register ***/
            $rpEntity = oxNew(d3PublicKeyCredentialRpEntity::class, Registry::getConfig()->getActiveShop());

            $publicKeyCredentialSourceRepository = oxNew(d3PublicKeyCredentialSourceRepository::class);

            $server = new Server(
                $rpEntity,
                $publicKeyCredentialSourceRepository,
                new d3MetadataStatementRepository()
            );

            $user = oxNew(User::class);
            //$user->load('oxdefaultadmin');
            $user->load('36944b76d6e583fe2.12734046');

            $userEntity = new d3PublicKeyCredentialUserEntity($user);

            $excludedCredentials = [];
            $credentialSourceRepository = oxNew(d3PublicKeyCredentialSourceRepository::class);
            foreach ($credentialSourceRepository->findAllForUserEntity($userEntity) as $credentialSource) {
                $excludedCredentials[] = $credentialSource->getPublicKeyCredentialDescriptor();
            }

            $publicKeyCredentialCreationOptions = $server->generatePublicKeyCredentialCreationOptions(
                $userEntity,
                PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
                $excludedCredentials
            );

            $this->addTplParam(
                'webauthn_publickey_register',
                json_encode($publicKeyCredentialCreationOptions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            );

            if (!Registry::getSession()->isSessionStarted()) {
                Registry::getSession()->start();
            }
            Registry::getSession()->setVariable('authnobject', $publicKeyCredentialCreationOptions);

    /*** login ***/

            $allowedCredentials = [];
            $credentialSourceRepository = oxNew(d3PublicKeyCredentialSourceRepository::class);
            foreach ($credentialSourceRepository->findAllForUserEntity($userEntity) as $credentialSource) {
                $allowedCredentials[] = $credentialSource->getPublicKeyCredentialDescriptor();
            }

            // We generate the set of options.
            $publicKeyCredentialRequestOptions = $server->generatePublicKeyCredentialRequestOptions(
                PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_PREFERRED, // Default value
                $allowedCredentials
            );

            $this->addTplParam(
                'webauthn_publickey_login',
                json_encode($publicKeyCredentialRequestOptions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            );

            Registry::getSession()->setVariable('authnloginobject', $publicKeyCredentialRequestOptions);
        }

        $return = parent::render();

        return $return;
    }

    public function ____checkregister()
    {
        // Retrieve the PublicKeyCredentialCreationOptions object created earlier
        /** @var PublicKeyCredentialCreationOptions $publicKeyCredentialCreationOptions */
        $publicKeyCredentialCreationOptions = Registry::getSession()->getVariable('authnobject');

        // Retrieve de data sent by the device
        $data = base64_decode(Registry::getRequest()->getRequestParameter('authn'), true);

        $psr17Factory = new Psr17Factory();
        $creator = new ServerRequestCreator(
            $psr17Factory, // ServerRequestFactory
            $psr17Factory, // UriFactory
            $psr17Factory, // UploadedFileFactory
            $psr17Factory  // StreamFactory
        );

        $serverRequest = $creator->fromGlobals();

        /*** register ***/
        $rpEntity = oxNew(d3PublicKeyCredentialRpEntity::class, Registry::getConfig()->getActiveShop());

        $publicKeyCredentialSourceRepository = oxNew(d3PublicKeyCredentialSourceRepository::class);

        $server = new Server(
            $rpEntity,
            $publicKeyCredentialSourceRepository,
            new d3MetadataStatementRepository()
        );

        try {
            $publicKeyCredentialSource = $server->loadAndCheckAttestationResponse(
                $data,
                $publicKeyCredentialCreationOptions, // The options you stored during the previous step
                $serverRequest                       // The PSR-7 request
            );

            // The user entity and the public key credential source can now be stored using their repository
            // The Public Key Credential Source repository must implement Webauthn\PublicKeyCredentialSourceRepository
            $publicKeyCredentialSourceRepository->saveCredentialSource($publicKeyCredentialSource);

        } catch(\Exception $exception) {
            dumpvar($exception);
        }
        dumpvar('registered');
    }

    public function _____checklogin()
    {
        // Retrieve the Options passed to the device
        $publicKeyCredentialRequestOptions = Registry::getSession()->getVariable('authnloginobject');

        if (!$publicKeyCredentialRequestOptions) {
            return;
        }

        $psr17Factory = new Psr17Factory();
        $creator = new ServerRequestCreator(
            $psr17Factory, // ServerRequestFactory
            $psr17Factory, // UriFactory
            $psr17Factory, // UploadedFileFactory
            $psr17Factory  // StreamFactory
        );

        $serverRequest = $creator->fromGlobals();

        // Retrieve de data sent by the device
        $data = base64_decode(Registry::getRequest()->getRequestParameter('authn'));

        $publicKeyCredentialSourceRepository = oxNew(d3PublicKeyCredentialSourceRepository::class);

        $server = new Server(
            new d3PublicKeyCredentialRpEntity(Registry::getConfig()->getActiveShop()),
            $publicKeyCredentialSourceRepository,
            new d3MetadataStatementRepository()
        );

        $user = oxNew(User::class);
        //$user->load('oxdefaultadmin');
        $user->load('36944b76d6e583fe2.12734046');

        $userEntity = new d3PublicKeyCredentialUserEntity($user);

        try {
            $publicKeyCredentialSource = $server->loadAndCheckAssertionResponse(
                $data,
                $publicKeyCredentialRequestOptions, // The options you stored during the previous step
                $userEntity,                        // The user entity
                $serverRequest                      // The PSR-7 request
            );

            //If everything is fine, this means the user has correctly been authenticated using the
            // authenticator defined in $publicKeyCredentialSource
        } catch(\Throwable $exception) {
            dumpvar(openssl_error_string());
            dumpvar($exception);
        }

        dumpvar('logged in');

    }
}