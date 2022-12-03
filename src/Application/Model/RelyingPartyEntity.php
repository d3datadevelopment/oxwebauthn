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

use D3\TestingTools\Production\IsMockable;
use OxidEsales\Eshop\Application\Model\Shop;
use OxidEsales\Eshop\Core\Config;
use Webauthn\PublicKeyCredentialRpEntity;

class RelyingPartyEntity extends PublicKeyCredentialRpEntity
{
    use IsMockable;

    public function __construct()
    {
        $this->d3CallMockableParent(
            '__construct',
            [
                $this->getActiveShop()->getFieldData('oxname'),
                $this->getRPShopUrl()
            ]
        );
    }

    /**
     * @return bool
     */
    public function hasConfiguredShopUrl(): bool
    {
        return (bool) strlen(trim((string) $this->getConfiguredShopUrl()));
    }

    /**
     * @return mixed
     */
    public function getConfiguredShopUrl()
    {
        return $this->d3GetMockableRegistryObject(Config::class)->getConfigParam('d3webauthn_diffshopurl');
    }

    /**
     * @return string
     */
    public function getShopUrlByHost(): string
    {
        return preg_replace('/(^www\.)(.*)/mi', '$2', $_SERVER['HTTP_HOST']);
    }

    /**
     * @return string|null
     */
    public function getRPShopUrl(): ?string
    {
        return $this->hasConfiguredShopUrl() ?
            trim($this->getConfiguredShopUrl()) :
            $this->getShopUrlByHost();
    }

    /**
     * @return Shop
     */
    public function getActiveShop(): Shop
    {
        return $this->d3GetMockableRegistryObject(Config::class)->getActiveShop();
    }
}