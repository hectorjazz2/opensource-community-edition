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
use Cake\Core\Plugin;
use Cake\Datasource\ConnectionManager;
use Exception;

/**
 * InitDatabase command.
 *
 * Initializes the database by:
 * 1. Running migrations
 * 2. Running seeders for base data (roles, etc.)
 */
class InitDatabaseCommand extends Command
{
    /**
     * Hook method for defining this command's option parser.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);

        $parser
            ->setDescription('Initialize the database with migrations and seed data.')
            ->addOption('skip-migrations', [
                'help' => 'Skip running migrations',
                'boolean' => true,
                'default' => false,
            ])
            ->addOption('skip-seeders', [
                'help' => 'Skip running seeders',
                'boolean' => true,
                'default' => false,
            ])
            ->addOption('seed', [
                'help' => 'Run seeders (requires confirmation unless -y is provided)',
                'boolean' => true,
                'default' => false,
            ])
            ->addOption('yes', [
                'short' => 'y',
                'help' => 'Automatically confirm all prompts',
                'boolean' => true,
                'default' => false,
            ])
            ->addOption('dry-run', [
                'help' => 'Show what would be executed without running anything',
                'boolean' => true,
                'default' => false,
            ])
            ->addOption('connection', [
                'help' => 'Database connection to use',
                'default' => 'default',
            ]);

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $connectionName = $args->getOption('connection');
        $skipMigrations = $args->getOption('skip-migrations');
        $skipSeeders = $args->getOption('skip-seeders');
        $runSeed = $args->getOption('seed');
        $autoConfirm = $args->getOption('yes');
        $dryRun = $args->getOption('dry-run');

        if ($dryRun) {
            $io->out('<info>DRY RUN MODE - No changes will be made</info>');
        }

        $io->out('<info>Starting database initialization...</info>');
        $io->hr();

        try {
            // Pre-flight checks
            if (!$this->validateEnvironment($connectionName, $io, $dryRun)) {
                return Command::CODE_ERROR;
            }

            // Step 1: Run migrations
            if (!$skipMigrations) {
                $io->out('<info>Step 1: Running migrations...</info>');
                if (!$dryRun) {
                    $exitCode = $this->runMigrations($io);
                    if ($exitCode !== 0) {
                        $io->warning('Migrations returned non-zero exit code, continuing...');
                    }
                } else {
                    $io->out('  [DRY RUN] Would run: bin/cake migrations migrate');
                    $this->showPluginMigrations($io);
                }
            } else {
                $io->out('<info>Step 1: Skipping migrations (--skip-migrations flag)</info>');
            }

            // Step 2: Run seeders (only if --seed flag is provided)
            if ($runSeed && !$skipSeeders) {
                $confirm = 'y';
                if (!$autoConfirm && !$dryRun) {
                    $confirm = $io->askChoice(
                        'Are you sure you want to run seeders? This will insert seed data into the database.',
                        ['y', 'n'],
                        'n'
                    );
                } elseif ($autoConfirm) {
                    $io->out('<info>Auto-confirming seeder execution (-y flag provided)</info>');
                } elseif ($dryRun) {
                    $io->out('<info>[DRY RUN] Would prompt for seeder confirmation</info>');
                }
                
                if ($confirm === 'y') {
                    if (!$dryRun) {
                        // Seeders insert explicit ids; AUTO_INCREMENT moves past them on its own.
                        $io->out('<info>Step 2: Running seeders...</info>');
                        $this->runSeeders($io);
                    } else {
                        $io->out('<info>Step 2: [DRY RUN] Would run seeders</info>');
                    }
                } else {
                    $io->out('<info>Step 2: Skipping seeders (user declined)</info>');
                }
            } elseif ($skipSeeders) {
                $io->out('<info>Step 2: Skipping seeders (--skip-seeders flag)</info>');
            } else {
                $io->out('<info>Step 2: Skipping seeders (use --seed flag to run seeders)</info>');
            }

            $io->hr();
            if ($dryRun) {
                $io->success('Database initialization preview completed (dry run)');
            } else {
                $io->success('Database initialization completed successfully!');
            }

            return Command::CODE_SUCCESS;
        } catch (Exception $e) {
            $io->error('Database initialization failed: ' . $e->getMessage());
            if ($io->level() >= ConsoleIo::VERBOSE) {
                $io->error($e->getTraceAsString());
            }
            return Command::CODE_ERROR;
        }
    }

    /**
     * Validate environment before running migrations
     *
     * @param string $connectionName
     * @param \Cake\Console\ConsoleIo $io
     * @param bool $dryRun
     * @return bool
     */
    protected function validateEnvironment(string $connectionName, ConsoleIo $io, bool $dryRun): bool
    {
        $io->out('<info>Running pre-flight checks...</info>');
        
        // Check database connection
        try {
            $connection = ConnectionManager::get($connectionName);
            if (!$dryRun) {
                // Test connection with a simple query instead of explicit connect()
                $connection->execute('SELECT 1')->fetchAll();
                $io->out('  ✓ Database connection successful');
            } else {
                $io->out('  [DRY RUN] Would check database connection');
            }
        } catch (Exception $e) {
            $io->error('  ✗ Database connection failed: ' . $e->getMessage());
            return false;
        }
        
        // Check if bin/cake.php exists
        if (!file_exists(ROOT . DS . 'bin' . DS . 'cake.php')) {
            $io->error('  ✗ bin/cake.php not found');
            return false;
        }
        $io->out('  ✓ CakePHP console found');
        
        // Check if migrations directory exists
        if (!is_dir(CONFIG . 'Migrations')) {
            $io->warning('  ⚠ Main migrations directory not found, will be created if needed');
        } else {
            $io->out('  ✓ Migrations directory exists');
        }
        
        $io->out('');
        return true;
    }

