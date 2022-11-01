<?php

namespace D3\Webauthn\Application\Model;

use OxidEsales\Eshop\Core\Registry;

class WebauthnErrors
{
    public const INVALIDSTATE       = 'invalidstateerror';
    public const NOTALLWED          = 'notallowederror';
    public const ABORT              = 'aborterror';
    public const CONSTRAINT         = 'constrainterror';
    public const NOTSUPPORTED       = 'notsupporederror';
    public const UNKNOWN            = 'unknownerror';
    public const NOPUBKEYSUPPORT    = 'd3nopublickeycredentialsupportederror';

    /**
     * @param $msg
     * @param null $type
     * @return string
     */
    public function translateError($msg, $type = null): string
    {
        $lang = Registry::getLang();
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

        return $lang->translateString('D3_WEBAUTHN_ERR_TECHNICALERROR', null, true);
    }

    /**
     * @param string $msg
     * @return string
     */
    public function getErrIdFromMessage(string $msg): string
    {
        return trim(strtolower(substr($msg, 0, strpos($msg, ':'))));
    }
}