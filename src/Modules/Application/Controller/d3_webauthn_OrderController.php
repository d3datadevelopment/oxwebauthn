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

namespace D3\Webauthn\Modules\Application\Controller;

use D3\Webauthn\Application\Controller\Traits\checkoutGetUserTrait;

class d3_webauthn_OrderController extends d3_webauthn_OrderController_parent
{
    use checkoutGetUserTrait;
}