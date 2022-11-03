<?php

namespace D3\Webauthn\Application\Model\Exceptions;

use D3\Webauthn\Application\Model\WebauthnErrors;
use OxidEsales\Eshop\Core\Exception\StandardException;

class WebauthnException extends StandardException
{
    public $detailedErrorMessage = null;

    public function __construct( $sMessage = "not set", $iCode = 0, \Exception $previous = null )
    {
        $this->setDetailedErrorMessage($sMessage);

        parent::__construct(
            (oxNew(WebauthnErrors::class))->translateError($sMessage, $this->getRequestType()),
            $iCode,
            $previous
        );
    }

    /**
     * @return string|null
     */
    public function getRequestType(): ?string
    {
        return null;
    }


    /**
     * @return null|string
     */
    public function getDetailedErrorMessage(): ?string
    {
        return $this->detailedErrorMessage;
    }

    /**
     * @param string|null $detailedErrorMessage
     */
    public function setDetailedErrorMessage(string $detailedErrorMessage = null): void
    {
        $this->detailedErrorMessage = $detailedErrorMessage;
    }
}