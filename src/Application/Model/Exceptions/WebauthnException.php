<?php

/**
 * This Software is the property of Data Development and is protected
 * by copyright law - it is NOT Freeware.
 * Any unauthorized use of this software without a valid license
 * is a violation of the license agreement and will be prosecuted by
 * civil and criminal law.
 * http://www.shopmodule.com
 *
 * @copyright (C) D3 Data Development (Inh. Thomas Dartsch)
 * @author        D3 Data Development - Daniel Seifert <support@shopmodule.com>
 * @link          http://www.oxidmodule.com
 */

declare(strict_types=1);

namespace D3\Webauthn\Application\Model\Exceptions;

use D3\Webauthn\Application\Model\WebauthnErrors;
use Exception;
use OxidEsales\Eshop\Core\Exception\StandardException;

class WebauthnException extends StandardException
{
    public $detailedErrorMessage = null;

    public function __construct( $sMessage = "not set", $iCode = 0, Exception $previous = null )
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
        $this->detailedErrorMessage = 'Webauthn: '.$detailedErrorMessage;
    }
}