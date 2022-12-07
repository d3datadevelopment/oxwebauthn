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
use D3\Webauthn\Application\Model\Exceptions\WebauthnException;
use OxidEsales\Eshop\Application\Model\User;
use Webauthn\PublicKeyCredentialUserEntity;

class UserEntity extends PublicKeyCredentialUserEntity
{
    use IsMockable;

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

        $this->d3CallMockableFunction(
            [
                PublicKeyCredentialUserEntity::class,
                '__construct'
            ],
            [
                strtolower($user->getFieldData('oxusername')),
                $user->getId(),
                $user->getFieldData('oxfname') . ' ' . $user->getFieldData('oxlname')
            ]
        );
    }
}