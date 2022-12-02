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
use OxidEsales\Eshop\Core\Registry;
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
        $lgn_user = $this->d3WebauthnGetRequestObject()->getRequestParameter( 'user') ?:
            $this->d3WebauthnGetSessionObject()->getVariable(WebauthnConf::WEBAUTHN_ADMIN_SESSION_LOGINUSER);

        /** @var d3_User_Webauthn $user */
        $user = $this->d3WebauthnGetUserObject();
        $userId = $user->d3GetLoginUserId($lgn_user, 'malladmin');

        if ( $this->d3CanUseWebauthn( $lgn_user, $userId)) {
            $this->d3WebauthnGetSessionObject()->setVariable(
                WebauthnConf::WEBAUTHN_ADMIN_PROFILE,
                $this->d3WebauthnGetRequestObject()->getRequestEscapedParameter( 'profile')
            );
            $this->d3WebauthnGetSessionObject()->setVariable(
                WebauthnConf::WEBAUTHN_ADMIN_CHLANGUAGE,
                $this->d3WebauthnGetRequestObject()->getRequestEscapedParameter( 'chlanguage')
            );

            if ($this->hasWebauthnButNotLoggedin($userId)) {
                $this->d3WebauthnGetSessionObject()->setVariable(
                    WebauthnConf::WEBAUTHN_ADMIN_SESSION_CURRENTCLASS,
                    $this->getClassKey() != 'd3webauthnadminlogin' ? $this->getClassKey() : 'admin_start'
                );
                $this->d3WebauthnGetSessionObject()->setVariable(
                    WebauthnConf::WEBAUTHN_ADMIN_SESSION_CURRENTUSER,
                    $userId
                );
                $this->d3WebauthnGetSessionObject()->setVariable(
                    WebauthnConf::WEBAUTHN_ADMIN_SESSION_LOGINUSER,
                    $lgn_user
                );

                return "d3webauthnadminlogin";
            }
        }

        return $this->d3CallMockableParent('checklogin');
    }

    /**
     * @return Webauthn
     */
    public function d3GetWebauthnObject(): Webauthn
    {
        return oxNew(Webauthn::class);
    }

    /**
     * @return void
     */
    public function d3WebauthnCancelLogin(): void
    {
        $oUser = $this->d3WebauthnGetUserObject();
        $oUser->logout();
    }

    /**
     * @return User
     */
    public function d3WebauthnGetUserObject(): User
    {
        return oxNew(User::class);
    }

    /**
     * @return Request
     */
    public function d3WebauthnGetRequestObject(): Request
    {
        return Registry::getRequest();
    }

    /**
     * @return Session
     */
    public function d3WebauthnGetSessionObject(): Session
    {
        return Registry::getSession();
    }

    /**
     * @param             $lgn_user
     * @param string|null $userId
     *
     * @return bool
     */
    protected function d3CanUseWebauthn( $lgn_user, ?string $userId): bool
    {
        $password = $this->d3WebauthnGetRequestObject()->getRequestParameter( 'pwd');

        return $lgn_user &&
               $userId &&
               false === $this->d3WebauthnGetSessionObject()->hasVariable( WebauthnConf::WEBAUTHN_ADMIN_SESSION_AUTH ) &&
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
        $webauthn = $this->d3GetWebauthnObject();

        return $webauthn->isActive($userId)
            && !$this->d3WebauthnGetSessionObject()->getVariable(WebauthnConf::WEBAUTHN_ADMIN_SESSION_AUTH);
    }
}