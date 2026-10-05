<?php
declare(strict_types=1);

namespace Kewico\Service;

use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;

/**
 * Extra fields on the user card (Users > Manage), as kewico_php8 showed them:
 * role, business unit, and the mobile app's operating system, last usage and
 * version.
 *
 * Role and business unit come from the company membership; the app fields
 * are kept in kewico_legacy_users until the mobile API is ported (step 12).
 */
class UserCardInfo
{
    /**
     * Add a `Kewico` entry to every user of the card list.
     *
     * @param array<int|string, array<string, mixed>> $users Users as built by UsersController::manage()
     * @param int $companyId Current company (SES_COMP)
     * @param callable(string): string $formatDate Formats a UTC datetime for the viewer
     * @return array<int|string, array<string, mixed>>
     */
    public function addTo(array $users, int $companyId, callable $formatDate): array
    {
        $ids = array_values(array_filter(array_map(fn($u) => (int)($u['id'] ?? 0), $users)));
        if (!$ids) {
            return $users;
        }

        try {
            $rows = $this->load($ids, $companyId);
        } catch (\Throwable $e) {
            // The card must never break because of the Kewico fields
            // (e.g. a fresh install without the imported tables).
            Log::warning('Kewico user card fields not loaded: ' . $e->getMessage());

            return $users;
        }

        foreach ($users as $key => $user) {
            $row = $rows[(int)($user['id'] ?? 0)] ?? [];
            $lastUsage = $row['app_last_login'] ?? null;
            $users[$key]['Kewico'] = [
                'role' => $row['role'] ?? null,
                'business_unit' => $row['business_unit'] ?? null,
                'app_os' => $row['app_os'] ?? null,
                'app_last_usage' => $lastUsage && strpos((string)$lastUsage, '0000-00-00') !== 0
                    ? $formatDate((string)$lastUsage)
                    : null,
                'app_version' => $row['app_version'] ?? null,
            ];
        }

        return $users;
    }

    /**
     * @param array<int> $ids User ids
     * @param int $companyId Company id
     * @return array<int, array<string, mixed>> Rows keyed by user id
     */
    private function load(array $ids, int $companyId): array
    {
        $connection = ConnectionManager::get('default');
        $hasLegacy = (bool)$connection->execute(
            "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'kewico_legacy_users'"
        )->fetchColumn(0);
        $hasUnits = (bool)$connection->execute(
            "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'business_units'"
        )->fetchColumn(0);

        $select = ['cu.user_id', 'r.role'];
        $joins = ['LEFT JOIN roles r ON r.id = cu.role_id'];
        if ($hasUnits) {
            $select[] = 'bu.business_unit';
            $joins[] = 'LEFT JOIN business_units bu ON bu.id = cu.business_unit_id';
        }
        if ($hasLegacy) {
            array_push($select, 'l.app_os', 'l.app_last_login', 'l.app_version');
            $joins[] = 'LEFT JOIN kewico_legacy_users l ON l.id = cu.user_id';
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = 'SELECT ' . implode(', ', $select) . ' FROM company_users cu ' . implode(' ', $joins)
            . " WHERE cu.company_id = ? AND cu.user_id IN ({$placeholders})";

        $rows = [];
        foreach ($connection->execute($sql, array_merge([$companyId], $ids))->fetchAll('assoc') as $row) {
            $rows[(int)$row['user_id']] = $row;
        }

        return $rows;
    }
}
