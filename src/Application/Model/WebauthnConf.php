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
    public const WEBAUTHN_SESSION_AUTH          = 'webauthn_auth';     // has valid webauthn, user is logged in completly
    public const WEBAUTHN_LOGIN_OBJECT          = 'authnloginobject';  // webauthn register options, required for credential check
    public const WEBAUTHN_SESSION_CURRENTUSER   = 'd3webauthnCurrentUser'; // oxid assigned to user from entered username
    public const WEBAUTHN_SESSION_LOGINUSER     = 'd3webauthnLoginUser';   // username entered in login form
    public const WEBAUTHN_SESSION_CURRENTCLASS  = 'd3webauthnCurrentClass';    // no usage
    public const WEBAUTHN_SESSION_NAVFORMPARAMS = 'd3webauthnNavFormParams';   // no usage
    public const WEBAUTHN_SESSION_NAVPARAMS     = 'd3webauthnNavigationParams';   // no usage

    public const GLOBAL_SWITCH                  = 'blDisableWebauthnGlobally';

    public const TYPE_CREATE                    = 'TYPECREATE';
    public const TYPE_GET                       = 'TYPEGET';
}