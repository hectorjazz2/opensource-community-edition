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
 * - `decode`   plain-text columns stored with HTML entities by the old
 *              system (`H&auml;rkingen`); decoded to real characters, as the
 *              new version stores typed text. Never for HTML columns
 *              (easycases.message is real HTML and stays as it is).
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
        'accounts' => ['companies', 'users', 'company_users', 'projects', 'project_users', 'roles'],
        // Group names must not be table names: `--tables projects` means the table.
        'cases' => ['status_groups', 'custom_statuses', 'types', 'type_companies', 'easycases'],
        'files' => ['case_files', 'log_times', 'labels', 'easycase_labels'],
        'userdata' => [
            'check_lists', 'search_filters', 'case_user_emails', 'case_user_views', 'case_recents',
            'user_notifications', 'user_invitations', 'invoice_customers', 'case_templates',
        ],
        'kewico' => self::COPY_TABLES,
        'all' => [
            'companies', 'users', 'company_users', 'projects', 'project_users', 'roles',
            'status_groups', 'custom_statuses', 'types', 'type_companies', 'easycases',
            'case_files', 'log_times', 'labels', 'easycase_labels',
            'check_lists', 'search_filters', 'case_user_emails', 'case_user_views', 'case_recents',
            'user_notifications', 'user_invitations', 'invoice_customers', 'case_templates',
            ...self::COPY_TABLES,
        ],
    ];

    /**
     * Tables that only exist in kewico_php8. Copied 1:1 with their old
     * structure (as InnoDB / utf8mb4) and the same names, so the Kewico code
     * ported later finds them where it expects them. `statuses` is not in the
     * list: it is converted into custom_statuses.
     *
     * When a later step changes one of these tables in the new system (e.g. a
     * migration adds a column), move it out of this list and give it a normal
     * entry in tables(). The import refuses to re-copy a changed table.
     *
     * @var array<string>
     */
    public const COPY_TABLES = [
        // AI Compliance Checker
        'ai_check_inputs', 'ai_check_logs', 'ai_report_files', 'ai_rule_categories', 'ai_rule_files',
        'ai_rule_history', 'ai_rule_sets', 'ai_rule_source_files', 'ai_rules',
        // Archicad translations
        'archicad_translation_attributes', 'archicad_translations',
        // Invoices and payments
        'invoices', 'invoice_activities', 'invoice_logs', 'invoice_settings', 'recurring_invoices',
        'payments', 'payment_activities', 'payment_logs', 'bank_infos', 'transactions',
        // Calendar: leaves, holidays, working hours, resources
        'user_leaves', 'user_holidays', 'company_holidays', 'work_hours', 'project_booked_resources',
        'overloads', 'utilization_filters',
        // Approvals: documents, assignments, time sheets
        'document_approvers', 'assign_approvers', 'timesheet_approvers', 'timesheet_logs',
        // Customers and CAD project fields
        'task_customers', 'project_task_customers', 'task_fields', 'project_fields', 'easycase_publish_formats',
        'business_units',
        // Files and folders, archive
        'folder_informations', 'folder_permissions', 'archives',
        // Templates
        'default_templates', 'default_project_templates', 'default_project_template_cases',
        'project_templates', 'project_template_cases', 'template_module_cases',
        // Roles, reports, dashboard, daily updates
        'user_roles', 'save_reports', 'dashboard_sorting_orders', 'daily_updates', 'dailyupdate_notifications',
        // Other
        'status_workflows', 'project_sub_types', 'user_technologies', 'mail_tbls',
        // Old SaaS tables, kept until confirmed unused
        'addons', 'subscriptions', 'user_subscriptions',
    ];

    /** Kewico's company id in the old system. */
    private const COMPANY_ID = 1;

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function tables(): array
    {
        return [
            'companies' => [
                'decode' => ['name'],
                'defaults' => [
                    'tenant_uuid' => fn(array $old) => Text::uuid(),
                    'user_last_login' => fn(array $old) => $old['modified'] ?? $old['created'] ?? gmdate('Y-m-d H:i:s'),
                ],
                'legacy' => true,
            ],

            'users' => [
                'decode' => ['name', 'last_name', 'short_name'],
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
                'decode' => ['name', 'short_name'],
                // Old status workflow (19 Salesplan, 20 Installation Plan,
                // 21 Plan Status) becomes the account's status group.
                'defaults' => [
                    'status_group_id' => fn(array $old) => (int)$old['workflow_id'],
                ],
                'legacy' => true,
            ],

            // Kewico's own roles (5 CAD Planer, 6 Sales, 7 Project Coordinator,
            // 8 Office, 9 Manager, 10 View Only, 12 Kewico_Coordinator).
            // company_users.role_id points at them. Roles 1-4 (Owner, Admin,
            // User, Client) are the same in both versions and are kept, like
            // the new version's own roles (e.g. 699 Guest).
            // Only the names: what each role may do (role_actions) is mapped
            // in step 4 (roles and permissions).
            'roles' => [
                'mode' => 'merge',
                'where' => 'id > 4',
                'decode' => ['role'],
            ],

            'project_users' => [
                // One leftover row from 2016 links user 0 to account 0.
                'where' => 'user_id > 0 AND project_id > 0',
                'legacy' => true,
            ],

            // Old status workflows -> new status groups (same ids). The new
            // version ships its own groups 1-6, which are kept.
            'status_groups' => [
                'source' => 'workflows',
                'mode' => 'merge',
                'decode' => ['name'],
                'defaults' => [
                    'parent_id' => 0,
                    'company_id' => self::COMPANY_ID,
                    'description' => '',
                    'created_by' => 0,
                    'is_default' => 0,
                    'created' => fn(array $old) => $old['dt_created'] ?? null,
                    'modified' => fn(array $old) => $old['dt_created'] ?? null,
                ],
            ],

            // Statuses of those workflows -> custom statuses (same ids, so
            // easycases.legend can point at them). Statuses without a workflow
            // are the standard base states (1, 2, 3, 5) or empty leftovers.
            'custom_statuses' => [
                'source' => 'statuses',
                'mode' => 'merge',
                'decode' => ['name'],
                'where' => 'workflow_id > 0',
                'defaults' => [
                    'company_id' => self::COMPANY_ID,
                    'status_group_id' => fn(array $old) => (int)$old['workflow_id'],
                    'progress' => fn(array $old) => (int)$old['percentage'],
                    'color' => fn(array $old) => ltrim((string)$old['color'], '#'),
                    'seq' => fn(array $old) => (int)$old['seq_order'],
                    'status_master_id' => fn(array $old) => self::statusMaster($old),
                    'created' => fn(array $old) => gmdate('Y-m-d H:i:s'),
                    'modified' => fn(array $old) => gmdate('Y-m-d H:i:s'),
                ],
            ],

            // Task types: 1-12 are the standard ones (same in both versions),
            // 13-18 are Kewico's (Astronaut Salesplan, Barn Design, ...).
            'types' => [
                'mode' => 'merge',
                'decode' => ['name'],
                'defaults' => [
                    'project_id' => 0,
                ],
            ],

            // Which types Kewico has switched on. Replaces the list the new
            // version created at install.
            'type_companies' => [
                'defaults' => [
                    'project_id' => 0,
                ],
            ],

            // Projects (istype 1) and their comments (istype 2).
            'easycases' => [
                // Title is plain text; message is real HTML and is not decoded.
                'decode' => ['title'],
                'lookups' => [
                    'projects' => 'SELECT id, company_id FROM projects',
                    'statuses' => 'SELECT id, name, percentage, seq_order FROM statuses WHERE workflow_id > 0',
                ],
                'defaults' => [
                    'company_id' => self::COMPANY_ID,
                    'custom_status_id' => 0,
                    // Number of replies, shown on the project. The old system
                    // kept it in case_count; comments themselves have 0.
                    'thread_count' => fn(array $old) => (int)$old['istype'] === 1 ? (int)$old['case_count'] : 0,
                ],
                'transform' => function (array $new, array $old, array $lookups): array {
                    $new['company_id'] = (int)($lookups['projects'][$old['project_id']]['company_id'] ?? self::COMPANY_ID);

                    // Old: legend holds the workflow status id (e.g. 89).
                    // New: custom_status_id holds it, legend the base state.
                    $status = $lookups['statuses'][$old['legend']] ?? null;
                    if ($status !== null) {
                        $new['custom_status_id'] = (int)$status['id'];
                        $new['legend'] = self::statusMaster($status);
                    }

                    return $new;
                },
                'legacy' => true,
            ],

            // File rows only. The files themselves stay in
            // app/webroot/files/case_files/ on the server and are copied to
            // webroot/files/case_files/ of the new system separately.
            // Kewico-only: is_internal (internal files) and file_status
            // (lock / draft / approved) go to kewico_legacy_case_files.
            'case_files' => [
                'lookups' => [
                    'cases' => 'SELECT id, dt_created FROM easycases WHERE id IN (SELECT easycase_id FROM case_files)',
                ],
                'defaults' => [
                    'created' => null,
                    'modified' => null,
                ],
                'transform' => function (array $new, array $old, array $lookups): array {
                    // The old table has no dates; use the date of its project or comment.
                    $created = $lookups['cases'][$old['easycase_id']]['dt_created'] ?? null;
                    $new['created'] = $created;
                    $new['modified'] = $created;

                    return $new;
                },
                'legacy' => true,
            ],

            // Time logs. Kewico-only: approver_id and pending_status (time
            // sheet approval) go to kewico_legacy_log_times.
            'log_times' => [
                'decode' => ['description'],
                'defaults' => [
                    'is_from_timer' => 0,
                ],
                // The description is shown as plain text. A few were saved by a
                // rich-text editor as <p>...</p>; keep the text only.
                'transform' => function (array $new, array $old, array $lookups): array {
                    if (is_string($new['description']) && preg_match('/<(p|br|div|span)\b/i', $new['description'])) {
                        $text = preg_replace('/<br\s*\/?>|<\/p>\s*<p[^>]*>/i', "
", $new['description']);
                        $new['description'] = trim(strip_tags($text));
                    }

                    return $new;
                },
                'legacy' => true,
            ],

            // Labels are company-wide in the old system (project_id 0).
            'labels' => [
                'decode' => ['lbl_title'],
                'defaults' => [
                    'project_id' => 0,
                ],
            ],

            'easycase_labels' => [],

            // Checklist items of a project.
            'check_lists' => [
                'decode' => ['title'],
                'rename' => [
                    'item_name' => 'title',
                    'is_check' => 'is_checked',
                ],
                'defaults' => [
                    'uniq_id' => fn(array $old) => md5('kewico-check-list-' . $old['id']),
                    'company_id' => self::COMPANY_ID,
                    // Items were listed by id; keep that order.
                    'sequence' => fn(array $old) => (int)$old['id'],
                ],
            ],

            'search_filters' => [],
            // Who gets emails for which project.
            'case_user_emails' => [],
            'case_user_views' => [],
            'case_recents' => [],

            // Notification settings. Kewico-only case_reminder and
            // assignment_case go to kewico_legacy_user_notifications.
            'user_notifications' => [
                'legacy' => true,
            ],

            // Invitation records. The old email links (qstr) do not work in
            // the new version, which uses invite_token: invitations that are
            // still open have to be sent again from the new system.
            'user_invitations' => [],

            // Invoice customers. Kewico-only invoice settings per customer
            // (VAT, note, invoice email and its text, language, street 2) go
            // to kewico_legacy_invoice_customers.
            'invoice_customers' => [
                'legacy' => true,
            ],

            'case_templates' => [],

            // Not imported on purpose:
            // - task_views, default_task_views: the view types themselves;
            //   the ids mean different views in the two versions.
            // - easycase_relates: identical in both versions.
            // - role_actions, role_modules, role_groups, modules, actions: what
            //   each role may do, mapped in step 4 (roles and permissions).
            // - currencies, timezones, timezone_names, languages, industries,
            //   log_types: reference lists the new version ships itself.
            // - user_logins, log_activities: login and activity history.
        ] + array_fill_keys(self::COPY_TABLES, ['mode' => 'copy']);
    }

    /**
     * Base state of an old workflow status: 1 New, 2 In progress, 3 Closed.
     *
     * @param array<string, mixed> $status Old statuses row
     * @return int
     */
    public static function statusMaster(array $status): int
    {
        if (strcasecmp(trim((string)$status['name']), 'New') === 0 || (int)$status['percentage'] === 0) {
            return 1;
        }
        if (strcasecmp(trim((string)$status['name']), 'Completed') === 0 || (int)$status['percentage'] >= 100) {
            return 3;
        }

        return 2;
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
