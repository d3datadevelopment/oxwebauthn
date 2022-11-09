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

namespace D3\Webauthn\Application\Controller\Traits;

use D3\Webauthn\Application\Model\Webauthn;
use D3\Webauthn\Application\Model\WebauthnConf;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Exception as DoctrineException;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Session;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

trait checkoutGetUserTrait
{
    /**
     * @return false|User
     * @throws ContainerExceptionInterface
     * @throws DoctrineException
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    public function getUser()
    {
        $user = parent::getUser();

        if ($user && $user->getId()) {
            $webauthn = $this->d3GetWebauthnObject();

            if ($webauthn->isActive($user->getId())
                && !$this->d3WebauthnGetSessionObject()->getVariable(WebauthnConf::WEBAUTHN_SESSION_AUTH)
            ) {
                return false;
            }
        }

        return $user;
    }

    /**
     * @return Webauthn
     */
    public function d3GetWebauthnObject(): Webauthn
    {
        return oxNew(Webauthn::class);
    }

    /**
     * @return Session
     */
    public function d3WebauthnGetSessionObject(): Session
    {
        return Registry::getSession();
    }
}