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

use Assert\Assert;
use Assert\AssertionFailedException;
use Assert\InvalidArgumentException;
use D3\TestingTools\Production\IsMockable;
use D3\Webauthn\Migrations\Version20230209212939;
use DateTime;
use Doctrine\DBAL\Driver\Exception as DoctrineDriverException;
use Doctrine\DBAL\Exception as DoctrineException;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Statement;
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
            'name' => $name,
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
     * @throws AssertionFailedException
     */
    public function setCredentialId(string $credentialId): void
    {
        $encodedCID = base64_encode($credentialId);

        Assert::that($encodedCID)
            ->maxLength(
                Version20230209212939::FIELDLENGTH_CREDID,
                'the credentialId (%3$d) does not fit into the database field (%2$d)'
            );

        $this->assign([
            'credentialid' => $encodedCID,
        ]);
    }

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public function getCredentialId(): ?string
    {
        $encodedCID = $this->__get($this->_getFieldLongName('credentialid'))->rawValue;

        Assert::that($encodedCID)->base64('Credential ID "%s" is not a valid base64 string.');

        return base64_decode($encodedCID);
    }

    /**
     * @param string $userId
     */
    public function setUserId(string $userId): void
    {
        $this->assign([
            'oxuserid' => $userId,
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
     * @throws AssertionFailedException
     */
    public function setCredential(PublicKeyCredentialSource $credential): void
    {
        $encodedCredential = base64_encode(serialize($credential));

        Assert::that($encodedCredential)
            ->maxLength(
                Version20230209212939::FIELDLENGTH_CREDENTIAL,
                'the credential source (%3$d) does not fit into the database field (%2$d)',
            );

        $this->assign([
            'credential' => $encodedCredential,
        ]);
    }

    /**
     * @return null|PublicKeyCredentialSource
     */
    public function getCredential(): ?PublicKeyCredentialSource
    {
        return unserialize(
            base64_decode(
                $this->__get($this->_getFieldLongName('credential'))->rawValue
            ),
            ['allowed_classes'  => [PublicKeyCredentialSource::class]]
        ) ?:
        null;
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
     * @throws AssertionFailedException
     */
    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource, string $keyName = null): void
    {
        // item exist already
        /** @var PublicKeyCredentialList $pkcl */
        $pkcl = d3GetOxidDIC()->get(PublicKeyCredentialList::class);
        if ($pkcl->findOneByCredentialId($publicKeyCredentialSource->getPublicKeyCredentialId())) {
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
                        $qb->createNamedParameter(d3GetOxidDIC()->get('d3ox.webauthn.'.Config::class)->getShopId())
                    )
                )
            );
        /** @var Statement $stmt */
        $stmt = $qb->execute();
        $oxid = $stmt->fetchOne();

        return strlen((string) $oxid) ? $oxid : null;
    }
}
