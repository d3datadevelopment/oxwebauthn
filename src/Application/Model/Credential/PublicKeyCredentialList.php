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

namespace D3\Webauthn\Application\Model\Credential;

use D3\TestingTools\Production\IsMockable;
use Doctrine\DBAL\Driver\Exception as DoctrineDriverException;
use Doctrine\DBAL\Exception as DoctrineException;
use Doctrine\DBAL\Query\QueryBuilder;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Model\ListModel;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\PublicKeyCredentialUserEntity;

class PublicKeyCredentialList extends ListModel implements PublicKeyCredentialSourceRepository
{
    use IsMockable;

    protected $_sObjectsInListName = PublicKeyCredential::class;

    public function __construct()
    {
        $this->d3CallMockableFunction([ListModel::class, '__construct'], [PublicKeyCredential::class]);
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
                        $qb->createNamedParameter(base64_encode($publicKeyCredentialId))
                    ),
                    $qb->expr()->eq(
                        'oxshopid',
                        $qb->createNamedParameter($this->d3GetMockableRegistryObject(Config::class)->getShopId())
                    )
                )
            );
        $credential = $qb->execute()->fetchOne();

        if (!strlen((string) $credential)) {
            return null;
        }

        $credential = unserialize(base64_decode($credential));

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
                        $qb->createNamedParameter($this->d3GetMockableRegistryObject(Config::class)->getShopId())
                    )
                )
            );

        // generate decoded credentials list
        return array_map(function (array $fields) {
            return unserialize(base64_decode($fields['credential']));
        }, $qb->execute()->fetchAllAssociative());
    }

    /**
     * @param User $user
     * @return self
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
                        $qb->createNamedParameter($this->d3GetMockableRegistryObject(Config::class)->getShopId())
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

    /**
     * @param PublicKeyCredentialSource $publicKeyCredentialSource
     * @return void
     */
    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void
    {
        /** @var PublicKeyCredential $base */
        $base = $this->getBaseObject();
        $base->saveCredentialSource($publicKeyCredentialSource);
    }
}
