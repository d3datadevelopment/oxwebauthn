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

use DateTime;
use Doctrine\DBAL\Driver\Exception as DoctrineDriverException;
use Doctrine\DBAL\Exception as DoctrineException;
use Doctrine\DBAL\Query\QueryBuilder;
use Exception;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
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
            'credentialid' => base64_encode($credentialId)
        ]);
    }

    public function getCredentialId()
    {
        return base64_decode($this->__get($this->_getFieldLongName('credentialid'))->rawValue);
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
            'credential' => base64_encode(serialize($credential))
        ]);
    }

    public function getCredential()
    {
        return unserialize(base64_decode($this->__get($this->_getFieldLongName('credential'))->rawValue));
    }

    /**
     * @param PublicKeyCredentialSource $publicKeyCredentialSource
     * @param string|null $keyName
     * @return void
     * @throws Exception
     */
    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource, string $keyName = null): void
    {
        if ((oxNew(PublicKeyCredentialList::class))
            ->findOneByCredentialId($publicKeyCredentialSource->getPublicKeyCredentialId())
        ) {
            return;
        }

        // will save on every successfully assertion, set id to prevent duplicated database entries
        $id = $this->getIdByCredentialId($publicKeyCredentialSource->getPublicKeyCredentialId());

        if ($this->exists($id)) {
            $this->load($id);
        }

        $this->setShopId(Registry::getConfig()->getShopId());
        $this->setUserId($publicKeyCredentialSource->getUserHandle());
        $this->setCredentialId($publicKeyCredentialSource->getPublicKeyCredentialId());
        $this->setCredential($publicKeyCredentialSource);
        $this->setName($keyName ?: $this->getName() ?: (new DateTime())->format('Y-m-d H:i:s'));
        $this->save();
    }

    /**
     * @param string $publicKeyCredentialId
     * @return string|null
     * @throws DoctrineDriverException
     * @throws DoctrineException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
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
                        $qb->createNamedParameter(base64_encode($publicKeyCredentialId))
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