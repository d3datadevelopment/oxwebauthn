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

namespace D3\Webauthn\Application\Model\Webauthn;

use D3\Webauthn\Application\Model\Credential\publicKeyCredential;
use Webauthn\PublicKeyCredentialSource;

/** @deprecated  */

class d3PublicKeyCredentialSource  extends PublicKeyCredentialSource
{
    /**
     * @throws \Exception
     */
    public function saveCredential()
    {
        $credential = oxNew(publicKeyCredential::class);
        $credential->d3SetName(date('Y-m-d H:i:s'));
        $credential->d3SetCredentialId($this->getPublicKeyCredentialId());
        $credential->d3SetType($this->getType());
        $credential->d3SetTransports($this->getTransports());
        $credential->d3SetAttestationType($this->getAttestationType());
        $credential->d3SetTrustPath($this->getTrustPath());
        $credential->d3SetAaguid($this->getAaguid());
        $credential->d3SetPublicKey($this->getCredentialPublicKey());
        $credential->d3SetUserHandle($this->getUserHandle());
        $credential->d3SetCounter($this->getCounter());

        $credential->save();
    }

    public static function createFromd3PublicKeyCredential(publicKeyCredential $publicKeyCredential): self
    {
        return new self(
            $publicKeyCredential->d3GetCredentialId(),
            $publicKeyCredential->d3GetType(),
            $publicKeyCredential->d3GetTransports(),
            $publicKeyCredential->d3GetAttestationType(),
            $publicKeyCredential->d3GetTrustPath(),
            $publicKeyCredential->d3GetAaguid(),
            $publicKeyCredential->d3GetPublicKey(),
            $publicKeyCredential->d3GetUserHandle(),
            $publicKeyCredential->d3GetCounter()
        );
    }

    public static function createFromPublicKeyCredentialSource(publicKeyCredentialSource $publicKeyCredential): self
    {
        return new self(
            $publicKeyCredential->getPublicKeyCredentialId(),
            $publicKeyCredential->getType(),
            $publicKeyCredential->getTransports(),
            $publicKeyCredential->getAttestationType(),
            $publicKeyCredential->getTrustPath(),
            $publicKeyCredential->getAaguid(),
            $publicKeyCredential->getCredentialPublicKey(),
            $publicKeyCredential->getUserHandle(),
            $publicKeyCredential->getCounter()
        );
    }
}