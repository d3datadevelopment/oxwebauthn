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

use Doctrine\DBAL\Driver\Exception as DoctrineDriverException;
use Doctrine\DBAL\Exception as DoctrineException;
use Doctrine\DBAL\Query\QueryBuilder;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Model\ListModel;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\PublicKeyCredentialUserEntity;

class PublicKeyCredentialList extends ListModel implements PublicKeyCredentialSourceRepository
{
    protected $_sObjectsInListName = PublicKeyCredential::class;

    public function __construct()
    {
        parent::__construct(PublicKeyCredential::class);
    }

    /**
     * @param string $publicKeyCredentialId
     * @return PublicKeyCredentialSource|null
     * @throws DoctrineDriverException
     * @throws DoctrineException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource
    {
        /** @var QueryBuilder $qb */
        $qb = ContainerFactory::getInstance()->getContainer()->get(QueryBuilderFactoryInterface::class)->create();
        $qb->select('credential')
            ->from($this->getBaseObject()->getViewName())
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
        $credential = $qb->execute()->fetchOne();

        if (!strlen($credential)) {
            return null;
        }

        $credential = unserialize(hex2bin($credential));

        return $credential instanceof PublicKeyCredentialSource ? $credential : null;
    }

    /**
     * @param PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity
     * @return array|PublicKeyCredentialSource[]
     * @throws ContainerExceptionInterface
     * @throws DoctrineDriverException
     * @throws DoctrineException
     * @throws NotFoundExceptionInterface
     */
    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
        /** @var QueryBuilder $qb */
        $qb = ContainerFactory::getInstance()->getContainer()->get(QueryBuilderFactoryInterface::class)->create();
        $qb->select('credential')
            ->from($this->getBaseObject()->getViewName())
            ->where(
                $qb->expr()->and(
                    $qb->expr()->eq(
                        'oxuserid',
                        $qb->createNamedParameter($publicKeyCredentialUserEntity->getId())
                    ),
                    $qb->expr()->eq(
                        'oxshopid',
                        $qb->createNamedParameter(Registry::getConfig()->getShopId())
                    )
                )
            );

        // generate decoded credentials list
        return array_map(function (array $fields) {
            return unserialize(hex2bin($fields['credential']));
        }, $qb->execute()->fetchAllAssociative());
    }

    /**
     * @param User $user
     * @return $this
     * @throws ContainerExceptionInterface
     * @throws DoctrineDriverException
     * @throws DoctrineException
     * @throws NotFoundExceptionInterface
     */
    public function getAllFromUser(User $user): PublicKeyCredentialList
    {
        if (!$user->isLoaded()) {
            return $this;
        }

        /** @var QueryBuilder $qb */
        $qb = ContainerFactory::getInstance()->getContainer()->get(QueryBuilderFactoryInterface::class)->create();
        $qb->select('oxid')
            ->from($this->getBaseObject()->getViewName())
            ->where(
                $qb->expr()->and(
                    $qb->expr()->eq(
                        'oxuserid',
                        $qb->createNamedParameter($user->getId())
                    ),
                    $qb->expr()->eq(
                        'oxshopid',
                        $qb->createNamedParameter(Registry::getConfig()->getShopId())
                    )
                )
            );

        foreach ($qb->execute()->fetchAllAssociative() as $fields) {
            $id = $fields['oxid'];
            $credential = clone $this->getBaseObject();
            $credential->load($id);
            $this->offsetSet($id, $credential);
        }

        return $this;
    }

    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void
    {
        $this->getBaseObject()->saveCredentialSource($publicKeyCredentialSource);
    }
}