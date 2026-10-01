<?php
declare(strict_types=1);

namespace Kewico\Import;

use Cake\Database\Connection;
use RuntimeException;

/**
 * Copies one table at a time from the old CakePHP 2 database into the new
 * schema, following the rules in LegacyTableMap.
 *
 * Works on raw rows (no ORM), keeps the primary keys, and never writes to
 * the legacy connection.
 */
class LegacyImporter
{
    private const READ_CHUNK = 1000;
    private const MAX_PLACEHOLDERS = 60000;
    private const LEGACY_PREFIX = 'kewico_legacy_';
    private const FINGERPRINT_PREFIX = 'kewico-copy:';

    private const NUMERIC_TYPES = ['tinyint', 'smallint', 'mediumint', 'int', 'bigint', 'decimal', 'float', 'double', 'bit'];
    private const DATE_TYPES = ['date', 'datetime', 'timestamp'];

    private Connection $source;
    private Connection $target;

    /**
     * @param \Cake\Database\Connection $source Old database (read only)
     * @param \Cake\Database\Connection $target New database
     */
    public function __construct(Connection $source, Connection $target)
    {
        $this->source = $source;
        $this->target = $target;
    }

    /**
     * Work out how the columns of one table are moved, without writing anything.
     *
     * @param string $table Target table name
     * @param array<string, mixed> $def Table definition from LegacyTableMap
     * @return array<string, mixed>
     */
    public function plan(string $table, array $def): array
    {
        $sourceTable = $def['source'] ?? $table;
        $mode = $def['mode'] ?? 'replace';
        $sourceCols = $this->columns($this->source, $sourceTable);
        $targetCols = $this->columns($this->target, $table);
        if (!$sourceCols) {
            throw new RuntimeException("Source table `{$sourceTable}` not found in the legacy database.");
        }
        if ($mode === 'copy') {
            // Kewico-only table: created with the old structure, copied as is.
            $targetExists = (bool)$targetCols;
            $targetCols = $sourceCols;
        } elseif (!$targetCols) {
            throw new RuntimeException("Target table `{$table}` not found. Run the migrations first.");
        }

        $rename = $def['rename'] ?? [];
        $defaults = $def['defaults'] ?? [];
        $fromSource = array_flip($rename);

        $copy = [];       // new column => old column
        $withDefault = [];
        $fallback = [];   // required new columns without an old value or map default
        $leftEmpty = [];  // new optional columns without an old value
        foreach ($targetCols as $name => $col) {
            $oldName = $fromSource[$name] ?? $name;
            if (isset($sourceCols[$oldName]) && !array_key_exists($name, $defaults)) {
                $copy[$name] = $oldName;
            } elseif (array_key_exists($name, $defaults)) {
                $withDefault[] = $name;
            } elseif ($col['required']) {
                $fallback[] = $name;
            } else {
                $leftEmpty[] = $name;
            }
        }

        $used = array_merge(array_values($copy), array_keys($rename));
        $oldOnly = array_values(array_diff(array_keys($sourceCols), $used));

        // Single-column primary key of the old table (usually `id`,
        // `log_id` for log_times). Needed for chunked reads and side tables.
        $keys = array_keys(array_filter($sourceCols, fn(array $c) => $c['primary']));
        $key = count($keys) === 1 ? $keys[0] : null;
        if ($key === null || !isset($targetCols[$key])) {
            $def['legacy'] = false;
        }
        if ($key === null && ($def['mode'] ?? 'replace') === 'merge') {
            throw new RuntimeException("`{$sourceTable}` has no single-column primary key, merge mode is not possible.");
        }

        return [
            'table' => $table,
            'source' => $sourceTable,
            'key' => $key,
            'sourceCols' => $sourceCols,
            'targetCols' => $targetCols,
            'copy' => $copy,
            'defaults' => $withDefault,
            'fallback' => $fallback,
            'leftEmpty' => $leftEmpty,
            'oldOnly' => $oldOnly,
            'legacy' => !empty($def['legacy']) && $oldOnly,
            'where' => $def['where'] ?? null,
            'mode' => $mode,
            'sourceRows' => (int)$this->source->execute("SELECT COUNT(*) FROM `{$sourceTable}`" . self::whereSql($def['where'] ?? null))->fetchColumn(0),
            'targetRows' => $mode === 'copy' && !$targetExists
                ? 0
                : (int)$this->target->execute("SELECT COUNT(*) FROM `{$table}`")->fetchColumn(0),
        ];
    }

