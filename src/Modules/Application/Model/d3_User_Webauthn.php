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

namespace D3\Webauthn\Modules\Application\Model;

use D3\TestingTools\Production\IsMockable;
use D3\Webauthn\Application\Model\WebauthnConf;
use Doctrine\DBAL\Driver\Exception as DoctrineDriverException;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Query\QueryBuilder;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;

class d3_User_Webauthn extends d3_User_Webauthn_parent
{
    use IsMockable;

    public function logout()
    {
        $return = $this->d3CallMockableFunction([d3_User_Webauthn_parent::class, 'logout']);
        $this->d3WebauthnLogout();

        return $return;
    }

    /**
     * @return void
     */
    protected function d3WebauthnLogout(): void
    {
        $session = d3GetOxidDIC()->get('d3ox.webauthn.'.Session::class);
        $session->deleteVariable(WebauthnConf::WEBAUTHN_SESSION_AUTH);
        $session->deleteVariable(WebauthnConf::WEBAUTHN_LOGIN_OBJECT);
        $session->deleteVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER);
        $session->deleteVariable(WebauthnConf::WEBAUTHN_SESSION_LOGINUSER);
        $session->deleteVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTCLASS);

        $session->deleteVariable(WebauthnConf::WEBAUTHN_ADMIN_SESSION_AUTH);
        $session->deleteVariable(WebauthnConf::WEBAUTHN_ADMIN_LOGIN_OBJECT);
        $session->deleteVariable(WebauthnConf::WEBAUTHN_ADMIN_SESSION_CURRENTUSER);
        $session->deleteVariable(WebauthnConf::WEBAUTHN_ADMIN_SESSION_LOGINUSER);
        $session->deleteVariable(WebauthnConf::WEBAUTHN_ADMIN_SESSION_CURRENTCLASS);

        $session->deleteVariable(WebauthnConf::WEBAUTHN_SESSION_NAVFORMPARAMS);
    }

    /**
     * @param $userName
     * @param $password
     * @param bool $setSessionCookie
     * @return bool
     * @throws ReflectionException
     */
    public function login($userName, $password, $setSessionCookie = false)
    {
        $userName = $this->d3WebauthnLogin($userName);

        return $this->d3CallMockableFunction([d3_User_Webauthn_parent::class, 'login'], [$userName, $password, $setSessionCookie]);
    }

    /**
     * @param string $userName
     * @return mixed|string|null
     * @throws ReflectionException
     */
    protected function d3WebauthnLogin(string $userName)
    {
        $session = d3GetOxidDIC()->get('d3ox.webauthn.'.Session::class);

        if ($session->getVariable(WebauthnConf::WEBAUTHN_SESSION_AUTH) &&
            $userName === $session->getVariable(WebauthnConf::WEBAUTHN_SESSION_LOGINUSER)
        ) {
            $userName = $userName ?: $session->getVariable(WebauthnConf::WEBAUTHN_SESSION_LOGINUSER);
            $shopId = d3GetOxidDIC()->get('d3ox.webauthn.'.Config::class)->getShopId();

            /** private method is out of scope */
            $class = new ReflectionClass($this);
            $method = $class->getMethod('loadAuthenticatedUser');
            $method->setAccessible(true);
            $method->invokeArgs(
                $this,
                [
                    $userName,
                    $shopId,
                ]
            );
        }
        return $userName;
    }

    /**
     * @param string|null $username
     * @param string|null $rights
     * @return string|null
     * @throws ContainerExceptionInterface
     * @throws DoctrineDriverException
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    public function d3GetLoginUserId(?string $username, string $rights = null): ?string
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
                        $qb->createNamedParameter(d3GetOxidDIC()->get('d3ox.webauthn.'.Config::class)->getShopId())
                    ),
                    $rights ?
                        $qb->expr()->eq(
                            'oxrights',
                            $qb->createNamedParameter($rights)
                        ) : '1'
                )
            )->setMaxResults(1);

        return $qb->execute()->fetchOne() ?: null;
    }
}
