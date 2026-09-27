<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\Infrastructure;

use Contao\CoreBundle\Doctrine\Schema\SchemaProvider;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;

/**
 * Contao's engine comparison also visits tables supplied by third-party schema
 * listeners. Unlike DCA tables, these may not declare a storage engine.
 */
final class ContaoSchemaProvider extends SchemaProvider
{
    public function __construct(
        private readonly SchemaProvider $inner,
        private readonly Connection $connection,
    ) {
    }

    public function createSchema(): Schema
    {
        $schema = $this->inner->createSchema();
        if (!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform) {
            return $schema;
        }

        foreach ($schema->getTables() as $table) {
            if (!str_starts_with($table->getName(), 'tl_')) {
                continue;
            }

            $engine = $table->hasOption('engine') ? $table->getOption('engine') : null;
            if (is_string($engine) && trim($engine) !== '') {
                continue;
            }

            // Preserve the actual engine. An unspecified target is not a request
            // to convert an existing table or drop its indexes.
            $status = $this->connection->fetchAssociative(
                'SHOW TABLE STATUS WHERE Name = ?',
                [$table->getName()],
            );
            $engine = $status['Engine'] ?? null;
            if (!is_string($engine) || trim($engine) === '') {
                $engine = $this->connection->getParams()['defaultTableOptions']['engine'] ?? null;
            }
            $table->addOption('engine', is_string($engine) && trim($engine) !== '' ? $engine : 'InnoDB');
        }

        return $schema;
    }
}
