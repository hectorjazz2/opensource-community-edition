<?php

declare(strict_types=1);

/**
 * Orangescrum Community Edition
 *
 * Copyright (c) 2026 Andolasoft Inc.
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License
 * for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Datasource\ConnectionManager;
use Exception;
use PDO;

/**
 * Copy an existing PostgreSQL Orangescrum database into MySQL.
 *
 * The MySQL schema is NOT created here: build it first with the regular
 * migrations so it is identical to a fresh install, then copy the data.
 *
 *   bin/cake migrations migrate
 *   bin/cake migrations migrate -p EmailTemplating
 *   bin/cake migrate_postgres_to_mysql --pg-password secret [--force]
 *
 * Every table in the PostgreSQL `public` schema is copied into the table of the
 * same name on the target connection (default: `default`), then row counts are
 * compared. The whole copy runs in one transaction, so a failure changes
 * nothing. Target tables are emptied first; the phinxlog tables are replaced
 * too, so the migration history matches the source. AUTO_INCREMENT counters
 * advance to MAX(id)+1 on their own when explicit ids are inserted.
 */
class MigratePostgresToMysqlCommand extends Command
{
    protected const BATCH_SIZE = 500;

    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);
        $parser
            ->setDescription('Copy all data from a PostgreSQL Orangescrum database into the MySQL connection.')
            ->addOption('pg-host', ['default' => env('PG_HOST', 'localhost')])
            ->addOption('pg-port', ['default' => env('PG_PORT', '5432')])
            ->addOption('pg-database', ['default' => env('PG_DATABASE', 'orangescrum')])
            ->addOption('pg-username', ['default' => env('PG_USERNAME', 'postgres')])
            ->addOption('pg-password', ['default' => env('PG_PASSWORD', '')])
            ->addOption('pg-schema', ['default' => 'public'])
            ->addOption('connection', ['help' => 'Target MySQL connection', 'default' => 'default'])
            ->addOption('force', [
                'help' => 'Overwrite target tables that already contain data',
                'boolean' => true,
                'default' => false,
            ]);

        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        try {
            $pg = new PDO(
                sprintf(
                    'pgsql:host=%s;port=%s;dbname=%s',
                    $args->getOption('pg-host'),
                    $args->getOption('pg-port'),
                    $args->getOption('pg-database')
                ),
                (string)$args->getOption('pg-username'),
                (string)$args->getOption('pg-password'),
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $my = $this->targetPdo((string)$args->getOption('connection'));
        } catch (Exception $e) {
            $io->error('Connection failed: ' . $e->getMessage());

            return Command::CODE_ERROR;
        }
        $schema = (string)$args->getOption('pg-schema');

        $sourceTables = $this->sourceColumns($pg, $schema);
        $targetTables = $this->targetColumns($my);

        // Refuse to start if the target schema cannot hold the source data.
        $problems = [];
        foreach ($sourceTables as $table => $columns) {
            if (!isset($targetTables[$table])) {
                $problems[] = "table `{$table}` does not exist in MySQL";
                continue;
            }
            foreach (array_diff(array_keys($columns), array_keys($targetTables[$table])) as $column) {
                $problems[] = "column `{$table}`.`{$column}` does not exist in MySQL";
            }
        }
        if ($problems) {
            $io->error('Target schema does not match the source. Run the migrations first.');
            foreach ($problems as $problem) {
                $io->err('  - ' . $problem);
            }

            return Command::CODE_ERROR;
        }

        if (!$args->getOption('force')) {
            foreach (array_keys($sourceTables) as $table) {
                if (str_ends_with($table, 'phinxlog')) {
                    continue;
                }
                if ((int)$my->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn() > 0) {
                    $io->error("Target table `{$table}` already contains data. Re-run with --force to overwrite.");

                    return Command::CODE_ERROR;
                }
            }
        }

        $my->exec('SET FOREIGN_KEY_CHECKS = 0');
        // Keep explicit id 0 rows as 0 instead of treating them as "next id".
        $my->exec("SET SESSION sql_mode = CONCAT_WS(',', NULLIF(@@sql_mode, ''), 'NO_AUTO_VALUE_ON_ZERO')");
        $mismatches = 0;
        // One transaction for the whole copy (DELETE, not TRUNCATE, so it can be
        // rolled back): a failure part-way leaves the target exactly as it was.
        $my->beginTransaction();
        $table = null;
        try {
            foreach ($sourceTables as $table => $columns) {
                $copied = $this->copyTable($pg, $my, $schema, $table, $columns);
                $source = (int)$pg->query('SELECT COUNT(*) FROM ' . $this->pgName($schema, $table))->fetchColumn();
                $target = (int)$my->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
                if ($source === $target) {
                    $io->verbose(sprintf('  %-40s %8d rows', $table, $copied));
                } else {
                    $mismatches++;
                    $io->err(sprintf('  %-40s source %d / target %d rows', $table, $source, $target));
                }
            }
            if ($mismatches > 0) {
                $my->rollBack();
                $io->error("{$mismatches} table(s) have mismatched row counts; nothing was written.");

                return Command::CODE_ERROR;
            }
            $my->commit();
        } catch (Exception $e) {
            $my->rollBack();
            $io->error("Copy failed on table `{$table}`; nothing was written. " . $e->getMessage());
            if (str_contains($e->getMessage(), '1062')) {
                // PostgreSQL compares text case-sensitively; MySQL's utf8mb4_unicode_ci
                // does not, so e.g. "Bob@x.com" and "bob@x.com" collide on a unique key.
                $io->err('The source has values that differ only in letter case or trailing spaces '
                    . 'in a unique column. Merge or rename them in PostgreSQL, then re-run.');
            }

            return Command::CODE_ERROR;
        } finally {
            $my->exec('SET FOREIGN_KEY_CHECKS = 1');
        }

        // Cached ORM metadata may describe the old database.
        try {
            (new \Cake\Database\SchemaCache(ConnectionManager::get((string)$args->getOption('connection'))))->clear();
        } catch (Exception $e) {
            $io->warning('Could not clear the schema cache — run: bin/cake schema_cache clear');
        }

        $io->success(sprintf('Copied %d tables from PostgreSQL to MySQL; all row counts match.', count($sourceTables)));

        return Command::CODE_SUCCESS;
    }

    /**
     * @return array<string, array<string, string>> table => [column => data_type]
     */
    protected function sourceColumns(PDO $pg, string $schema): array
    {
        $stmt = $pg->prepare(
            'SELECT c.table_name, c.column_name, c.data_type
               FROM information_schema.columns c
               JOIN information_schema.tables t
                 ON t.table_schema = c.table_schema AND t.table_name = c.table_name
              WHERE c.table_schema = ? AND t.table_type = \'BASE TABLE\'
              ORDER BY c.table_name, c.ordinal_position'
        );
        $stmt->execute([$schema]);
        $tables = [];
        foreach ($stmt->fetchAll(PDO::FETCH_NUM) as [$table, $column, $type]) {
            $tables[$table][$column] = $type;
        }

        return $tables;
    }

    /**
     * @return array<string, array<string, string>> table => [column => data_type]
     */
    protected function targetColumns(PDO $my): array
    {
        $tables = [];
        $rows = $my->query(
            'SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME, ORDINAL_POSITION'
        )->fetchAll(PDO::FETCH_NUM);
        foreach ($rows as [$table, $column, $type]) {
            $tables[$table][$column] = $type;
        }

        return $tables;
    }

    /**
     * @param array<string, string> $columns source column => type
     */
    protected function copyTable(PDO $pg, PDO $my, string $schema, string $table, array $columns): int
    {
        $names = array_keys($columns);
        $my->exec("DELETE FROM `{$table}`");

        // Page by primary key; a table without one has no stable order, so it
        // is read in a single pass (OFFSET paging could skip or repeat rows).
        $pk = $this->primaryKey($pg, $schema, $table);
        $select = 'SELECT ' . implode(', ', array_map(fn($c) => '"' . $c . '"', $names))
            . ' FROM ' . $this->pgName($schema, $table);
        $batchSize = PHP_INT_MAX;
        if ($pk) {
            $batchSize = self::BATCH_SIZE;
            $select .= ' ORDER BY ' . implode(', ', array_map(fn($c) => '"' . $c . '"', $pk))
                . ' LIMIT ' . $batchSize . ' OFFSET ?';
        }
        $columnList = implode(', ', array_map(fn($c) => "`{$c}`", $names));
        $rowPlaceholder = '(' . implode(', ', array_fill(0, count($names), '?')) . ')';

        $copied = 0;
        $read = $pg->prepare($select);
        while (true) {
            $read->execute($pk ? [$copied] : []);
            $rows = $read->fetchAll(PDO::FETCH_NUM);
            if (!$rows) {
                break;
            }
            foreach (array_chunk($rows, self::BATCH_SIZE) as $chunk) {
                $values = [];
                foreach ($chunk as $row) {
                    foreach ($row as $i => $value) {
                        $values[] = $this->convertValue($value, $columns[$names[$i]]);
                    }
                }
                $my->prepare(
                    "INSERT INTO `{$table}` ({$columnList}) VALUES "
                    . implode(', ', array_fill(0, count($chunk), $rowPlaceholder))
                )->execute($values);
            }
            $copied += count($rows);
            if (count($rows) < $batchSize) {
                break;
            }
        }

        return $copied;
    }

    /**
     * Normalise a PostgreSQL value for binding into MySQL.
     */
    protected function convertValue(mixed $value, string $sourceType): mixed
    {
        if ($value === null) {
            return null;
        }
        if (is_resource($value)) {
            return stream_get_contents($value);
        }
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }
        if ($sourceType === 'boolean') {
            return in_array($value, ['t', 'true', '1', 1], true) ? 1 : 0;
        }
        if (str_starts_with($sourceType, 'timestamp') && is_string($value)) {
            // Drop any "+00" style offset; MySQL DATETIME has no time zone.
            return preg_replace('/([+-]\d{2}(:?\d{2})?)$/', '', $value);
        }

        return $value;
    }

    /**
     * @return array<string>
     */
    protected function primaryKey(PDO $pg, string $schema, string $table): array
    {
        $stmt = $pg->prepare(
            'SELECT a.attname
               FROM pg_index i
               JOIN pg_attribute a ON a.attrelid = i.indrelid AND a.attnum = ANY(i.indkey)
              WHERE i.indrelid = ?::regclass AND i.indisprimary'
        );
        $stmt->execute([$this->pgName($schema, $table)]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    protected function pgName(string $schema, string $table): string
    {
        return '"' . str_replace('"', '""', $schema) . '"."' . str_replace('"', '""', $table) . '"';
    }

    protected function targetPdo(string $connectionName): PDO
    {
        $config = ConnectionManager::getConfig($connectionName);
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config['host'] ?? 'localhost',
            $config['port'] ?? '3306',
            $config['database']
        );

        return new PDO($dsn, $config['username'] ?? 'root', $config['password'] ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }
}
