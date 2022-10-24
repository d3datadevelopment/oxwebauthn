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
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Model\ListModel;
use Webauthn\PublicKeyCredentialUserEntity;

class d3PublicKeyCredentialList extends ListModel
{
    protected $_sObjectsInListName = d3PublicKeyCredential::class;

    public function __construct()
    {
        parent::__construct(d3PublicKeyCredential::class);
    }

    /**
     * @param PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function loadAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity)
    {
        $q = "SELECT oxid FROM ".$this->getBaseObject()->getViewName()." WHERE UserHandle = ".DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC)->quote($publicKeyCredentialUserEntity->getId());
        $idList = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC)->getAll($q);

        if ($idList && is_iterable($idList)) {
            foreach ($idList as $id) {
                $credential = oxNew($this->_sObjectsInListName);
                $credential->load($id['oxid']);
                $this->offsetSet($credential->getId(), $credential);
            }
        }
    }
}