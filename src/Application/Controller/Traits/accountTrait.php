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

namespace D3\Webauthn\Application\Controller\Traits;

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