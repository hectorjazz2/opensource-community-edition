<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Squashed baseline schema for the Community Edition.
 *
 * This single migration replaces the original per-feature migration history
 * (Initial plus a chain of Add and Drop migrations that created then removed
 * the enterprise/legacy tables). It applies the exact schema they produced
 * (originally captured with `pg_dump --schema-only`, now converted to MySQL),
 * so a fresh install builds the final Community-Edition schema in one step
 * with no create-then-drop churn.
 *
 * The SQL lives beside this file (InitialOssBaseline.sql). Statements are run
 * one at a time: PDO MySQL only reports errors for the first statement of a
 * multi-statement batch, and MySQL DDL is not transactional, so a failure
 * stops at the offending statement. Drop the database to retry.
 */
class InitialOssBaseline extends AbstractMigration
{
    public function up(): void
    {
        $sql = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'InitialOssBaseline.sql');
        if ($sql === false || trim($sql) === '') {
            throw new \RuntimeException('InitialOssBaseline.sql is missing or empty.');
        }
        $sql = preg_replace('/^--.*$/m', '', $sql);
        foreach (preg_split('/;\s*\n/', $sql) as $statement) {
            if (trim($statement) !== '') {
                $this->execute($statement);
            }
        }
    }

    /**
     * Irreversible: this is the schema baseline. Roll back by dropping the
     * database, not the migration.
     */
    public function down(): void
    {
    }
}
