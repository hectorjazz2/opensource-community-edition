<?php
declare(strict_types=1);

namespace Kewico\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Datasource\ConnectionManager;
use Kewico\Import\LegacyImporter;
use Kewico\Import\LegacyTableMap;

/**
 * Import data from the old CakePHP 2 database (kewico_php8) into this system.
 *
 *   bin/cake kewico import_legacy --dry-run
 *   bin/cake kewico import_legacy --truncate
 *   bin/cake kewico import_legacy --tables users,company_users --truncate
 */
class ImportLegacyCommand extends Command
{
    /**
     * @return string
     */
    public static function defaultName(): string
    {
        return 'kewico import_legacy';
    }

    /**
     * @param \Cake\Console\ConsoleOptionParser $parser Parser
     * @return \Cake\Console\ConsoleOptionParser
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return $parser
            ->setDescription('Import data from the old Kewico system (kewico_php8) through the `legacy` connection.')
            ->addOption('tables', [
                'help' => 'Comma-separated tables or groups (' . implode(', ', array_keys(LegacyTableMap::GROUPS)) . ').',
                'default' => 'accounts',
            ])
            ->addOption('dry-run', [
                'help' => 'Show the column plan and row counts, write nothing.',
                'boolean' => true,
            ])
            ->addOption('truncate', [
                'help' => 'Empty each target table before importing into it.',
                'boolean' => true,
            ])
            ->addOption('source', [
                'help' => 'Connection of the old database.',
                'default' => 'legacy',
            ])
            ->addOption('target', [
                'help' => 'Connection of the new database.',
                'default' => 'default',
            ]);
    }

    /**
     * @param \Cake\Console\Arguments $args Arguments
     * @param \Cake\Console\ConsoleIo $io Console IO
     * @return int
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $source = ConnectionManager::get((string)$args->getOption('source'));
        $target = ConnectionManager::get((string)$args->getOption('target'));
        $sourceDb = $source->config()['database'];
        $targetDb = $target->config()['database'];
        if ($sourceDb === $targetDb) {
            $io->error('Source and target are the same database. Check the `legacy` connection.');

            return static::CODE_ERROR;
        }

        $dryRun = (bool)$args->getOption('dry-run');
        $truncate = (bool)$args->getOption('truncate');
        $definitions = LegacyTableMap::tables();
        $tables = LegacyTableMap::resolve(explode(',', (string)$args->getOption('tables')));
        $importer = new LegacyImporter($source, $target);

        $io->out("<info>Kewico legacy import</info>  {$sourceDb}  ->  {$targetDb}" . ($dryRun ? '  (dry run)' : ''));
        $io->hr();

        $plans = [];
        foreach ($tables as $table) {
            $plan = $importer->plan($table, $definitions[$table]);
            $plans[$table] = $plan;
            $this->printPlan($io, $plan);
        }

        if ($dryRun) {
            $io->out('Dry run: nothing was written.');

            return static::CODE_SUCCESS;
        }

        $blocked = array_filter($plans, fn(array $p) => $p['targetRows'] > 0);
        if ($blocked && !$truncate) {
            $io->error('These target tables already have rows: ' . implode(', ', array_keys($blocked)) . '. Use --truncate to replace them.');

            return static::CODE_ERROR;
        }

        $failed = false;
        $io->hr();
        foreach ($plans as $table => $plan) {
            $started = microtime(true);
            $io->out("Importing <info>{$table}</info> ... ", 0);
            $result = $importer->run($plan, $definitions[$table], $truncate);
            $counts = $importer->counts($plan);
            $ok = $counts['source'] === $counts['target']
                && ($counts['legacy'] === null || $counts['legacy'] === $counts['source']);
            $failed = $failed || !$ok;

            $line = sprintf(
                '%d rows in %.1fs  (old %d / new %d%s)',
                $result['inserted'],
                microtime(true) - $started,
                $counts['source'],
                $counts['target'],
                $counts['legacy'] !== null ? " / kewico_legacy_{$table} {$counts['legacy']}" : ''
            );
            $io->out($ok ? "<success>OK</success>  {$line}" : "<error>COUNT MISMATCH</error>  {$line}");

            foreach ($definitions[$table]['report'] ?? [] as $title => $sql) {
                $rows = $target->execute($sql)->fetchAll('assoc');
                if ($rows) {
                    $io->warning("  {$title}:");
                    foreach ($rows as $row) {
                        $io->out('    ' . implode('  ', $row));
                    }
                }
            }
        }

        $io->hr();
        if ($failed) {
            $io->error('Some row counts do not match. Check the tables marked above.');

            return static::CODE_ERROR;
        }
        $io->success('Import finished, all row counts match.');

        return static::CODE_SUCCESS;
    }

    /**
     * @param \Cake\Console\ConsoleIo $io Console IO
     * @param array<string, mixed> $plan Plan from LegacyImporter::plan()
     * @return void
     */
    private function printPlan(ConsoleIo $io, array $plan): void
    {
        $io->out(sprintf(
            '<info>%s</info>  old rows: %d, rows now in target: %d',
            $plan['table'],
            $plan['sourceRows'],
            $plan['targetRows']
        ));
        if ($plan['where']) {
            $io->out("  <warning>only rows where:</warning> {$plan['where']}");
        }
        $renamed = array_filter($plan['copy'], fn($old, $new) => $old !== $new, ARRAY_FILTER_USE_BOTH);
        $io->out('  copied columns:   ' . count($plan['copy']));
        if ($renamed) {
            $io->out('  renamed:          ' . implode(', ', array_map(fn($new, $old) => "{$old} -> {$new}", array_keys($renamed), $renamed)));
        }
        if ($plan['defaults']) {
            $io->out('  mapped/defaults:  ' . implode(', ', $plan['defaults']));
        }
        if ($plan['fallback']) {
            $io->out('  <warning>required, filled with 0 / \'\' / now:</warning> ' . implode(', ', $plan['fallback']));
        }
        if ($plan['oldOnly']) {
            $where = $plan['legacy'] ? "kept in kewico_legacy_{$plan['table']}" : '<warning>dropped</warning>';
            $io->out("  old-only ({$where}): " . implode(', ', $plan['oldOnly']));
        }
    }
}
