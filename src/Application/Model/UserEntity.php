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

use D3\Webauthn\Application\Model\Exceptions\WebauthnException;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Registry;
use Webauthn\PublicKeyCredentialUserEntity;

class UserEntity extends PublicKeyCredentialUserEntity
{
    /**
     * @param User $user
     * @throws WebauthnException
     */
    public function __construct(User $user)
    {
        if (!$user->isLoaded() || !$user->getId()) {
            /** @var WebauthnException $e */
            $e = oxNew(WebauthnException::class, 'D3_WEBAUTHN_ERR_NOTLOADEDUSER');
            throw $e;
        }

        parent::__construct(
            strtolower($user->getFieldData('oxusername')),
            $user->getId(),
            $user->getFieldData('oxfname') . ' ' . $user->getFieldData('oxlname')
        );
    }
}