    /**
     * Import one table.
     *
     * @param array<string, mixed> $plan Result of plan()
     * @param array<string, mixed> $def Table definition from LegacyTableMap
     * @param bool $truncate Empty the target table first
     * @return array{inserted: int, legacy: int}
     */
    public function run(array $plan, array $def, bool $truncate): array
    {
        $table = $plan['table'];
        $merge = $plan['mode'] === 'merge';
        if (!$merge && $plan['targetRows'] > 0 && !$truncate) {
            throw new RuntimeException("`{$table}` already has {$plan['targetRows']} rows. Use --truncate to replace them.");
        }

        $lookups = [];
        foreach ($def['lookups'] ?? [] as $name => $sql) {
            $lookups[$name] = [];
            foreach ($this->source->execute($sql)->fetchAll('assoc') as $row) {
                $lookups[$name][$row['id']] = $row;
            }
        }

        $this->target->execute('SET FOREIGN_KEY_CHECKS = 0');
        $this->target->execute("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION,NO_AUTO_VALUE_ON_ZERO'");

        $legacyTable = self::LEGACY_PREFIX . $table;
        $copy = $plan['mode'] === 'copy';
        if ($copy) {
            $this->createCopyTable($plan);
        } elseif ($merge) {
            // Keep the rows that came with the new version, replace only the
            // rows with the ids being imported.
            foreach (array_chunk($this->sourceIds($plan), 1000) as $ids) {
                $this->target->execute("DELETE FROM `{$table}` WHERE `{$plan['key']}` IN (" . implode(',', $ids) . ')');
            }
        } elseif ($truncate) {
            $this->target->execute("TRUNCATE TABLE `{$table}`");
        }
        if ($plan['legacy']) {
            $this->createLegacyTable($legacyTable, $plan);
        }

        $targetColumns = array_merge(array_keys($plan['copy']), $plan['defaults'], $plan['fallback']);
        $legacyColumns = array_merge([$plan['key']], $plan['oldOnly']);
        $inserted = 0;
        $legacyInserted = 0;

        $this->target->begin();
        try {
            foreach ($this->readSource($plan) as $rows) {
                $newRows = [];
                $legacyRows = [];
                foreach ($rows as $old) {
                    // Copy mode keeps every value exactly as it was, zero dates included.
                    $newRows[] = $copy ? $old : $this->mapRow($old, $plan, $def, $lookups, $targetColumns);
                    if ($plan['legacy']) {
                        $legacyRows[] = $this->legacyRow($old, $plan, $legacyColumns);
                    }
                }
                $inserted += $this->insert($table, $targetColumns, $newRows);
                if ($legacyRows) {
                    $legacyInserted += $this->insert($legacyTable, $legacyColumns, $legacyRows);
                }
            }
            $this->target->commit();
        } catch (\Throwable $e) {
            $this->target->rollback();
            throw $e;
        } finally {
            $this->target->execute('SET FOREIGN_KEY_CHECKS = 1');
        }

        return ['inserted' => $inserted, 'legacy' => $legacyInserted];
    }

    /**
     * Row counts after the import, for the verification report.
     *
     * @param array<string, mixed> $plan Result of plan()
     * @return array{source: int, target: int, legacy: int|null}
     */
    public function counts(array $plan): array
    {
        $legacyTable = self::LEGACY_PREFIX . $plan['table'];
        $legacy = null;
        if ($plan['legacy']) {
            $legacy = (int)$this->target->execute("SELECT COUNT(*) FROM `{$legacyTable}`")->fetchColumn(0);
        }

        $target = 0;
        if ($plan['mode'] === 'merge') {
            foreach (array_chunk($this->sourceIds($plan), 1000) as $ids) {
                $target += (int)$this->target->execute("SELECT COUNT(*) FROM `{$plan['table']}` WHERE `{$plan['key']}` IN (" . implode(',', $ids) . ')')->fetchColumn(0);
            }
        } else {
            $target = (int)$this->target->execute("SELECT COUNT(*) FROM `{$plan['table']}`")->fetchColumn(0);
        }

        return [
            'source' => (int)$this->source->execute("SELECT COUNT(*) FROM `{$plan['source']}`" . self::whereSql($plan['where']))->fetchColumn(0),
            'target' => $target,
            'legacy' => $legacy,
        ];
    }

