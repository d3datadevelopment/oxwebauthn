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

namespace D3\Webauthn\tests;

use D3\ModCfg\Tests\additional_abstract;
use OxidEsales\Eshop\Core\Exception\StandardException;

include(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'd3webauthn_config.php');

class additional extends additional_abstract
{
    /**
     * additional constructor.
     * @throws StandardException
     */
    public function __construct()
    {
        if (D3WEBAUTHN_REQUIRE_MODCFG) {
            $this->reactivateModCfg();
        }
    }
}

oxNew(additional::class);
