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

use OxidEsales\Eshop\Core\Registry;
use Webauthn\PublicKeyCredentialRpEntity;

class RelyingPartyEntity extends PublicKeyCredentialRpEntity
{
    public function __construct()
    {
        $shopUrl = is_string(Registry::getConfig()->getConfigParam('d3webauthn_diffshopurl')) ?
            trim(Registry::getConfig()->getConfigParam('d3webauthn_diffshopurl')) :
            null;

        parent::__construct(
            Registry::getConfig()->getActiveShop()->getFieldData('oxname'),
            $shopUrl ?: preg_replace('/(^www\.)(.*)/mi', '$2', $_SERVER['HTTP_HOST'])
        );
    }
}