    /**
     * @param array<string, mixed> $old Old row
     * @param array<string, mixed> $plan Result of plan()
     * @param array<string, mixed> $def Table definition
     * @param array<string, mixed> $lookups Preloaded lookups
     * @param array<string> $columns Target columns, in insert order
     * @return array<string, mixed>
     */
    private function mapRow(array $old, array $plan, array $def, array $lookups, array $columns): array
    {
        $new = [];
        foreach ($plan['copy'] as $newName => $oldName) {
            $new[$newName] = $old[$oldName];
        }
        foreach ($plan['defaults'] as $name) {
            $value = $def['defaults'][$name];
            $new[$name] = is_callable($value) ? $value($old) : $value;
        }
        foreach ($plan['fallback'] as $name) {
            $new[$name] = null;
        }
        if (isset($def['transform'])) {
            $new = $def['transform']($new, $old, $lookups);
        }
        foreach ($def['decode'] ?? [] as $name) {
            if (isset($new[$name]) && is_string($new[$name])) {
                $new[$name] = self::decodeEntities($new[$name]);
            }
        }

        $row = [];
        foreach ($columns as $name) {
            $row[$name] = $this->normalize($new[$name] ?? null, $plan['targetCols'][$name]);
        }

        return $row;
    }

    /**
     * @param array<string, mixed> $old Old row
     * @param array<string, mixed> $plan Result of plan()
     * @param array<string> $columns Legacy side-table columns
     * @return array<string, mixed>
     */
    private function legacyRow(array $old, array $plan, array $columns): array
    {
        $row = [];
        foreach ($columns as $name) {
            $col = $plan['sourceCols'][$name];
            $col['required'] = $name === $plan['key'];
            $col['nullable'] = $name !== $plan['key'];
            $row[$name] = $this->normalize($old[$name], $col);
        }

        return $row;
    }

    /**
     * Make an old value fit the target column: zero dates, empty numbers,
     * too-long strings, datetime into date.
     *
     * @param mixed $value Value
     * @param array<string, mixed> $col Column metadata
     * @return mixed
     */
    private function normalize($value, array $col)
    {
        $type = $col['type'];

        if (in_array($type, self::DATE_TYPES, true)) {
            if ($value === null || $value === '' || strpos((string)$value, '0000-00-00') === 0) {
                return $col['nullable'] ? null : gmdate($type === 'date' ? 'Y-m-d' : 'Y-m-d H:i:s');
            }

            return $type === 'date' ? substr((string)$value, 0, 10) : $value;
        }

        if (in_array($type, self::NUMERIC_TYPES, true)) {
            if ($value === null || $value === '') {
                return $col['nullable'] ? null : 0;
            }

            return $value;
        }

        if ($value === null) {
            return $col['nullable'] ? null : '';
        }
        if ($col['maxLength'] !== null && mb_strlen((string)$value) > $col['maxLength']) {
            return mb_substr((string)$value, 0, $col['maxLength']);
        }

        return $value;
    }

    /**
     * Read the source table in chunks, ordered by id when there is one.
     *
     * @param array<string, mixed> $plan Result of plan()
     * @return \Generator<array<array<string, mixed>>>
     */
    private function readSource(array $plan): \Generator
    {
        $table = $plan['source'];
        $filter = $plan['where'] ? " AND ({$plan['where']})" : '';
        $key = $plan['key'];
        if ($key !== null && in_array($plan['sourceCols'][$key]['type'], self::NUMERIC_TYPES, true)) {
            $lastId = PHP_INT_MIN;
            while (true) {
                $rows = $this->source->execute(
                    "SELECT * FROM `{$table}` WHERE `{$key}` > ?{$filter} ORDER BY `{$key}` LIMIT " . self::READ_CHUNK,
                    [$lastId]
                )->fetchAll('assoc');
                if (!$rows) {
                    return;
                }
                yield $rows;
                $lastId = (int)end($rows)[$key];
            }
        }

        $offset = 0;
        while (true) {
            $rows = $this->source->execute(
                "SELECT * FROM `{$table}`" . self::whereSql($plan['where']) . ' LIMIT ' . self::READ_CHUNK . " OFFSET {$offset}"
            )->fetchAll('assoc');
            if (!$rows) {
                return;
            }
            yield $rows;
            $offset += self::READ_CHUNK;
        }
    }

