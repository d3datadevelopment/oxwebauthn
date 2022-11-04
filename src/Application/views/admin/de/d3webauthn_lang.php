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

use D3\Webauthn\Application\Model\WebauthnConf;

$sLangName = "Deutsch";

$aLang = [
    'charset'                                         => 'UTF-8',

    'D3_WEBAUTHN_ERROR_UNVALID'                       => 'Der verwendete Schlüssel ist ungültig oder kann nicht geprüft werden.',
    'D3_WEBAUTHN_ERROR_MISSINGPKC'                    => 'Keine prüfbaren Anfrageoptionen gespeichert. Bitte führen Sie die Anmeldung noch einmal durch bzw. wenden sich an den Betreiber.',
    'WEBAUTHN_INPUT_HELP'                             => 'Bitte mit Hardwareschlüssel authentisieren.',
    'WEBAUTHN_CANCEL_LOGIN'                           => 'Anmeldung abbrechen',
    'D3WEBAUTHN_CONF_BROWSER_REQUEST'                 => 'Bitte die Anfrage des Browsers bestätigen:',
    'D3WEBAUTHN_CANCEL'                               => 'Abbrechen',
    'D3WEBAUTHN_DELETE'                               => 'Löschen',
    'D3WEBAUTHN_DELETE_CONFIRM'                       => 'Soll der Schlüssel wirklich gelöscht werden?',
    'D3WEBAUTHN_CANCELNOKEYREGISTERED'                => 'kein Schlüssel registriert',

    'd3mxuser_webauthn'                               => 'Hardwareschlüssel',

    'D3_WEBAUTHN_REGISTERNEW'                         => 'neue Registrierung erstellen',
    'D3_WEBAUTHN_ADDKEY'                              => 'Sicherheitsschlüssel hinzufügen',
    'D3_WEBAUTHN_KEYNAME'                             => 'Name des Schlüssels',

    'D3_WEBAUTHN_REGISTEREDKEYS'                      => 'registrierte Schlüssel',

    'D3_WEBAUTHN_ERR_UNSECURECONNECTION'              => 'Die Verwendung von Sicherheitsschlüsseln ist nur bei lokalen oder gesicherten Verbindungen (https) möglich.',
    'D3_WEBAUTHN_ERR_INVALIDSTATE_'.WebauthnConf::TYPE_CREATE   => 'Der Schlüssel vom Token kann nicht oder nicht mehr verwendet werden. Möglicherweise wurde dieser in Ihrem Konto schon einmal gespeichert.',
    'D3_WEBAUTHN_ERR_INVALIDSTATE_'.WebauthnConf::TYPE_GET      => 'Der Schlüssel kann nicht validiert werden.',
    'D3_WEBAUTHN_ERR_NOTALLOWED'                      => 'Die Anfrage wurde vom Browser oder der Plattform nicht zugelassen. Möglicherweise fehlen Berechtigungen oder die Zeit ist abgelaufen.',
    'D3_WEBAUTHN_ERR_ABORT'                           => 'Die Aktion wurde vom Browser oder der Plattform abgebrochen.',
    'D3_WEBAUTHN_ERR_CONSTRAINT'                      => 'Die Aktion konnte vom authentisierenden Gerät nicht durchgeführt werden.',
    'D3_WEBAUTHN_ERR_NOTSUPPORTED'                    => 'Die Aktion wird nicht unterstützt.',
    'D3_WEBAUTHN_ERR_UNKNOWN'                         => 'Die Aktion wurde wegen eines unbekannten Fehlers abgebrochen.',
    'D3_WEBAUTHN_ERR_NOPUBKEYSUPPORT'                 => 'Ihr Browser unterstützt die Verwendung von Hardwareschlüsseln leider nicht.',
    'D3_WEBAUTHN_ERR_TECHNICALERROR'                  => 'Beim Prüfen der Zugangsdaten ist ein technischer Fehler aufgetreten.',
    'D3_WEBAUTHN_ERR_NOTLOADEDUSER'                   => "Kann keine Anmeldedaten von nicht geladenem Kundenkonto beziehen.",

    'D3_WEBAUTHN_ERR_LOGINPROHIBITED'                 => 'Die Anmeldung mit Sicherheitsschlüssel ist aus technischen Gründen derzeit leider nicht möglich. Bitte verwenden Sie statt dessen Ihr Passwort.',

    'SHOP_MODULE_GROUP_d3webauthn_general'            => 'Grundeinstellungen',
    'SHOP_MODULE_d3webauthn_diffshopurl'              => 'abweichende Shop-URL',
    'HELP_SHOP_MODULE_d3webauthn_diffshopurl'         => '<p>Die Zugangsdaten werden für die URL Ihres Shops festgeschrieben. Dazu wird bei jeder Anfrage die Domain Ihres Shops ohne "http(s)://" und ohne "www." übergeben.</p>'.
                                                         '<p>Ist Ihr Shop unter verschiedenen Subdomains erreichbar, können Sie hier die Hauptdomain angeben, die zur Registrierung verwendet werden soll. Beachten Sie bitte, '.
                                                         'dass die hier angegebene Adresse mit der des Shopaufrufs übereinstimmen muss. Shopfremde Adressen werden bei der Verwendung abgelehnt.</p>'.
                                                         '<p>Bleibt das Feld leer, wird die Adresse des aktuellen Shopaufrufs verwendet. Bei Verwendung unterschiedlicher Adressen muss vom Nutzer für jede Adresse eine separate '.
                                                         'Schlüsselregistrierung durchgeführt werden.</p>'
];
