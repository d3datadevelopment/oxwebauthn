<?php

/**
 * This Software is the property of Data Development and is protected
 * by copyright law - it is NOT Freeware.
 * Any unauthorized use of this software without a valid license
 * is a violation of the license agreement and will be prosecuted by
 * civil and criminal law.
 * http://www.shopmodule.com
 *
 * @copyright (C) D3 Data Development (Inh. Thomas Dartsch)
 * @author    D3 Data Development - Daniel Seifert <support@shopmodule.com>
 * @link      http://www.oxidmodule.com
 */

namespace D3\Webauthn\Application\Model;

class WebauthnConf
{
    const WEBAUTHN_SESSION_AUTH          = 'webauthn_auth';     // has valid webauthn, user is logged in completly
    const WEBAUTHN_LOGIN_OBJECT          = 'authnloginobject';  // webauthn register options, required for credential check
    const WEBAUTHN_SESSION_CURRENTUSER   = 'd3webauthnCurrentUser'; // oxid assigned to user from entered username
    const WEBAUTHN_SESSION_CURRENTCLASS  = 'd3webauthnCurrentClass';    // no usage
    const WEBAUTHN_SESSION_NAVFORMPARAMS = 'd3webauthnNavFormParams';   // no usage
}