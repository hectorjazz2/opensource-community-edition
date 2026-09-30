<?php
declare(strict_types=1);

namespace Kewico\Import;

use Cake\Utility\Text;

/**
 * How each table of the old CakePHP 2 database (kewico_php8) is moved into the
 * new schema.
 *
 * Per table:
 * - `source`   old table name (defaults to the key)
 * - `where`    SQL filter on the old table: rows left out on purpose
 * - `rename`   old column => new column
 * - `defaults` new column => value, or callable(array $old): mixed. Used when
 *              the new column has no counterpart in the old row. Every column
 *              that `transform` sets must be listed here.
 * - `lookups`  name => SQL on the legacy connection, loaded once as
 *              [id => row] and passed to `transform`
 * - `transform` callable(array $new, array $old, array $lookups): array,
 *              runs last on every row
 * - `report`   title => SQL on the new database, printed after the import
 * - `legacy`   true: old-only columns go to `kewico_legacy_<table>`
 *
 * Columns with the same name in both schemas are copied automatically.
 */
class LegacyTableMap
{
    /**
     * Named groups of tables, imported in this order.
     *
     * @var array<string, array<string>>
     */
    public const GROUPS = [
        'accounts' => ['companies', 'users', 'company_users', 'projects', 'project_users'],
    ];

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function tables(): array
    {
        return [
            'companies' => [
                'defaults' => [
                    'tenant_uuid' => fn(array $old) => Text::uuid(),
                    'user_last_login' => fn(array $old) => $old['modified'] ?? $old['created'] ?? gmdate('Y-m-d H:i:s'),
                ],
                'legacy' => true,
            ],

            'users' => [
                // Emails that are used by more than one user (case-insensitive).
                // The lowest id keeps the address, the others are marked.
                // Known case: users 28 and 74 (panoramablick@bluewin.ch), both
                // disabled in kewico_php8. Agreed to keep 74 as marked.
                'lookups' => [
                    'duplicates' => 'SELECT u.id FROM users u
                        JOIN (SELECT LOWER(TRIM(email)) AS e, MIN(id) AS keep_id FROM users
                              GROUP BY LOWER(TRIM(email)) HAVING COUNT(*) > 1) d
                          ON LOWER(TRIM(u.email)) = d.e AND u.id <> d.keep_id',
                ],
                'defaults' => [
                    'phone' => fn(array $old) => self::firstFilled($old, ['phone_number', 'contact_no']),
                ],
                'transform' => function (array $new, array $old, array $lookups): array {
                    // The new login lowercases the submitted email before the
                    // lookup, and the new schema has a unique key on email.
                    $email = strtolower(trim((string)$old['email']));
                    if (isset($lookups['duplicates'][$old['id']])) {
                        $email = 'duplicate' . $old['id'] . '.' . $email . '.invalid';
                    }
                    $new['email'] = $email;

                    // Pending invitations (isactive 2) cannot log in to the old
                    // system. The new login lets a pending user with a password
                    // in and activates them, so import them without one: they
                    // set a password by accepting the invitation.
                    if ((int)$old['isactive'] === 2) {
                        $new['password'] = null;
                    }

                    return $new;
                },
                'report' => [
                    'Duplicate emails marked (not deliverable, fix by hand if needed)' =>
                        "SELECT id, email FROM users WHERE email LIKE 'duplicate%.invalid'",
                    'Pending invitations imported without a password' =>
                        'SELECT COUNT(*) AS users FROM users WHERE isactive = 2 HAVING users > 0',
                ],
                'legacy' => true,
            ],

            'company_users' => [
                // A few memberships point to users that no longer exist
                // (e.g. 5, 6, 46, 212, 261 in case_data_9_30).
                'where' => 'user_id IN (SELECT id FROM users)',
                // Business unit and client flag were stored on the user in the
                // old system; the new schema keeps them per company membership.
                'lookups' => [
                    'users' => 'SELECT id, business_unit, is_client FROM users',
                ],
                'defaults' => [
                    'business_unit_id' => null,
                    'is_client' => 0,
                ],
                'transform' => function (array $new, array $old, array $lookups): array {
                    $user = $lookups['users'][$old['user_id']] ?? null;
                    if ($user !== null) {
                        $new['business_unit_id'] = $user['business_unit'] ?: null;
                        $new['is_client'] = (int)$user['is_client'];
                    }

                    return $new;
                },
                'legacy' => true,
            ],

            'projects' => [
                'legacy' => true,
            ],

            'project_users' => [
                // One leftover row from 2016 links user 0 to account 0.
                'where' => 'user_id > 0 AND project_id > 0',
                'legacy' => true,
            ],
        ];
    }

    /**
     * Resolve group names and table names to an ordered list of tables.
     *
     * @param array<string> $names Group or table names
     * @return array<string>
     */
    public static function resolve(array $names): array
    {
        $tables = self::tables();
        $result = [];
        foreach ($names as $name) {
            $name = trim($name);
            if (isset(self::GROUPS[$name])) {
                array_push($result, ...self::GROUPS[$name]);
            } elseif (isset($tables[$name])) {
                $result[] = $name;
            } else {
                throw new \InvalidArgumentException("Unknown table or group: {$name}");
            }
        }

        return array_values(array_unique($result));
    }

    /**
     * @param array<string, mixed> $row Old row
     * @param array<string> $columns Candidate columns, in order
     * @return string|null
     */
    private static function firstFilled(array $row, array $columns): ?string
    {
        foreach ($columns as $column) {
            $value = trim((string)($row[$column] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }
}
