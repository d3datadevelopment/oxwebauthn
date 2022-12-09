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
use OxidEsales\Eshop\Core\Language;

class WebauthnErrors
{
    use IsMockable;

    public const INVALIDSTATE       = 'invalidstateerror';
    public const NOTALLWED          = 'notallowederror';
    public const ABORT              = 'aborterror';
    public const CONSTRAINT         = 'constrainterror';
    public const NOTSUPPORTED       = 'notsupporederror';
    public const UNKNOWN            = 'unknownerror';
    public const NOPUBKEYSUPPORT    = 'd3nopublickeycredentialsupportederror';
    public const UNSECURECONNECTION = 'D3_WEBAUTHN_ERR_UNSECURECONNECTION';

    /**
     * @param $msg
     * @param null $type
     * @return string
     */
    public function translateError($msg, $type = null): string
    {
        $lang = $this->d3GetMockableRegistryObject(Language::class);
        $type = $type ? '_'.$type : null;

        switch ($this->getErrIdFromMessage($msg)) {
            case self::INVALIDSTATE:
                return $lang->translateString('D3_WEBAUTHN_ERR_INVALIDSTATE'.$type, null, true);
            case self::NOTALLWED:
                return $lang->translateString('D3_WEBAUTHN_ERR_NOTALLOWED', null, true);
            case self::ABORT:
                return $lang->translateString('D3_WEBAUTHN_ERR_ABORT', null, true);
            case self::CONSTRAINT:
                return $lang->translateString('D3_WEBAUTHN_ERR_CONSTRAINT', null, true);
            case self::NOTSUPPORTED:
                return $lang->translateString('D3_WEBAUTHN_ERR_NOTSUPPORTED', null, true);
            case self::UNKNOWN:
                return $lang->translateString('D3_WEBAUTHN_ERR_UNKNOWN', null, true);
            case self::NOPUBKEYSUPPORT:
                return $lang->translateString('D3_WEBAUTHN_ERR_NOPUBKEYSUPPORT', null, true);
        }

        switch (strtoupper($msg)) {
            case self::UNSECURECONNECTION:
                return $lang->translateString($msg);
        }

        return $lang->translateString('D3_WEBAUTHN_ERR_TECHNICALERROR', null, true);
    }

    /**
     * @param string $msg
     * @return string
     */
    public function getErrIdFromMessage(string $msg): string
    {
        if (is_int(strpos($msg, ':'))) {
            return trim( strtolower( substr( $msg, 0, strpos( $msg, ':' ) ) ) );
        }

        return trim(strtolower($msg));
    }
}