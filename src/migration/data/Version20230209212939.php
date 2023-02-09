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

namespace D3\Webauthn\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\StringType;
use Doctrine\Migrations\AbstractMigration;

final class Version20230209212939 extends AbstractMigration
{
    public const FIELDLENGTH_CREDID = 512;
    public const FIELDLENGTH_CREDENTIAL = 2000;

    public function getDescription() : string
    {
        return 'create credential database table';
    }

    public function up(Schema $schema) : void
    {
        $this->connection->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

        $table = !$schema->hasTable('d3wa_usercredentials') ?
            $schema->createTable('d3wa_usercredentials') :
            $schema->getTable('d3wa_usercredentials');

        if (!$table->hasColumn('OXID')) {
            $table->addColumn('OXID', (new StringType())->getName())
                ->setLength(32)
                ->setFixed(true)
                ->setNotnull(true);
        }

        if (!$table->hasColumn('OXUSERID')) {
            $table->addColumn('OXUSERID', (new StringType())->getName())
                ->setLength(32)
                ->setFixed(true)
                ->setNotnull(true);
        }

        if (!$table->hasColumn('OXSHOPID')) {
            $table->addColumn('OXSHOPID', (new IntegerType())->getName())
                ->setLength(11)
                ->setNotnull(true);
        }

        if (!$table->hasColumn('NAME')) {
            $table->addColumn('NAME', (new StringType())->getName())
                ->setLength(100)
                ->setFixed(false)
                ->setNotnull(true);
        }

        if (!$table->hasColumn('CREDENTIALID')) {
            $table->addColumn('CREDENTIALID', (new StringType())->getName())
                ->setLength(self::FIELDLENGTH_CREDID)
                ->setFixed(false)
                ->setNotnull(true);
        }

        if (!$table->hasColumn('CREDENTIAL')) {
            $table->addColumn('CREDENTIAL', (new StringType())->getName())
                ->setLength(self::FIELDLENGTH_CREDENTIAL)
                ->setFixed(false)
                ->setNotnull(true);
        }

        if (!$table->hasColumn('OXTIMESTAMP')) {
            $table->addColumn('OXTIMESTAMP', (new DateTimeType())->getName())
                ->setType(new DateTimeType())
                ->setNotnull(true)
                // can't set default value via default method
                ->setColumnDefinition('timestamp DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP');
        }

        if (!$table->hasPrimaryKey()) {
            $table->setPrimaryKey(['OXID']);
        }

        if (!$table->hasIndex('SHOPUSER_IDX')) {
            $table->addIndex(['OXUSERID', 'OXSHOPID'], 'SHOPUSER_IDX');
        }

        if (!$table->hasIndex('CREDENTIALID_IDX')) {
            $table->addIndex(['CREDENTIALID'], 'CREDENTIALID_IDX');
        }

        $table->setComment('WebAuthn Credentials');
    }

    public function down(Schema $schema) : void
    {
        if ($schema->hasTable('d3wa_usercredentials')) {
            $schema->dropTable('d3wa_usercredentials');
        }
    }
}
