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
 * @author    D3 Data Development - Daniel Seifert <support@shopmodule.com>
 * @link      http://www.oxidmodule.com
 */

namespace D3\Webauthn\Application\Model;

use Assert\InvalidArgumentException;
use D3\Webauthn\Application\Model\Credential\d3MetadataStatementRepository;
use D3\Webauthn\Application\Model\Exceptions\d3webauthnWrongAuthException;
use D3\Webauthn\Application\Model\Exceptions\d3webauthnMissingPublicKeyCredentialRequestOptions;
use D3\Webauthn\Application\Model\Webauthn\d3PublicKeyCredentialRpEntity;
use D3\Webauthn\Application\Model\Webauthn\d3PublicKeyCredentialSourceRepository;
use D3\Webauthn\Application\Model\Webauthn\d3PublicKeyCredentialUserEntity;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Database\Adapter\DatabaseInterface;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Registry;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\Server;

/**
 * @deprecated
 */

class d3webauthn extends BaseModel
{
    public $tableName = 'd3PublicKeyCredential';
    protected $_sCoreTable = 'd3PublicKeyCredential';
    public $userId;

    /**
     * d3webauthn constructor.
     */
    public function __construct()
    {
        $this->init($this->tableName);

        return parent::__construct();
    }

    /**
     * @param $userId
     * @throws DatabaseConnectionException
     */
    public function loadByUserId($userId)
    {
        $this->userId = $userId;
        $oDb = $this->d3GetDb();

        if ($userId && $oDb->getOne("SHOW TABLES LIKE '".$this->tableName."'")) {
            $query = "SELECT oxid FROM ".$this->getViewName().' WHERE UserHandle = '.$oDb->quote($userId).' LIMIT 1';
            $this->load($oDb->getOne($query));
        }
    }

    /**
     * @return DatabaseInterface
     * @throws DatabaseConnectionException
     */
    public function d3GetDb()
    {
        return DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
    }

    /**
     * @return User
     */
    public function getUser()
    {
        $userId = $this->userId ? $this->userId : $this->getFieldData('UserHandle');

        $user = $this->d3GetUser();
        $user->load($userId);
        return $user;
    }

    /**
     * @return User
     */
    public function d3GetUser()
    {
        return oxNew(User::class);
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return false == Registry::getConfig()->getConfigParam('blDisableWebauthnGlobally')
            &&  $this->UserUseWebauthn();
    }

    /**
     * @return bool
     */
    public function UserUseWebauthn()
    {
        return strlen($this->getId())
            && strlen($this->__get($this->_getFieldLongName('publickey'))->rawValue);
    }

    /**
     * @param $auth
     * @return false|string|null
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getCredentialRequestOptions($auth)
    {
        $this->loadByUserId($auth);

        $requestOptions = null;

        if ($auth
            && $this->isActive()
            && false == Registry::getSession()->getVariable(WebauthnConf::WEBAUTHN_SESSION_AUTH)
        ) {
            /** @var d3PublicKeyCredentialRpEntity $rpEntity */
            $rpEntity = oxNew(d3PublicKeyCredentialRpEntity::class, Registry::getConfig()->getActiveShop());

            $publicKeyCredentialSourceRepository = oxNew(d3PublicKeyCredentialSourceRepository::class);

            $server = new Server(
                $rpEntity,
                $publicKeyCredentialSourceRepository,
                new d3MetadataStatementRepository()
            );

            $user = $this->getUser();
            $userEntity = new d3PublicKeyCredentialUserEntity($user);

            $allowedCredentials = [];
            $credentialSourceRepository = oxNew(d3PublicKeyCredentialSourceRepository::class);
            /** @var d3PublicKeyCredentialSource $credentialSource */
            foreach ($credentialSourceRepository->findAllForUserEntity($userEntity) as $credentialSource) {
                $allowedCredentials[] = $credentialSource->getPublicKeyCredentialDescriptor();
            }

            // We generate the set of options.
            $publicKeyCredentialRequestOptions = $server->generatePublicKeyCredentialRequestOptions(
                PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_PREFERRED, // Default value
                $allowedCredentials
            );

