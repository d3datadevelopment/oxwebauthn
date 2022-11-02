<?php

/**
 * This Software is the property of Data Development and is protected
 * by copyright law - it is NOT Freeware.
 *
 * Any unauthorized use of this software without a valid license
 * is a violation of the license agreement and will be prosecuted by
 * civil and criminal law.
 *
 * http://www.shopmodule.com
 *
 * @copyright (C) D3 Data Development (Inh. Thomas Dartsch)
 * @author    D3 Data Development - Daniel Seifert <support@shopmodule.com>
 * @link      http://www.oxidmodule.com
 */

use D3\Webauthn\Application\Model\WebauthnConf;

$sLangName = "English";

$aLang = [
    'charset'                                         => 'UTF-8',

    'D3_WEBAUTHN_ERROR_UNVALID'                       => 'The key used is invalid or cannot be checked.',
    'D3_WEBAUTHN_ERROR_MISSINGPKC'                    => 'No verifiable request options saved. Please perform the registration again or contact the operator.',
    'WEBAUTHN_INPUT_HELP'                             => 'Please authenticate with hardware key.',
    'WEBAUTHN_CANCEL_LOGIN'                           => 'Cancel login',
    'D3WEBAUTHN_CONF_BROWSER_REQUEST'                 => 'Please confirm the browser request:',
    'D3WEBAUTHN_CANCEL'                               => 'Cancel',
    'D3WEBAUTHN_DELETE'                               => 'Delete',
    'D3WEBAUTHN_DELETE_CONFIRM'                       => 'Do you really want to delete the key?',
    'D3WEBAUTHN_CANCELNOKEYREGISTERED'                => 'No key registered',

    'd3mxuser_webauthn'                               => 'hardware key',

    'D3_WEBAUTHN_REGISTERNEW'                         => 'create new registration',
    'D3_WEBAUTHN_ADDKEY'                              => 'add security key',
    'D3_WEBAUTHN_KEYNAME'                             => 'Key name',

    'D3_WEBAUTHN_REGISTEREDKEYS'                      => 'registered keys',

    'D3_WEBAUTHN_ERR_UNSECURECONNECTION'              => 'The use of security keys is only possible with local or secure connections (https).',
    'D3_WEBAUTHN_ERR_INVALIDSTATE_'.WebauthnConf::TYPE_CREATE   => 'The key from the token cannot be used or can no longer be used. It may have been stored in your account before.',
    'D3_WEBAUTHN_ERR_INVALIDSTATE_'.WebauthnConf::TYPE_GET      => 'The key cannot be validated.',
    'D3_WEBAUTHN_ERR_NOTALLOWED'                      => 'The request was not allowed by the browser or the platform. Possibly permissions are missing or the time has expired.',
    'D3_WEBAUTHN_ERR_ABORT'                           => 'The action was aborted by the browser or the platform.',
    'D3_WEBAUTHN_ERR_CONSTRAINT'                      => 'The action could not be performed by the authenticating device.',
    'D3_WEBAUTHN_ERR_NOTSUPPORTED'                    => 'The action is not supported.',
    'D3_WEBAUTHN_ERR_UNKNOWN'                         => 'The action was cancelled due to an unknown error.',
    'D3_WEBAUTHN_ERR_NOPUBKEYSUPPORT'                 => 'Unfortunately, your browser does not support the use of hardware keys.',
    'D3_WEBAUTHN_ERR_TECHNICALERROR'                  => 'A technical error occurred while checking the access data.',

    'D3_WEBAUTHN_ERR_LOGINPROHIBITED'                 => 'Unfortunately, logging in with a security key is currently not possible for technical reasons. Please use your password instead.',
];
