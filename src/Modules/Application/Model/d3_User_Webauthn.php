<?php

/**
 * This Software is the property of Data Development and is protected
 * by copyright law - it is NOT Freeware.
 * Any unauthorized use of this software without a valid license
 * is a violation of the license agreement and will be prosecuted by
 * civil and criminal law.
 * http://www.shopmodule.com
 *
 * @copyright (C) D3 Data Development (Inh. Thomas Dartsch)
 * @author        D3 Data Development - Daniel Seifert <support@shopmodule.com>
 * @link          http://www.oxidmodule.com
 */

declare(strict_types=1);

namespace D3\Webauthn\Modules\Application\Model;

use D3\Webauthn\Application\Model\WebauthnConf;
use Doctrine\DBAL\Driver\Exception as DoctrineDriverException;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Query\QueryBuilder;
use OxidEsales\Eshop\Core\Exception\UserException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;

class d3_User_Webauthn extends d3_User_Webauthn_parent
{
    public function logout()
    {
        $return = parent::logout();

        Registry::getSession()->deleteVariable(WebauthnConf::WEBAUTHN_SESSION_AUTH);
        Registry::getSession()->deleteVariable(WebauthnConf::WEBAUTHN_LOGIN_OBJECT);
        Registry::getSession()->deleteVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER);
        Registry::getSession()->deleteVariable(WebauthnConf::WEBAUTHN_SESSION_LOGINUSER);
        Registry::getSession()->deleteVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTCLASS);
        Registry::getSession()->deleteVariable(WebauthnConf::WEBAUTHN_SESSION_NAVFORMPARAMS);

        return $return;
    }

    /**
     * @param $userName
     * @param $password
     * @param $setSessionCookie
     * @return bool
     * @throws UserException
     * @throws ReflectionException
     */
    public function login($userName, $password, $setSessionCookie = false)
    {
        if (Registry::getSession()->getVariable(WebauthnConf::WEBAUTHN_SESSION_AUTH)) {
            $userName = $userName ?: Registry::getSession()->getVariable(WebauthnConf::WEBAUTHN_SESSION_LOGINUSER);
            $config = Registry::getConfig();
            $shopId = $config->getShopId();

            /** private method is out of scope */
            $class = new ReflectionClass($this);
            $method = $class->getMethod('loadAuthenticatedUser');
            $method->setAccessible(true);
            $method->invokeArgs(
                $this,
                [
                    Registry::getSession()->getVariable(WebauthnConf::WEBAUTHN_SESSION_LOGINUSER),
                    $shopId
                ]
            );
        }

        return parent::login($userName, $password, $setSessionCookie);
    }

    /**
     * @param string $username
     * @param string|null $rights
     * @return string|null
     * @throws ContainerExceptionInterface
     * @throws DoctrineDriverException
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    public function d3GetLoginUserId(string $username, string $rights = null): ?string
    {
        if (empty($username)) {
            return null;
        }

        /** @var QueryBuilder $qb */
        $qb = ContainerFactory::getInstance()->getContainer()->get(QueryBuilderFactoryInterface::class)->create();
        $qb->select('oxid')
            ->from($this->getViewName())
            ->where(
                $qb->expr()->and(
                    $qb->expr()->eq(
                        'oxusername',
                        $qb->createNamedParameter($username)
                    ),
                    $qb->expr()->eq(
                        'oxshopid',
                        $qb->createNamedParameter(Registry::getConfig()->getShopId())
                    ),
                    $rights ? $qb->expr()->eq(
                        'oxrights',
                        $qb->createNamedParameter($rights)
                    ) : '1'
                )
            )->setMaxResults(1);

        return $qb->execute()->fetchOne();
    }
}