            $requestOptions = json_encode($publicKeyCredentialRequestOptions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            Registry::getSession()->setVariable(WebauthnConf::WEBAUTHN_LOGIN_OBJECT, $publicKeyCredentialRequestOptions);

            // set auth as secured parameter;
            Registry::getSession()->setVariable("auth", $auth);
        }

        return $requestOptions;
    }

    /**
     * @param $webauth
     * @return bool
     * @throws d3webauthnWrongAuthException
     * @throws d3webauthnMissingPublicKeyCredentialRequestOptions
     */
    public function verify($webauth)
    {
        $blVerify = false;
        // Retrieve the Options passed to the device
        $publicKeyCredentialRequestOptions = Registry::getSession()->getVariable(WebauthnConf::WEBAUTHN_LOGIN_OBJECT);

        if (!$publicKeyCredentialRequestOptions) {
            $oException = oxNew(d3webauthnMissingPublicKeyCredentialRequestOptions::class);
            throw $oException;
        }

        $psr17Factory = new Psr17Factory();
        $creator = new ServerRequestCreator(
            $psr17Factory, // ServerRequestFactory
            $psr17Factory, // UriFactory
            $psr17Factory, // UploadedFileFactory
            $psr17Factory  // StreamFactory
        );

        $serverRequest = $creator->fromGlobals();

        $publicKeyCredentialSourceRepository = oxNew(d3PublicKeyCredentialSourceRepository::class);

        $server = new Server(
            new d3PublicKeyCredentialRpEntity(Registry::getConfig()->getActiveShop()),
            $publicKeyCredentialSourceRepository,
            new d3MetadataStatementRepository()
        );

        $user = $this->getUser();
        $userEntity = new d3PublicKeyCredentialUserEntity($user);

        try {
            $server->loadAndCheckAssertionResponse(
                $webauth,
                $publicKeyCredentialRequestOptions, // The options you stored during the previous step
                $userEntity,                        // The user entity
                $serverRequest                      // The PSR-7 request
            );
            $blVerify = true;

            Registry::getSession()->deleteVariable(WebauthnConf::WEBAUTHN_LOGIN_OBJECT);
            //If everything is fine, this means the user has correctly been authenticated using the
            // authenticator defined in $publicKeyCredentialSource
        } catch(InvalidArgumentException $exception) {
// ToDo
            $oException = oxNew(d3webauthnWrongAuthException::class);
            Registry::getUtilsView()->addErrorToDisplay($oException);
            // write to log
            //dumpvar(openssl_error_string());
            //dumpvar($exception);
        }

        if (false == $blVerify) {
            $oException = oxNew(d3webauthnWrongAuthException::class);
            throw $oException;
        }

        return $blVerify;
    }

    /**
     * @param $sUserId
     * @return PublicKeyCredentialCreationOptions
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function setAuthnRegister($sUserId)
    {
        $rpEntity = oxNew(d3PublicKeyCredentialRpEntity::class, Registry::getConfig()->getActiveShop());

        $publicKeyCredentialSourceRepository = oxNew(d3PublicKeyCredentialSourceRepository::class);

        $server = new Server(
            $rpEntity,
            $publicKeyCredentialSourceRepository,
            new d3MetadataStatementRepository()
        );
        /*
                    if (!($user = Registry::getSession()->getUser())) {
                        $e = oxNew(\Exception::class, 'no user loaded');
                        throw $e;
                    }
        */
        $user = oxNew(User::class);
        $user->load($sUserId);

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

        if (!Registry::getSession()->isSessionStarted()) {
            Registry::getSession()->start();
        }
        Registry::getSession()->setVariable('authnobject', $publicKeyCredentialCreationOptions);

        return $publicKeyCredentialCreationOptions;
    }

    /**
     * @param $request
     */
    public function registerNewKey($request)
    {
        /** @var PublicKeyCredentialCreationOptions $publicKeyCredentialCreationOptions */
        $publicKeyCredentialCreationOptions = Registry::getSession()->getVariable('authnobject');

        // Retrieve de data sent by the device
        $data = base64_decode($request, true);

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
// ToDo: is counter set and why will not save in case of login?
            $publicKeyCredentialSourceRepository->saveCredentialSource($publicKeyCredentialSource);

        } catch(\Exception $exception) {
            dumpvar($exception);
        }
        dumpvar('registered');
    }
}