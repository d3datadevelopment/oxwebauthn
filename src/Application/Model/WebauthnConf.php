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

class WebauthnConf
{
    public const OXID_ADMIN_AUTH       = 'auth';
    public const OXID_FRONTEND_AUTH    = 'usr';

    public const WEBAUTHN_SESSION_AUTH              = 'd3webauthn_auth';     // has valid webauthn, user is logged in completly
    public const WEBAUTHN_LOGIN_OBJECT              = 'd3webauthn_loginobject';  // webauthn register options, required for credential check
    public const WEBAUTHN_SESSION_CURRENTUSER       = 'd3webauthn_currentUser'; // oxid assigned to user from entered username
    public const WEBAUTHN_SESSION_LOGINUSER         = 'd3webauthn_loginUser';   // username entered in login form
    public const WEBAUTHN_SESSION_CURRENTCLASS      = 'd3webauthn_currentClass';    // no usage

    public const WEBAUTHN_ADMIN_SESSION_AUTH        = 'd3webauthn_be_auth';     // has valid webauthn, user is logged in completly
    public const WEBAUTHN_ADMIN_LOGIN_OBJECT        = 'd3webauthn_be_loginobject';  // webauthn register options, required for credential check
    public const WEBAUTHN_ADMIN_SESSION_CURRENTUSER = 'd3webauthn_be_currentUser'; // oxid assigned to user from entered username
    public const WEBAUTHN_ADMIN_SESSION_LOGINUSER   = 'd3webauthn_be_loginUser';   // username entered in login form
    public const WEBAUTHN_ADMIN_SESSION_CURRENTCLASS= 'd3webauthn_be_currentClass';    // no usage

    public const WEBAUTHN_SESSION_NAVFORMPARAMS     = 'd3webauthn_navFormParams';   // no usage
    public const WEBAUTHN_SESSION_NAVPARAMS         = 'd3webauthn_navigationParams';   // no usage

    public const GLOBAL_SWITCH                      = 'd3webauthn_disabledGlobally';

    public const TYPE_CREATE                        = 'TYPECREATE';
    public const TYPE_GET                           = 'TYPEGET';
}