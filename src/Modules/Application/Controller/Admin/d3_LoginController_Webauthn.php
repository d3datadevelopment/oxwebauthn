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

namespace D3\Webauthn\Modules\Application\Controller\Admin;

use D3\TestingTools\Production\IsMockable;
use D3\Webauthn\Application\Model\Webauthn;
use D3\Webauthn\Application\Model\WebauthnConf;
use D3\Webauthn\Modules\Application\Model\d3_User_Webauthn;
use Doctrine\DBAL\Driver\Exception as DoctrineException;
use Doctrine\DBAL\Exception;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Core\Session;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class d3_LoginController_Webauthn extends d3_LoginController_Webauthn_parent
{
    use IsMockable;

    /**
     * @return mixed|string
     * @throws ContainerExceptionInterface
     * @throws DoctrineException
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    public function checklogin()
    {
        $lgn_user = $this->d3GetMockableRegistryObject(Request::class)->getRequestParameter( 'user') ?:
            $this->d3GetMockableRegistryObject(Session::class)
                 ->getVariable(WebauthnConf::WEBAUTHN_ADMIN_SESSION_LOGINUSER);

        /** @var d3_User_Webauthn $user */
        $user = $this->d3GetMockableOxNewObject(User::class);
        $userId = $user->d3GetLoginUserId($lgn_user, 'malladmin');

        if ( $this->d3CanUseWebauthn( $lgn_user, $userId)) {
            $this->d3GetMockableRegistryObject(Session::class)->setVariable(
                WebauthnConf::WEBAUTHN_ADMIN_PROFILE,
                $this->d3GetMockableRegistryObject(Request::class)
                     ->getRequestEscapedParameter( 'profile')
            );
            $this->d3GetMockableRegistryObject(Session::class)->setVariable(
                WebauthnConf::WEBAUTHN_ADMIN_CHLANGUAGE,
                $this->d3GetMockableRegistryObject(Request::class)
                     ->getRequestEscapedParameter( 'chlanguage')
            );

            if ($this->hasWebauthnButNotLoggedin($userId)) {
                $this->d3GetMockableRegistryObject(Session::class)->setVariable(
                    WebauthnConf::WEBAUTHN_ADMIN_SESSION_CURRENTCLASS,
                    $this->getClassKey() != 'd3webauthnadminlogin' ? $this->getClassKey() : 'admin_start'
                );
                $this->d3GetMockableRegistryObject(Session::class)->setVariable(
                    WebauthnConf::WEBAUTHN_ADMIN_SESSION_CURRENTUSER,
                    $userId
                );
                $this->d3GetMockableRegistryObject(Session::class)->setVariable(
                    WebauthnConf::WEBAUTHN_ADMIN_SESSION_LOGINUSER,
                    $lgn_user
                );

                return "d3webauthnadminlogin";
            }
        }

        return $this->d3CallMockableFunction([d3_LoginController_Webauthn_parent::class, 'checklogin']);
    }

    /**
     * @return void
     */
    public function d3WebauthnCancelLogin(): void
    {
        $user = $this->d3GetMockableOxNewObject(User::class);
        $user->logout();
    }

    /**
     * @param             $lgn_user
     * @param string|null $userId
     *
     * @return bool
     */
    protected function d3CanUseWebauthn( $lgn_user, ?string $userId): bool
    {
        $password = $this->d3GetMockableRegistryObject(Request::class)->getRequestParameter( 'pwd');

        return $lgn_user &&
               $userId &&
               false === $this->d3GetMockableRegistryObject(Session::class)
                              ->hasVariable( WebauthnConf::WEBAUTHN_ADMIN_SESSION_AUTH ) &&
               ( ! strlen( trim( (string) $password ) ) );
    }

    /**
     * @param $userId
     * @return bool
     * @throws DoctrineException
     * @throws Exception
     */
    protected function hasWebauthnButNotLoggedin($userId): bool
    {
        $webauthn = $this->d3GetMockableOxNewObject(Webauthn::class);

        return $webauthn->isActive($userId)
            && !$this->d3GetMockableRegistryObject(Session::class)
                     ->getVariable(WebauthnConf::WEBAUTHN_ADMIN_SESSION_AUTH);
    }
}