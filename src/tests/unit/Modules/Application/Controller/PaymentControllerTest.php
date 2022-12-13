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

namespace D3\Webauthn\tests\unit\Modules\Application\Controller;

use OxidEsales\Eshop\Application\Controller\PaymentController;
use OxidEsales\TestingLibrary\UnitTestCase;

class PaymentControllerTest extends UnitTestCase
{
    use CheckoutTestTrait;

    protected $sutClass = PaymentController::class;
}
