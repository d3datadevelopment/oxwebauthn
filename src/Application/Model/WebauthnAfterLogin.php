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
use OxidEsales\Eshop\Core\Language;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\Eshop\Core\UtilsServer;

class WebauthnAfterLogin
{
    use IsMockable;

    /**
     * @return void
     */
    public function setDisplayProfile(): void
    {
        $session = $this->d3GetMockableRegistryObject(Session::class);

        $sProfile = $this->d3GetMockableRegistryObject(Request::class)
            ->getRequestEscapedParameter('profile') ?:
            $session->getVariable(WebauthnConf::WEBAUTHN_ADMIN_PROFILE);

        $session->deleteVariable(WebauthnConf::WEBAUTHN_ADMIN_PROFILE);

        $myUtilsServer = $this->d3GetMockableRegistryObject(UtilsServer::class);

        if (isset($sProfile)) {
            $aProfiles = $session->getVariable("aAdminProfiles");
            if ($aProfiles && isset($aProfiles[$sProfile])) {
                // setting cookie to store last locally used profile
                $myUtilsServer->setOxCookie("oxidadminprofile", $sProfile . "@" . implode("@", $aProfiles[$sProfile]), time() + 31536000);
                $session->setVariable("profile", $aProfiles[$sProfile]);
            }
        } else {
            //deleting cookie info, as setting profile to default
            $myUtilsServer->setOxCookie("oxidadminprofile", "", time() - 3600);
        }
    }

    /**
     * @return void
     */
    public function changeLanguage(): void
    {
        $myUtilsServer = $this->d3GetMockableRegistryObject(UtilsServer::class);
        $session = $this->d3GetMockableRegistryObject(Session::class);

        // languages
        $iLang = $this->d3GetMockableRegistryObject(Request::class)
            ->getRequestEscapedParameter('chlanguage') ?:
            $session->getVariable(WebauthnConf::WEBAUTHN_ADMIN_CHLANGUAGE);

        $session->deleteVariable(WebauthnConf::WEBAUTHN_ADMIN_CHLANGUAGE);

        $language = $this->d3GetMockableRegistryObject(Language::class);
        $aLanguages = $language->getAdminTplLanguageArray();

        if (!isset($aLanguages[$iLang])) {
            $iLang = key($aLanguages);
        }

        $myUtilsServer->setOxCookie("oxidadminlanguage", $aLanguages[$iLang]->abbr, time() + 31536000);
        $language->setTplLanguage($iLang);
    }
}
