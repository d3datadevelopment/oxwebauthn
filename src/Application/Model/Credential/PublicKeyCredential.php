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
use DateTime;
use Doctrine\DBAL\Driver\Exception as DoctrineDriverException;
use Doctrine\DBAL\Exception as DoctrineException;
use Doctrine\DBAL\Query\QueryBuilder;
use Exception;
use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webauthn\PublicKeyCredentialSource;

class PublicKeyCredential extends BaseModel
{
    use IsMockable;

    protected $_sCoreTable = 'd3wa_usercredentials';

    public function __construct()
    {
        $this->init($this->getCoreTableName());

        parent::__construct();
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->assign([
            'name' => $name
        ]);
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->getFieldData('name');
    }

    /**
     * @param string $credentialId
     */
    public function setCredentialId(string $credentialId): void
    {
        $this->assign([
            'credentialid' => base64_encode($credentialId)
        ]);
    }

    /**
     * @return false|string
     */
    public function getCredentialId(): ?string
    {
        return base64_decode($this->__get($this->_getFieldLongName('credentialid'))->rawValue);
    }

    /**
     * @param string $userId
     */
    public function setUserId(string $userId): void
    {
        $this->assign([
            'oxuserid' => $userId
        ]);
    }

    /**
     * @return string|null
     */
    public function getUserId(): ?string
    {
        return $this->__get($this->_getFieldLongName('oxuserid'))->rawValue;
    }

    /**
     * @param PublicKeyCredentialSource $credential
     */
    public function setCredential(PublicKeyCredentialSource $credential): void
    {
        $this->assign([
            'credential' => base64_encode(serialize($credential))
        ]);
    }

    /**
     * @return false|PublicKeyCredentialSource
     */
    public function getCredential(): ?PublicKeyCredentialSource
    {
        return unserialize(base64_decode($this->__get($this->_getFieldLongName('credential'))->rawValue));
    }

    /**
     * @param PublicKeyCredentialSource $publicKeyCredentialSource
     * @param string|null               $keyName
     *
     * @return void
     * @throws ContainerExceptionInterface
     * @throws DoctrineDriverException
     * @throws DoctrineException
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource, string $keyName = null): void
    {
        // item exist already
        if ($this->d3GetMockableOxNewObject(PublicKeyCredentialList::class)
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
     *
     * @return string|null
     * @throws ContainerExceptionInterface
     * @throws DoctrineDriverException
     * @throws DoctrineException
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
                        $qb->createNamedParameter($this->d3GetMockableRegistryObject(Config::class)->getShopId())
                    )
                )
            );
        $oxid = $qb->execute()->fetchOne();

        return strlen((string) $oxid) ? $oxid : null;
    }
}