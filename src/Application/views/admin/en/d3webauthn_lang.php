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

$sLangName = "English";

$aLang = [
    'charset'                                         => 'UTF-8',

    'D3_WEBAUTHN_ERROR_UNVALID'                       => 'The used login key is invalid or cannot be checked.',
    'D3_WEBAUTHN_ERROR_MISSINGPKC'                    => 'No verifiable request options saved. Please perform the registration again or contact the operator.',
    'WEBAUTHN_INPUT_HELP'                             => 'Please authenticate with login key.',
    'WEBAUTHN_CANCEL_LOGIN'                           => 'Cancel login',
    'D3WEBAUTHN_CONF_BROWSER_REQUEST'                 => 'Please confirm the browser request:',
    'D3WEBAUTHN_CANCEL'                               => 'Cancel',
    'D3WEBAUTHN_DELETE'                               => 'Delete',
    'D3WEBAUTHN_DELETE_CONFIRM'                       => 'Do you really want to delete the login key?',
    'D3WEBAUTHN_CANCELNOKEYREGISTERED'                => 'No login key registered',

    'd3mxuser_webauthn'                               => 'login keys',

    'D3_WEBAUTHN_REGISTERNEW'                         => 'create new registration',
    'D3_WEBAUTHN_ADDKEY'                              => 'add login key',
    'D3_WEBAUTHN_KEYNAME'                             => 'Key name',

    'D3_WEBAUTHN_REGISTEREDKEYS'                      => 'registered login keys',

    'D3_WEBAUTHN_ERR_UNSECURECONNECTION'              => 'The use of login keys is only possible with local or secure connections (https).',
    'D3_WEBAUTHN_ERR_INVALIDSTATE_'.WebauthnConf::TYPE_CREATE   => 'The login key from the token cannot be used or can no longer be used. It may have been stored in your account before.',
    'D3_WEBAUTHN_ERR_INVALIDSTATE_'.WebauthnConf::TYPE_GET      => 'The login key cannot be validated.',
    'D3_WEBAUTHN_ERR_NOTALLOWED'                      => 'The request was not allowed by the browser or the platform. Possibly permissions are missing or the time has expired.',
    'D3_WEBAUTHN_ERR_ABORT'                           => 'The action was aborted by the browser or the platform.',
    'D3_WEBAUTHN_ERR_CONSTRAINT'                      => 'The action could not be performed by the authenticating device.',
    'D3_WEBAUTHN_ERR_NOTSUPPORTED'                    => 'The action is not supported.',
    'D3_WEBAUTHN_ERR_UNKNOWN'                         => 'The action was cancelled due to an unknown error.',
    'D3_WEBAUTHN_ERR_NOPUBKEYSUPPORT'                 => 'Unfortunately, your browser does not support the use of login keys.',
    'D3_WEBAUTHN_ERR_TECHNICALERROR'                  => 'A technical error occurred while checking the access data.',
    'D3_WEBAUTHN_ERR_NOTLOADEDUSER'                   => "Can't create webauthn user entity from not loaded user",

    'D3_WEBAUTHN_ERR_LOGINPROHIBITED'                 => 'Unfortunately, logging in with a login key is currently not possible for technical reasons. Please use your password instead.',
];
