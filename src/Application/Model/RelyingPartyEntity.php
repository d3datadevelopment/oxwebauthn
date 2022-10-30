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

declare(strict_types=1);

namespace D3\Webauthn\Application\Model;

use OxidEsales\Eshop\Core\Registry;
use Webauthn\PublicKeyCredentialRpEntity;

class RelyingPartyEntity extends PublicKeyCredentialRpEntity
{
    public function __construct()
    {
        parent::__construct(
            Registry::getConfig()->getActiveShop()->getFieldData('oxname'),
            preg_replace('/(^www\.)(.*)/mi', '$2', $_SERVER['HTTP_HOST'])
        );
    }
}