    /**
     * Multi-row insert, split so it stays under the placeholder limit.
     *
     * @param string $table Table
     * @param array<string> $columns Columns
     * @param array<array<string, mixed>> $rows Rows keyed by column
     * @return int Rows inserted
     */
    private function insert(string $table, array $columns, array $rows): int
    {
        $perBatch = max(1, min(500, intdiv(self::MAX_PLACEHOLDERS, max(1, count($columns)))));
        $columnSql = '`' . implode('`, `', $columns) . '`';
        $rowSql = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $count = 0;

        foreach (array_chunk($rows, $perBatch) as $batch) {
            $params = [];
            foreach ($batch as $row) {
                foreach ($columns as $name) {
                    $params[] = $row[$name];
                }
            }
            $sql = "INSERT INTO `{$table}` ({$columnSql}) VALUES " . implode(', ', array_fill(0, count($batch), $rowSql));
            $this->target->execute($sql, $params);
            $count += count($batch);
        }

        return $count;
    }

    /**
     * Ids of the source rows that will be imported.
     *
     * @param array<string, mixed> $plan Result of plan()
     * @return array<int>
     */
    private function sourceIds(array $plan): array
    {
        $rows = $this->source->execute("SELECT `{$plan['key']}` FROM `{$plan['source']}`" . self::whereSql($plan['where']))->fetchAll('num');

        return array_map(fn($row) => (int)$row[0], $rows);
    }

