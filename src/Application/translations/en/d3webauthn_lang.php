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

$sLangName = 'English';

// -------------------------------
// RESOURCE IDENTIFIER = STRING
// -------------------------------
$aLang = [
    'charset'                              => 'UTF-8',

    'PAGE_TITLE_D3WEBAUTHNLOGIN'           => 'Passwordless login',
    'D3_WEBAUTHN_ACCOUNT'                  => 'My login keys',
    'PAGE_TITLE_D3_ACCOUNT_WEBAUTHN'       => 'My login keys',
    'D3_WEBAUTHN_ACCOUNT_DESC'             => 'Manage your login keys here.',
    'D3_WEBAUTHN_ACC_REGISTERNEW'          => 'create new registration',
    'D3_WEBAUTHN_ACC_ADDKEY'               => 'add login key',

    'D3_WEBAUTHN_ACC_REGISTEREDKEYS'       => 'registered login keys',

    'WEBAUTHN_INPUT_HELP'                  => 'Please authenticate with login key.',
    'WEBAUTHN_CANCEL_LOGIN'                => 'Cancel login',
    'D3_WEBAUTHN_BREADCRUMB'               => 'Passwordless login',
    'D3_WEBAUTHN_CONFIRMATION'             => 'Confirmation required',
    'D3_WEBAUTHN_CONF_BROWSER_REQUEST'     => 'Please confirm the browser request.',
    'D3_WEBAUTHN_CANCEL'                   => 'cancel',
    'D3_WEBAUTHN_DELETE'                   => 'delete',
    'D3_WEBAUTHN_DELETE_CONFIRM'           => 'Do you really want to delete the key?',
    'D3_WEBAUTHN_KEYNAME'                  => 'name of the key',
    'D3_WEBAUTHN_NOKEYREGISTERED'          => 'no login key registered',

    'D3_WEBAUTHN_ACCOUNT_TYPE0'            => 'password only',
    'D3_WEBAUTHN_ACCOUNT_TYPE1'            => 'auth keys only',
    'D3_WEBAUTHN_ACCOUNT_TYPE2'            => 'auth keys only, password as an alternative',
    'D3_WEBAUTHN_ACCOUNT_TYPE3'            => 'auth key and password combined',

    'D3_WEBAUTHN_ERR_UNSECURECONNECTION'   => 'The use of login keys is only possible with local or secured connections (https).',
    'D3_WEBAUTHN_ERR_LOGINPROHIBITED'      => 'Unfortunately, logging in with a login key is currently not possible for technical reasons. Please use your password instead.',
    'D3_WEBAUTHN_ERR_NOTLOADEDUSER'        => "Cannot obtain login data from unloaded customer account.",
    'D3_WEBAUTHN_ERR_NOTCREDENTIALNOTSAVEABLE' => "The login key cannot be registered for technical reasons. Please contact the shop operator.",
];
