<?php

/**
 * This Software is the property of Data Development and is protected
 * by copyright law - it is NOT Freeware.
 *
 * Any unauthorized use of this software without a valid license
 * is a violation of the license agreement and will be prosecuted by
 * civil and criminal law.
 *
 * http://www.shopmodule.com
 *
 * @copyright (C) D3 Data Development (Inh. Thomas Dartsch)
 * @author    D3 Data Development - Daniel Seifert <support@shopmodule.com>
 * @link      http://www.oxidmodule.com
 */

declare(strict_types=1);

namespace D3\Webauthn\Application\Model\Webauthn;

use D3\Webauthn\Application\Model\Credential\d3PublicKeyCredential;
use D3\Webauthn\Application\Model\Credential\d3PublicKeyCredentialList;
use Exception;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Registry;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\PublicKeyCredentialUserEntity;

class d3PublicKeyCredentialSourceRepository implements PublicKeyCredentialSourceRepository
{
    /**
     * @param string $publicKeyCredentialId
     * @return PublicKeyCredentialSource|null
     * @throws DatabaseConnectionException
     */
    public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource
    {
        if (Registry::getRequest()->getRequestEscapedParameter('fnc') == 'checkregister') {
            return null;
        }

        $credential = oxNew(d3PublicKeyCredential::class);
        $credential->loadByCredentialId($publicKeyCredentialId);

        return $credential->getId() ?
            d3PublicKeyCredentialSource::createFromd3PublicKeyCredential($credential) :
            null;
    }

    /**
     * @param PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity
     * @return array
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
        $sourceList = [];

        $credentialList = oxNew(d3PublicKeyCredentialList::class);
        $credentialList->loadAllForUserEntity($publicKeyCredentialUserEntity);

        /** @var d3PublicKeyCredential $credential */
        foreach ($credentialList->getArray() as $credential) {
            $sourceList[$credential->getId()] = d3PublicKeyCredentialSource::createFromd3PublicKeyCredential($credential);
        };

        return $sourceList;
    }

    /**
     * @param PublicKeyCredentialSource $publicKeyCredentialSource
     * @throws Exception
     */
    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void
    {
        $publicKeyCredentialSource = d3PublicKeyCredentialSource::createFromPublicKeyCredentialSource($publicKeyCredentialSource);

        if ($this->findOneByCredentialId($publicKeyCredentialSource->getPublicKeyCredentialId())) {
            // increase counter
        } else {
            $publicKeyCredentialSource->saveCredential();
        }
    }
}
