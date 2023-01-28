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

namespace D3\Webauthn\Application\Controller\Admin;

use D3\TestingTools\Production\IsMockable;
use D3\Webauthn\Application\Model\Exceptions\WebauthnGetException;
use D3\Webauthn\Application\Model\Webauthn;
use D3\Webauthn\Application\Model\WebauthnAfterLogin;
use D3\Webauthn\Application\Model\WebauthnConf;
use D3\Webauthn\Application\Model\Exceptions\WebauthnException;
use D3\Webauthn\Application\Model\WebauthnLogin;
use Doctrine\DBAL\Driver\Exception as DoctrineDriverException;
use Doctrine\DBAL\Exception as DoctrineException;
use OxidEsales\Eshop\Application\Controller\Admin\AdminController;
use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Core\Routing\ControllerClassNameResolver;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\Eshop\Core\Utils;
use OxidEsales\Eshop\Core\UtilsView;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;

class d3webauthnadminlogin extends AdminController
{
    use IsMockable;

    protected $_sThisTemplate = 'd3webauthnadminlogin.tpl';

    /**
     * @return bool
     */
    protected function _authorize(): bool
    {
        return true;
    }

    /**
     * @return string
     * @throws ContainerExceptionInterface
     * @throws DoctrineDriverException
     * @throws DoctrineException
     * @throws NotFoundExceptionInterface
     */
    public function render(): string
    {
        if (d3GetOxidDIC()->get('d3ox.webauthn.'.Session::class)
                 ->hasVariable(WebauthnConf::WEBAUTHN_ADMIN_SESSION_AUTH)
        ) {
            d3GetOxidDIC()->get('d3ox.webauthn.'.Utils::class)->redirect('index.php?cl=admin_start');
        } elseif (!d3GetOxidDIC()->get('d3ox.webauthn.'.Session::class)
                        ->hasVariable(WebauthnConf::WEBAUTHN_ADMIN_SESSION_CURRENTUSER)
        ) {
            d3GetOxidDIC()->get('d3ox.webauthn.'.Utils::class)->redirect('index.php?cl=login');
        }

        $this->generateCredentialRequest();

        $this->addTplParam('navFormParams', d3GetOxidDIC()->get('d3ox.webauthn.'.Session::class)
                                                 ->getVariable(WebauthnConf::WEBAUTHN_SESSION_NAVFORMPARAMS));
        $this->addTplParam('currentProfile', d3GetOxidDIC()->get('d3ox.webauthn.'.Session::class)
                                                  ->getVariable(WebauthnConf::WEBAUTHN_ADMIN_PROFILE));
        d3GetOxidDIC()->get('d3ox.webauthn.'.Session::class)
             ->deleteVariable(WebauthnConf::WEBAUTHN_ADMIN_PROFILE);
        $this->addTplParam('currentChLanguage', d3GetOxidDIC()->get('d3ox.webauthn.'.Session::class)
                                                 ->getVariable(WebauthnConf::WEBAUTHN_ADMIN_CHLANGUAGE));

        $afterLogin = d3GetOxidDIC()->get(WebauthnAfterLogin::class);
        $afterLogin->changeLanguage();

        return $this->d3CallMockableFunction([AdminController::class, 'render']);
    }

    /**
     * @return void
     * @throws DoctrineDriverException
     * @throws DoctrineException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function generateCredentialRequest(): void
    {
        $userId = d3GetOxidDIC()->get('d3ox.webauthn.'.Session::class)
                       ->getVariable(WebauthnConf::WEBAUTHN_ADMIN_SESSION_CURRENTUSER);
        try {
            $webauthn = d3GetOxidDIC()->get(Webauthn::class);
            $publicKeyCredentialRequestOptions = $webauthn->getRequestOptions($userId);
            d3GetOxidDIC()->get('d3ox.webauthn.'.Session::class)
                 ->setVariable(WebauthnConf::WEBAUTHN_ADMIN_LOGIN_OBJECT, $publicKeyCredentialRequestOptions);
            $this->addTplParam('webauthn_publickey_login', $publicKeyCredentialRequestOptions);
            $this->addTplParam('isAdmin', isAdmin());
        } catch (WebauthnException $e) {
            d3GetOxidDIC()->get('d3ox.webauthn.'.Session::class)
                 ->setVariable(WebauthnConf::GLOBAL_SWITCH, true);
            d3GetOxidDIC()->get('d3ox.webauthn.'.UtilsView::class)->addErrorToDisplay($e);
            d3GetOxidDIC()->get('d3ox.webauthn.'.LoggerInterface::class)->error($e->getDetailedErrorMessage(), ['UserId'   => $userId]);
            d3GetOxidDIC()->get('d3ox.webauthn.'.LoggerInterface::class)->debug($e->getTraceAsString());
            d3GetOxidDIC()->get('d3ox.webauthn.'.Utils::class)->redirect('index.php?cl=login');
        }
    }

    /**
     * @return string|null
     */
    public function d3AssertAuthn(): ?string
    {
        try {
            $login = $this->getWebAuthnLogin();
            return $login->adminLogin(
                d3GetOxidDIC()->get('d3ox.webauthn.'.Request::class)->getRequestEscapedParameter('profile')
            );
        } catch (WebauthnGetException $e) {
            d3GetOxidDIC()->get('d3ox.webauthn.'.UtilsView::class)->addErrorToDisplay($e);
            return 'login';
        }
    }

    /**
     * @return string|null
     */
    public function d3GetPreviousClass(): ?string
    {
        return d3GetOxidDIC()->get('d3ox.webauthn.'.Session::class)
                    ->getVariable(WebauthnConf::WEBAUTHN_ADMIN_SESSION_CURRENTCLASS);
    }

    /**
     * @return bool
     */
    public function previousClassIsOrderStep(): bool
    {
        $sClassKey = $this->d3GetPreviousClass();
        $resolvedClass = d3GetOxidDIC()->get('d3ox.webauthn.'.ControllerClassNameResolver::class)
                              ->getClassNameById($sClassKey);
        $resolvedClass = $resolvedClass ?: 'start';

        /** @var FrontendController $oController */
        $oController = oxNew($resolvedClass);
        return $oController->getIsOrderStep();
    }

    /**
     * @return bool
     */
    public function getIsOrderStep(): bool
    {
        return $this->previousClassIsOrderStep();
    }

    /**
     * @return WebauthnLogin
     */
    protected function getWebAuthnLogin(): WebauthnLogin
    {
        /** @var Request $request */
        $request = d3GetOxidDIC()->get('d3ox.webauthn.'.Request::class);

        return oxNew(
            WebauthnLogin::class,
            $request->getRequestEscapedParameter('credential'),
            $request->getRequestEscapedParameter('error')
        );
    }
}
