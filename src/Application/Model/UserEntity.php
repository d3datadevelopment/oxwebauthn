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

namespace D3\Webauthn\Application\Model;

use OxidEsales\Eshop\Application\Model\User;
use Webauthn\PublicKeyCredentialUserEntity;

class UserEntity extends publicKeyCredentialUserEntity
{
    /**
     * @param User $user
     * @throws WebauthnException
     */
    public function __construct(User $user)
    {
        if (!$user->isLoaded() || !$user->getId()) {
            throw oxNew(WebauthnException::class, 'can not create webauthn user entity from not loaded user');
        }

        parent::__construct(
            strtolower($user->getFieldData('oxusername')),
            $user->getId(),
            $user->getFieldData('oxfname') . ' ' . $user->getFieldData('oxlname')
        );
    }
}