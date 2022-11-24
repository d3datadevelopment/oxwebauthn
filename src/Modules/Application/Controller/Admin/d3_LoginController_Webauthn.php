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

use D3\Webauthn\Application\Model\Webauthn;
use D3\Webauthn\Application\Model\WebauthnConf;
use D3\Webauthn\Modules\Application\Model\d3_User_Webauthn;
use Doctrine\DBAL\Driver\Exception as DoctrineException;
use Doctrine\DBAL\Exception;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Registry;
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
        $lgn_user = Registry::getRequest()->getRequestParameter('user') ?:
            Registry::getSession()->getVariable(WebauthnConf::WEBAUTHN_ADMIN_SESSION_LOGINUSER);
        $password = Registry::getRequest()->getRequestParameter('pwd');

        /** @var d3_User_Webauthn $user */
        $user = $this->d3WebauthnGetUserObject();
        $userId = $user->d3GetLoginUserId($lgn_user, 'malladmin');

        if ($lgn_user && $userId &&
            false === Registry::getSession()->hasVariable(WebauthnConf::WEBAUTHN_ADMIN_SESSION_AUTH) &&
            (!strlen(trim((string) $password)))
        ) {
            Registry::getSession()->setVariable(
                WebauthnConf::WEBAUTHN_ADMIN_PROFILE,
                Registry::getRequest()->getRequestEscapedParameter('profile')
            );
            Registry::getSession()->setVariable(
                WebauthnConf::WEBAUTHN_ADMIN_CHLANGUAGE,
                Registry::getRequest()->getRequestEscapedParameter('chlanguage')
            );

            $webauthn = $this->d3GetWebauthnObject();

            if ($webauthn->isActive($userId)
                && !Registry::getSession()->getVariable(WebauthnConf::WEBAUTHN_ADMIN_SESSION_AUTH)
            ) {
                Registry::getSession()->setVariable(
                    WebauthnConf::WEBAUTHN_ADMIN_SESSION_CURRENTCLASS,
                    $this->getClassKey() != 'd3webauthnadminlogin' ? $this->getClassKey() : 'admin_start'
                );
                Registry::getSession()->setVariable(
                    WebauthnConf::WEBAUTHN_ADMIN_SESSION_CURRENTUSER,
                    $userId
                );
                Registry::getSession()->setVariable(
                    WebauthnConf::WEBAUTHN_ADMIN_SESSION_LOGINUSER,
                    $lgn_user
                );

                return "d3webauthnadminlogin";
            }
        }

        return parent::checklogin();
    }

    public function d3webauthnAfterLogin()
    {
        $myUtilsServer = Registry::getUtilsServer();
        $sProfile = Registry::getSession()->getVariable(WebauthnConf::WEBAUTHN_ADMIN_PROFILE);

        // #533
        if (isset($sProfile)) {
            $aProfiles = Registry::getSession()->getVariable("aAdminProfiles");
            if ($aProfiles && isset($aProfiles[$sProfile])) {
                // setting cookie to store last locally used profile
                $myUtilsServer->setOxCookie("oxidadminprofile", $sProfile . "@" . implode("@", $aProfiles[$sProfile]), time() + 31536000, "/");
                Registry::getSession()->setVariable("profile", $aProfiles[$sProfile]);
                Registry::getSession()->deleteVariable(WebauthnConf::WEBAUTHN_ADMIN_PROFILE);
            }
        } else {
            //deleting cookie info, as setting profile to default
            $myUtilsServer->setOxCookie("oxidadminprofile", "", time() - 3600, "/");
        }

        // languages
        $iLang = Registry::getSession()->getVariable(WebauthnConf::WEBAUTHN_ADMIN_CHLANGUAGE);

        $aLanguages = Registry::getLang()->getAdminTplLanguageArray();
        if (!isset($aLanguages[$iLang])) {
            $iLang = key($aLanguages);
        }

        $myUtilsServer->setOxCookie("oxidadminlanguage", $aLanguages[$iLang]->abbr, time() + 31536000, "/");
        Registry::getLang()->setTplLanguage($iLang);
        Registry::getSession()->deleteVariable(WebauthnConf::WEBAUTHN_ADMIN_CHLANGUAGE);
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
}