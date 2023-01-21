<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * https://www.d3data.de
 *
 * @copyright (C) D3 Data Development (Inh. Thomas Dartsch)
 * @author    D3 Data Development - Daniel Seifert <support@shopmodule.com>
 * @link      https://www.oxidmodule.com
 */

declare(strict_types=1);

namespace D3\Webauthn\Modules;

use D3\TestingTools\Production\IsMockable;

class WebauthnServices extends WebauthnServices_parent
{
    use IsMockable;

    public function __construct()
    {
        $this->d3CallMockableFunction([WebauthnServices_parent::class, '__construct']);
        $this->addYamlDefinitions('d3/oxwebauthn/Config/services.yaml');
    }
}
