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

namespace D3\Webauthn\Application\Controller\Traits;

/** workaround for missing tpl blocks (https://github.com/OXID-eSales/wave-theme/pull/124) */
trait accountTrait
{
    protected $d3WebauthnLoginTemplate = 'd3webauthnaccountlogin.tpl';

    public function __construct()
    {
        $this->addTplParam('oxLoginTpl', $this->_sThisLoginTemplate);
        $this->_sThisLoginTemplate = $this->d3WebauthnLoginTemplate;

        parent::__construct();
    }
}