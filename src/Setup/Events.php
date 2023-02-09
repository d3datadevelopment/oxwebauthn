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

namespace D3\Webauthn\Setup;

use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;

class Events
{
    /**
     * Execute action on activate event
     * @codeCoverageIgnore
     * @return void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public static function onActivate()
    {
        if (defined('OXID_PHP_UNIT')) {
            return;
        }

        $actions = oxNew(Actions::class);
        $actions->runModuleMigrations();
        $actions->regenerateViews();
        $actions->clearCache();
        $actions->seoUrl();
    }

    /**
     * @codeCoverageIgnore
     * @return void
     */
    public static function onDeactivate(): void
    {
    }
}
