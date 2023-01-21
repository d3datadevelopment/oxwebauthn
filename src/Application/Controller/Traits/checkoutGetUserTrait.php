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

use D3\TestingTools\Production\IsMockable;
use D3\Webauthn\Application\Model\Webauthn;
use D3\Webauthn\Application\Model\WebauthnConf;
use D3\Webauthn\Modules\Application\Model\d3_User_Webauthn;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Exception as DoctrineException;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Session;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

trait checkoutGetUserTrait
{
    use IsMockable;

    /**
     * @return false|User
     * @throws ContainerExceptionInterface
     * @throws DoctrineException
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    public function getUser()
    {
        /** @var d3_User_Webauthn|null $user */
        $user = $this->d3CallMockableFunction([$this->parentClass, 'getUser']);

        if ($user && $user->isLoaded() && $user->getId()) {
            $webauthn = d3GetOxidDIC()->get(Webauthn::class);

            if ($webauthn->isAvailable()
                && $webauthn->isActive($user->getId())
                && !d3GetOxidDIC()->get('d3ox.webauthn.'.Session::class)
                         ->getVariable(WebauthnConf::WEBAUTHN_SESSION_AUTH)
            ) {
                return false;
            }
        }

        return $user;
    }
}
