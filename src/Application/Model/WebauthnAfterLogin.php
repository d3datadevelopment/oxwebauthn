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

namespace D3\Webauthn\Application\Model;

use D3\TestingTools\Production\IsMockable;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\UtilsServer;

class WebauthnAfterLogin
{
    use IsMockable;

    public function setDisplayProfile()
    {
        $sProfile = Registry::getRequest()->getRequestEscapedParameter('profile') ?:
            Registry::getSession()->getVariable(WebauthnConf::WEBAUTHN_ADMIN_PROFILE);

        Registry::getSession()->deleteVariable(WebauthnConf::WEBAUTHN_ADMIN_PROFILE);

        $myUtilsServer = $this->d3GetMockableRegistryObject(UtilsServer::class);

        if (isset($sProfile)) {
            $aProfiles = Registry::getSession()->getVariable("aAdminProfiles");
            if ($aProfiles && isset($aProfiles[$sProfile])) {
                // setting cookie to store last locally used profile
                $myUtilsServer->setOxCookie("oxidadminprofile", $sProfile . "@" . implode("@", $aProfiles[$sProfile]), time() + 31536000);
                Registry::getSession()->setVariable("profile", $aProfiles[$sProfile]);
            }
        } else {
            //deleting cookie info, as setting profile to default
            $myUtilsServer->setOxCookie("oxidadminprofile", "", time() - 3600);
        }
    }

    /**
     * @return void
     */
    public function changeLanguage()
    {
        $myUtilsServer = $this->d3GetMockableRegistryObject(UtilsServer::class);
        // languages
        $iLang = Registry::getRequest()->getRequestEscapedParameter('chlanguage') ?:
            Registry::getSession()->getVariable(WebauthnConf::WEBAUTHN_ADMIN_CHLANGUAGE);

        Registry::getSession()->deleteVariable(WebauthnConf::WEBAUTHN_ADMIN_CHLANGUAGE);

        $aLanguages = Registry::getLang()->getAdminTplLanguageArray();
        if (!isset($aLanguages[$iLang])) {
            $iLang = key($aLanguages);
        }

        $myUtilsServer->setOxCookie("oxidadminlanguage", $aLanguages[$iLang]->abbr, time() + 31536000);
        Registry::getLang()->setTplLanguage($iLang);
    }
}