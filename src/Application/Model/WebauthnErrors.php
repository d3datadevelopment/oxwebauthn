<?php

namespace D3\Webauthn\Application\Model;

class WebauthnErrors
{
    public function translateError($msg)
    {
        switch ($msg) {
            case 'InvalidStateError: An attempt was made to use an object that is not, or is no longer, usable':
                return 'A key from this token is already saved';
        }

        return $msg;
    }
}