    /**
     * Show which plugin migrations would be run
     *
     * @param \Cake\Console\ConsoleIo $io
     * @return void
     */
    protected function showPluginMigrations(ConsoleIo $io): void
    {
        $plugins = Plugin::loaded();
        $plugins = array_filter($plugins, fn($plugin) => is_dir(ROOT . DS . 'plugins' . DS . $plugin));
        
        if (empty($plugins)) {
            $io->out('  [DRY RUN] No plugin migrations found');
            return;
        }
        
        foreach ($plugins as $plugin) {
            $migrationPath = ROOT . DS . 'plugins' . DS . $plugin . DS . 'config' . DS . 'Migrations';
            if (is_dir($migrationPath)) {
                $io->out("  [DRY RUN] Would run: bin/cake migrations migrate -p {$plugin}");
            }
        }
    }

    /**
     * Run CakePHP migrations
     *
     * @param \Cake\Console\ConsoleIo $io
     * @return int Exit code
     */
    protected function runMigrations(ConsoleIo $io): int
    {
        $exitCode = 0;

        // Run main app migrations
        $io->out('  Running: bin/cake migrations migrate');
        $exitCode = $this->runShellCommand('php ./bin/cake.php migrations migrate', $io);
        
        if ($exitCode !== 0) {
            $io->warning('  Main migrations completed with warnings');
        } else {
            $io->out('  <success>✓</success> Main migrations completed');
        }

        // Run plugin migrations (only plugins that have migrations)
        // Only include plugins that are in the plugins folder (not vendor)
        $plugins = Plugin::loaded();
        $plugins = array_filter($plugins, fn($plugin) => is_dir(ROOT . DS . 'plugins' . DS . $plugin));
        
        $pluginCount = 0;
        $pluginSuccess = 0;
        
        foreach ($plugins as $plugin) {
            $migrationPath = ROOT . DS . 'plugins' . DS . $plugin . DS . 'config' . DS . 'Migrations';
            
            // Skip if plugin doesn't have migrations directory
            if (!is_dir($migrationPath)) {
                continue;
            }
            
            $pluginCount++;
            $io->out("  Running: bin/cake migrations migrate -p {$plugin}");
            $pluginExitCode = $this->runShellCommand("php ./bin/cake.php migrations migrate -p {$plugin}", $io);
            
            if ($pluginExitCode !== 0) {
                $io->warning("  <warning>⚠</warning> Plugin {$plugin} migrations completed with warnings");
            } else {
                $io->out("  <success>✓</success> Plugin {$plugin} migrations completed");
                $pluginSuccess++;
            }
        }
        
        if ($pluginCount > 0) {
            $io->out('');
            $io->out("  Plugin migrations: {$pluginSuccess}/{$pluginCount} completed successfully");
        }

        return $exitCode;
    }

    /**
     * Execute a shell command and return the exit code
     *
     * @param string $command
     * @param \Cake\Console\ConsoleIo $io
     * @return int Exit code
     */
    protected function runShellCommand(string $command, ConsoleIo $io): int
    {
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open(
            $command,
            $descriptorSpec,
            $pipes,
            ROOT
        );

        if (!is_resource($process)) {
            $io->error('Failed to execute command: ' . $command);
            return 1;
        }

        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);

        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        // Show output with proper formatting
        if ($output) {
            $lines = explode("\n", trim($output));
            foreach ($lines as $line) {
                if (empty(trim($line))) continue;
                $io->out('    ' . $line);
            }
        }
        
        // Only show errors if exit code is non-zero
        if ($errors && $exitCode !== 0) {
            $io->warning('    ' . trim($errors));
        }

        return $exitCode;
    }

    /**
     * Run database seeders
     *
     * @param \Cake\Console\ConsoleIo $io
     * @return void
     */
    protected function runSeeders(ConsoleIo $io): void
    {
        $io->out('  Running: bin/cake migrations seed');

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open(
            'php ./bin/cake.php migrations seed',
            $descriptorSpec,
            $pipes,
            ROOT
        );

        if (!is_resource($process)) {
            $io->error('  Failed to start seeder process');
            throw new Exception('Failed to execute seeders');
        }

        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);

        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($output) {
            $lines = explode("\n", trim($output));
            foreach ($lines as $line) {
                if (empty(trim($line))) continue;
                $io->out('    ' . $line);
            }
        }
        
        if ($exitCode === 0) {
            $io->out('  <success>✓</success> All seeders completed successfully');
        } else {
            // Check if it's a duplicate key error (data already exists)
            if (strpos($errors, 'Duplicate entry') !== false || strpos($errors, '1062') !== false) {
                $io->out('  <info>✓</info> Seeders completed (existing data preserved)');
            } else {
                $io->warning('  Seeders completed with warnings');
                if (!empty(trim($errors))) {
                    $errorLines = explode("\n", trim($errors));
                    foreach ($errorLines as $line) {
                        if (empty(trim($line))) continue;
                        $io->warning('    ' . $line);
                    }
                }
            }
        }
    }
}