    /**
     * Plain text stored with HTML entities by the old system (`H&auml;rkingen`)
     * back to real characters (`Härkingen`), as the new version stores it.
     * Up to two passes, for the few values that were encoded twice.
     *
     * @param string $value Text
     * @return string
     */
    public static function decodeEntities(string $value): string
    {
        for ($pass = 0; $pass < 2 && preg_match('/&(#[0-9]+|#x[0-9a-f]+|[a-z][a-z0-9]*);/i', $value); $pass++) {
            $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return $value;
    }

    /**
     * @param string|null $where Filter from the table definition
     * @return string
     */
    private static function whereSql(?string $where): string
    {
        return $where ? " WHERE ({$where})" : '';
    }

    /**
     * (Re)create a Kewico-only table with the structure it has in the old
     * database, converted to InnoDB and utf8mb4 like the rest of the schema.
     *
     * @param array<string, mixed> $plan Result of plan()
     * @return void
     */
    private function createCopyTable(array $plan): void
    {
        // Once the ported Kewico code changes one of these tables (a new column
        // through a migration), re-copying it would silently rebuild it with
        // the old structure. Stop instead: that table then needs a normal
        // mapping (replace mode) in LegacyTableMap.
        // Changes in the OLD table (kewico_php8 is still maintained) are fine:
        // the copy is rebuilt with the new structure.
        $existing = $this->columns($this->target, $plan['table']);
        if ($existing && $this->changedInNewSystem($plan, $existing)) {
            throw new RuntimeException(
                "`{$plan['table']}` has changed in the new system since it was copied. "
                . 'Map it in LegacyTableMap instead of copying it.'
            );
        }

        $ddl = $this->source->execute("SHOW CREATE TABLE `{$plan['source']}`")->fetch('num')[1];
        $ddl = preg_replace('/^CREATE TABLE `[^`]+`/', "CREATE TABLE `{$plan['table']}`", $ddl);
        // Column-level character sets and collations: the table default below applies.
        $ddl = preg_replace('/ CHARACTER SET \w+/', '', $ddl);
        $ddl = preg_replace('/ COLLATE \w+/', '', $ddl);
        // Table options: InnoDB, utf8mb4, no stale AUTO_INCREMENT counter.
        $ddl = preg_replace('/\)\s*ENGINE=.*$/s', ')', $ddl);
        $ddl .= ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

        $this->target->execute("DROP TABLE IF EXISTS `{$plan['table']}`");
        $this->target->execute($ddl);

        // Remember the structure as copied, to recognise later changes made
        // in the new system (see changedInNewSystem()).
        $fingerprint = self::FINGERPRINT_PREFIX . self::structureHash($this->columns($this->target, $plan['table']));
        $this->target->execute("ALTER TABLE `{$plan['table']}` COMMENT = '{$fingerprint}'");
    }

    /**
     * Has a copied table been changed in the new system since it was copied?
     *
     * Tables copied with a fingerprint: changed when the structure no longer
     * matches it. Older copies without one: changed when they have a column
     * the old table does not have, or a column of a different type. Columns
     * only added to the old table (kewico_php8 is still maintained) do not
     * count.
     *
     * @param array<string, mixed> $plan Result of plan()
     * @param array<string, array<string, mixed>> $existing Columns of the copy
     * @return bool
     */
    private function changedInNewSystem(array $plan, array $existing): bool
    {
        $comment = (string)$this->target->execute(
            'SELECT TABLE_COMMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$plan['table']]
        )->fetchColumn(0);
        if (strpos($comment, self::FINGERPRINT_PREFIX) === 0) {
            return substr($comment, strlen(self::FINGERPRINT_PREFIX)) !== self::structureHash($existing);
        }

        foreach ($existing as $name => $col) {
            $old = $plan['sourceCols'][$name] ?? null;
            if ($old === null || $old['columnType'] !== $col['columnType'] || $old['nullable'] !== $col['nullable']) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, array<string, mixed>> $columns Column metadata
     * @return string
     */
    private static function structureHash(array $columns): string
    {
        $parts = [];
        foreach ($columns as $name => $col) {
            $parts[] = $name . ' ' . $col['columnType'] . ($col['nullable'] ? ' NULL' : ' NOT NULL');
        }

        return md5(implode(',', $parts));
    }

    /**
     * (Re)create the side table for old-only columns, keyed by the same id.
     *
     * @param string $legacyTable Side table name
     * @param array<string, mixed> $plan Result of plan()
     * @return void
     */
    private function createLegacyTable(string $legacyTable, array $plan): void
    {
        $key = $plan['key'];
        $defs = ["`{$key}` {$plan['sourceCols'][$key]['columnType']} NOT NULL"];
        foreach ($plan['oldOnly'] as $name) {
            $defs[] = "`{$name}` {$plan['sourceCols'][$name]['columnType']} NULL";
        }
        $defs[] = "PRIMARY KEY (`{$key}`)";

        $this->target->execute("DROP TABLE IF EXISTS `{$legacyTable}`");
        $this->target->execute(
            "CREATE TABLE `{$legacyTable}` (" . implode(', ', $defs) . ') '
            . 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci '
            . "COMMENT='Kewico: old-only columns of {$plan['source']} from kewico_php8'"
        );
    }

    /**
     * Column metadata of a table.
     *
     * @param \Cake\Database\Connection $connection Connection
     * @param string $table Table
     * @return array<string, array<string, mixed>>
     */
    private function columns(Connection $connection, string $table): array
    {
        $rows = $connection->execute(
            'SELECT COLUMN_NAME AS name, DATA_TYPE AS type, COLUMN_TYPE AS columnType, IS_NULLABLE AS nullable,
                    COLUMN_DEFAULT AS dflt, EXTRA AS extra, CHARACTER_MAXIMUM_LENGTH AS maxLength, COLUMN_KEY AS colKey
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
             ORDER BY ORDINAL_POSITION',
            [$table]
        )->fetchAll('assoc');

        $columns = [];
        foreach ($rows as $row) {
            // Generated columns are calculated by MySQL and cannot be written.
            // Not to be confused with DEFAULT_GENERATED (a column with a
            // default such as CURRENT_TIMESTAMP), which must be copied.
            if (preg_match('/\b(VIRTUAL|STORED) GENERATED\b/i', (string)$row['extra'])) {
                continue;
            }
            $nullable = $row['nullable'] === 'YES';
            $columns[$row['name']] = [
                'type' => strtolower($row['type']),
                'columnType' => $row['columnType'],
                'nullable' => $nullable,
                'required' => !$nullable && $row['dflt'] === null && stripos((string)$row['extra'], 'auto_increment') === false,
                'maxLength' => $row['maxLength'] !== null ? (int)$row['maxLength'] : null,
                'primary' => $row['colKey'] === 'PRI',
            ];
        }

        return $columns;
    }
}
