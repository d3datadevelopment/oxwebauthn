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

namespace D3\Webauthn\Application\Model\Credential;

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Registry;

class d3PublicKeyCredential extends BaseModel
{
    protected $_sCoreTable = 'd3PublicKeyCredential';

    public function __construct()
    {
        $this->init($this->getCoreTableName());

        parent::__construct();
    }

    public function d3SetName($name)
    {
        $this->assign(['name' => $name]);
    }

    public function d3GetName()
    {
        return $this->getFieldData('name');
    }

    public function d3SetCredentialId($credentialId)
    {
        $this->assign(['credentialid' => $credentialId]);
    }

    public function d3GetCredentialId()
    {
        return $this->__get($this->_getFieldLongName('credentialid'))->rawValue;
    }

    public function d3SetType($type)
    {
        $this->assign(['Type' => $type]);
    }

    public function d3GetType()
    {
        return $this->getFieldData('Type');
    }

    public function d3SetTransports($transports)
    {
        $this->assign(['Transports' => base64_encode(serialize($transports))]);
    }

    public function d3GetTransports()
    {
        return unserialize(base64_decode($this->getFieldData('Transports')));
    }

    public function d3SetAttestationType($attestationType)
    {
        $this->assign(['AttestationType' => $attestationType]);
    }

    public function d3GetAttestationType()
    {
        return $this->getFieldData('AttestationType');
    }

    public function d3SetTrustPath($trustPath)
    {
        $this->assign(['TrustPath' => base64_encode(serialize($trustPath))]);
    }

    public function d3GetTrustPath()
    {
        return unserialize(base64_decode($this->getFieldData('TrustPath')));
    }

    public function d3SetAaguid($aaguid)
    {
        $this->assign(['Aaguid' => base64_encode(serialize($aaguid))]);
    }

    public function d3GetAaguid()
    {
        return unserialize(base64_decode($this->getFieldData('Aaguid')));
    }

    public function d3SetPublicKey($publicKey)
    {
        $this->assign(['PublicKey' => $publicKey]);
    }

    public function d3GetPublicKey()
    {
        return $this->__get($this->_getFieldLongName('PublicKey'))->rawValue;
    }

    public function d3SetUserHandle($userHandle)
    {
        $this->assign(['UserHandle' => $userHandle]);
    }

    public function d3GetUserHandle()
    {
        return $this->getFieldData('UserHandle');
    }

    public function d3SetCounter($count)
    {
        $this->assign(['Counter' => $count]);
    }

    public function d3GetCounter()
    {
        return $this->getFieldData('Counter');
    }

    /**
     * @param string $publicKeyCredentialId
     * @return |null
     * @throws DatabaseConnectionException
     */
    public function loadByCredentialId(string $publicKeyCredentialId)
    {
        if (Registry::getRequest()->getRequestEscapedParameter('fnc') == 'checkregister') {
            return null;
        }

        $oDb = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
        $q = "SELECT oxid FROM ".$this->getViewName()." WHERE CredentialId = ".$oDb->quote($publicKeyCredentialId);
        $id = $oDb->getOne($q);
        $this->load($id);
    }

}