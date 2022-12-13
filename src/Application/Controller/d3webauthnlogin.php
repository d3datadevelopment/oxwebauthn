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

namespace D3\Webauthn\Application\Controller;

use D3\TestingTools\Production\IsMockable;
use D3\Webauthn\Application\Model\Webauthn;
use D3\Webauthn\Application\Model\WebauthnConf;
use D3\Webauthn\Application\Model\Exceptions\WebauthnException;
use Doctrine\DBAL\Driver\Exception as DoctrineDriverException;
use Doctrine\DBAL\Exception as DoctrineException;
use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Routing\ControllerClassNameResolver;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\Eshop\Core\Utils;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class d3webauthnlogin extends FrontendController
{
    use IsMockable;

    protected $_sThisTemplate = 'd3webauthnlogin.tpl';

    /**
     * @return array
     */
    public function getNavigationParams(): array
    {
        $navparams = $this->d3GetMockableRegistryObject(Session::class)->getVariable(
            WebauthnConf::WEBAUTHN_SESSION_NAVPARAMS
        );

        return array_merge(
            $this->d3CallMockableFunction([FrontendController::class, 'getNavigationParams']),
            $navparams,
            ['cl' => $navparams['actcontrol']]
        );
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
        if ($this->d3GetMockableRegistryObject(Session::class)
                 ->hasVariable(WebauthnConf::WEBAUTHN_SESSION_AUTH) ||
            !$this->d3GetMockableRegistryObject(Session::class)
                  ->hasVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER)
        ) {
            $this->d3GetMockableRegistryObject(Utils::class)->redirect('index.php?cl=start');
        }

        $this->generateCredentialRequest();

        $this->addTplParam('navFormParams', $this->d3GetMockableRegistryObject(Session::class)
                                                 ->getVariable(WebauthnConf::WEBAUTHN_SESSION_NAVFORMPARAMS));

        return $this->d3CallMockableFunction([FrontendController::class, 'render']);
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
        $userId = $this->d3GetMockableRegistryObject(Session::class)
                       ->getVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTUSER);

        try {
            /** @var Webauthn $webauthn */
            $webauthn = $this->d3GetMockableOxNewObject(Webauthn::class);
            $publicKeyCredentialRequestOptions = $webauthn->getRequestOptions($userId);
            $this->addTplParam('webauthn_publickey_login', $publicKeyCredentialRequestOptions);
            $this->addTplParam('isAdmin', isAdmin());
        } catch (WebauthnException $e) {
            $this->d3GetMockableRegistryObject(Session::class)
                 ->setVariable(WebauthnConf::GLOBAL_SWITCH, true);
            $this->d3GetMockableLogger()->error($e->getDetailedErrorMessage(), ['UserId' => $userId]);
            $this->d3GetMockableLogger()->debug($e->getTraceAsString());
            Registry::getUtilsView()->addErrorToDisplay($e);
            $this->d3GetMockableRegistryObject(Utils::class)->redirect('index.php?cl=start');
        }
    }

    /**
     * @return string|null
     */
    public function d3GetPreviousClass(): ?string
    {
        return $this->d3GetMockableRegistryObject(Session::class)
                    ->getVariable(WebauthnConf::WEBAUTHN_SESSION_CURRENTCLASS);
    }

    /**
     * @return bool
     */
    public function previousClassIsOrderStep(): bool
    {
        $sClassKey = $this->d3GetPreviousClass();
        $resolvedClass = $this->d3GetMockableRegistryObject(ControllerClassNameResolver::class)
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
     * @return array
     */
    public function getBreadCrumb(): array
    {
        $aPaths = [];
        $aPath = [];
        $iBaseLanguage = Registry::getLang()->getBaseLanguage();
        $aPath['title'] = Registry::getLang()->translateString(
            'D3_WEBAUTHN_BREADCRUMB',
            (int) $iBaseLanguage,
            false
        );
        $aPath['link'] = $this->getLink();

        $aPaths[] = $aPath;

        return $aPaths;
    }
}
