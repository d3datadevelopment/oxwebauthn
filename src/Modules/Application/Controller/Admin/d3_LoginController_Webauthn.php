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

namespace D3\Webauthn\Modules\Application\Controller\Admin;

use D3\Webauthn\Application\Model\Webauthn;
use D3\Webauthn\Application\Model\WebauthnConf;
use Doctrine\DBAL\Driver\Exception as DoctrineException;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Query\QueryBuilder;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\Eshop\Core\UtilsView;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class d3_LoginController_Webauthn extends d3_LoginController_Webauthn_parent
{
    /**
     * @return Webauthn
     */
    public function d3GetWebauthnObject(): Webauthn
    {
        return oxNew(Webauthn::class);
    }

    /**
     * @return mixed|string
     * @throws ContainerExceptionInterface
     * @throws DoctrineException
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    public function checklogin()
    {
        $lgn_user = Registry::getRequest()->getRequestParameter('user');
        $userId = $this->d3GetLoginUserId($lgn_user);

        if ($lgn_user && $userId && false === Registry::getSession()->hasVariable(WebauthnConf::WEBAUTHN_SESSION_AUTH)) {
            $webauthn = $this->d3GetWebauthnObject();

            if ($webauthn->isActive($userId)
                && !Registry::getSession()->getVariable(WebauthnConf::WEBAUTHN_SESSION_AUTH)
            ) {
                Registry::getSession()->setVariable(
                    WebauthnConf::WEBAUTHN_SESSION_CURRENTCLASS,
                    $this->getClassKey() != 'd3webauthnadminlogin' ? $this->getClassKey() : 'admin_start'
                );
                Registry::getSession()->setVariable(
                    WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER,
                    $userId
                );
                Registry::getSession()->setVariable(
                    WebauthnConf::WEBAUTHN_SESSION_LOGINUSER,
                    $lgn_user
                );

/*
                Registry::getSession()->setVariable(
                    WebauthnConf::WEBAUTHN_SESSION_NAVFORMPARAMS,
                    $this->getViewConfig()->getNavFormParams()
                );
*/
                //$oUser->d3templogout();

                return "d3webauthnadminlogin";
            }
        }

        return parent::checklogin();
    }

    /**
     * @param $username
     * @return string|null
     * @throws DoctrineException
     * @throws Exception
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function d3GetLoginUserId($username): ?string
    {
        if (empty($username)) {
            return null;
        }

        $user = oxNew(User::class);

        /** @var QueryBuilder $qb */
        $qb = ContainerFactory::getInstance()->getContainer()->get(QueryBuilderFactoryInterface::class)->create();
        $qb->select('oxid')
            ->from($user->getViewName())
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
                    $qb->expr()->eq(
                        'oxrights',
                        $qb->createNamedParameter('malladmin')
                    )
                )
            )->setMaxResults(1);

        return $qb->execute()->fetchOne();
    }

    public function d3WebauthnCancelLogin()
    {
        $oUser = $this->d3GetUserObject();
        $oUser->logout();
    }

    /**
     * @return User
     */
    public function d3GetUserObject()
    {
        return oxNew(User::class);
    }
}