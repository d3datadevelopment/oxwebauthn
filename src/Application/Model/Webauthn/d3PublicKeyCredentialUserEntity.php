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

namespace D3\Webauthn\Application\Model\Webauthn;

use OxidEsales\Eshop\Application\Model\User;
use Webauthn\PublicKeyCredentialUserEntity;

class d3PublicKeyCredentialUserEntity extends publicKeyCredentialUserEntity
{
    public function __construct(User $user)
    {
        parent::__construct(
            strtolower($user->getFieldData('oxfname').'.'.$user->getFieldData('oxlname')),
            $user->getId(),
            $user->getFieldData('oxfname').', '.$user->getFieldData('oxlname')
        );
    }
}