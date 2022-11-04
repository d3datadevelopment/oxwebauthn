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
 * @author        D3 Data Development - Daniel Seifert <support@shopmodule.com>
 * @link          http://www.oxidmodule.com
 */

namespace D3\Webauthn\Modules\Application\Controller;

use D3\Webauthn\Application\Controller\Traits\accountTrait;

/** workaround for missing tpl blocks (https://github.com/OXID-eSales/wave-theme/pull/124) */
class d3_AccountController_Webauthn extends d3_AccountController_Webauthn_parent
{
    use accountTrait;
}