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

namespace D3\Webauthn\Modules\Application\Component;

use D3\TestingTools\Production\IsMockable;
use D3\Webauthn\Application\Model\Exceptions\WebauthnGetException;
use D3\Webauthn\Application\Model\Exceptions\WebauthnLoginErrorException;
use D3\Webauthn\Application\Model\Webauthn;
use D3\Webauthn\Application\Model\WebauthnConf;
use D3\Webauthn\Application\Model\WebauthnLogin;
use D3\Webauthn\Modules\Application\Model\d3_User_Webauthn;
use Doctrine\DBAL\Driver\Exception as DoctrineDriverException;
use Doctrine\DBAL\Exception;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\Eshop\Core\Utils;
use OxidEsales\Eshop\Core\UtilsView;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class d3_webauthn_UserComponent extends d3_webauthn_UserComponent_parent
{
    use IsMockable;

    /**
     * @return string
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     * @throws DoctrineDriverException
     */
    public function login()
    {
        $this->d3WebauthnLogin();

        return $this->d3CallMockableFunction([d3_webauthn_UserComponent_parent::class, 'login']);
    }

    /**
     * @return void
     * @throws DoctrineDriverException
     * @throws Exception
     */
    public function d3WebauthnLogin(): void
    {
        $lgn_user = $this->d3GetMockableRegistryObject(Request::class)->getRequestParameter( 'lgn_usr');
        /** @var d3_User_Webauthn $user */
        $user = $this->d3GetMockableOxNewObject(User::class);
        $userId = $user->d3GetLoginUserId($lgn_user);

        if ( $this->d3CanUseWebauthn( $lgn_user, $userId)) {
            if ($this->d3HasWebauthnButNotLoggedin($userId)) {
                $session = $this->d3GetMockableRegistryObject(Session::class);
                $session->setVariable(
                    WebauthnConf::WEBAUTHN_SESSION_CURRENTCLASS,
                    $this->getClassKey() != 'd3webauthnlogin' ? $this->getClassKey() : 'start'
                );
                $session->setVariable(
                    WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER,
                    $userId
                );
                $session->setVariable(
                    WebauthnConf::WEBAUTHN_SESSION_NAVPARAMS,
                    $this->getParent()->getNavigationParams()
                );
                $session->setVariable(
                    WebauthnConf::WEBAUTHN_SESSION_NAVFORMPARAMS,
                    $this->getParent()->getViewConfig()->getNavFormParams()
                );

                $sUrl = $this->d3GetMockableRegistryObject(Config::class)->getShopHomeUrl() . 'cl=d3webauthnlogin';
                $this->d3GetMockableRegistryObject(Utils::class)->redirect($sUrl);
            }
        }
    }

    /**
     * @param             $lgn_user
     * @param string|null $userId
     *
     * @return bool
     */
    protected function d3CanUseWebauthn( $lgn_user, ?string $userId): bool
    {
        $password = $this->d3GetMockableRegistryObject(Request::class)->getRequestParameter( 'lgn_pwd');

        return $lgn_user &&
            $userId &&
            false === $this->d3GetMockableRegistryObject(Session::class)
                ->hasVariable( WebauthnConf::WEBAUTHN_SESSION_AUTH ) &&
            ( ! strlen( trim( (string) $password ) ) );
    }

    /**
     * @param $userId
     * @return bool
     * @throws DoctrineDriverException
     * @throws Exception
     */
    protected function d3HasWebauthnButNotLoggedin($userId): bool
    {
        $webauthn = $this->d3GetMockableOxNewObject(Webauthn::class);

        return $webauthn->isActive($userId)
            && !$this->d3GetMockableRegistryObject(Session::class)
                ->getVariable(WebauthnConf::WEBAUTHN_SESSION_AUTH);
    }

    /**
     * @return void
     */
    public function d3CancelWebauthnLogin(): void
    {
        $this->d3WebauthnClearSessionVariables();
    }

    /**
     * @return void
     */
    public function d3WebauthnClearSessionVariables(): void
    {
        $session = $this->d3GetMockableRegistryObject(Session::class);
        $session->deleteVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTCLASS);
        $session->deleteVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER);
        $session->deleteVariable(WebauthnConf::WEBAUTHN_SESSION_NAVFORMPARAMS);
        $session->deleteVariable(WebauthnConf::WEBAUTHN_LOGIN_OBJECT);
    }

    /**
     * @return void
     */
    public function d3AssertAuthn(): void
    {
        try {
            $login = $this->d3GetMockableOxNewObject(WebauthnLogin::class,
                $this->d3GetMockableRegistryObject(Request::class)->getRequestEscapedParameter('credential'),
                $this->d3GetMockableRegistryObject(Request::class)->getRequestEscapedParameter('error')
            );
            $login->frontendLogin($this, (bool)$this->d3GetMockableRegistryObject(Request::class)
                                                    ->getRequestParameter('lgn_cook'));
            $this->_afterLogin($this->getUser());
        } catch (WebauthnGetException $e) {
            $this->d3GetMockableRegistryObject(UtilsView::class)->addErrorToDisplay($e);
        } catch (WebauthnLoginErrorException $e) {}
    }
}