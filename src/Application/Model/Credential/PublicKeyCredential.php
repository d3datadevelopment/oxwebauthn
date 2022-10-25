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

use Doctrine\DBAL\Query\QueryBuilder;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use Webauthn\PublicKeyCredentialSource;

class PublicKeyCredential extends BaseModel
{
    protected $_sCoreTable = 'd3wa_usercredentials';

    public function __construct()
    {
        $this->init($this->getCoreTableName());

        parent::__construct();
    }

    public function setName($name)
    {
        $this->assign(['name' => $name]);
    }

    public function getName()
    {
        return $this->getFieldData('name');
    }

    public function setCredentialId($credentialId)
    {
        $this->assign([
            'credentialid' => bin2hex($credentialId)
        ]);
    }

    public function getCredentialId()
    {
        return hex2bin($this->__get($this->_getFieldLongName('credentialid'))->rawValue);
    }

    public function setUserId($userId)
    {
        $this->assign([
            'oxuserid' => $userId
        ]);
    }

    public function getUserId()
    {
        return $this->__get($this->_getFieldLongName('oxuserid'))->rawValue;
    }

    public function setCredential($credential)
    {
        $this->assign([
            'credential' => bin2hex(serialize($credential))
        ]);
    }

    public function getCredential()
    {
        return unserialize(hex2bin($this->__get($this->_getFieldLongName('credential'))->rawValue));
    }
/*
    public function setPublicKey($publicKey)
    {
        $this->assign(['PublicKey' => $publicKey]);
    }

    public function getPublicKey()
    {
        return $this->__get($this->_getFieldLongName('PublicKey'))->rawValue;
    }

    /**
     * @param PublicKeyCredentialSource $publicKeyCredentialSource
     * @return void
     * @throws \Exception
     */
    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource, string $keyName = null): void
    {
        // will save on every successfully assertion, set id to prevent duplicated database entries
        $id = $this->getIdByCredentialId($publicKeyCredentialSource->getPublicKeyCredentialId());
        $this->setId($id);

        $this->setShopId(Registry::getConfig()->getShopId());
        $this->setUserId($publicKeyCredentialSource->getUserHandle());
        $this->setCredentialId($publicKeyCredentialSource->getPublicKeyCredentialId());
        $this->setCredential($publicKeyCredentialSource);
        $this->setName($keyName ?: $this->getName());

// ToDo: required??
        $this->assign([
            'pubkey_hex' => bin2hex($publicKeyCredentialSource->getCredentialPublicKey()),
        ]);
        $this->save();
    }

    public function getIdByCredentialId(string $publicKeyCredentialId): ?string
    {
        /** @var QueryBuilder $qb */
        $qb = ContainerFactory::getInstance()->getContainer()->get(QueryBuilderFactoryInterface::class)->create();
        $qb->select('oxid')
            ->from($this->getViewName())
            ->where(
                $qb->expr()->and(
                    $qb->expr()->eq(
                        'credentialid',
                        $qb->createNamedParameter(bin2hex($publicKeyCredentialId))
                    ),
                    $qb->expr()->eq(
                        'oxshopid',
                        $qb->createNamedParameter(Registry::getConfig()->getShopId())
                    )
                )
            );
        $oxid = $qb->execute()->fetchOne();

        return strlen($oxid) ? $oxid : null;
    }
}