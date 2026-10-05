<?php
/**
 * Kewico rows on the user card (Users > Manage), as in kewico_php8.
 *
 * @var \App\View\AppView $this
 * @var array<string, mixed>|null $info $user['Kewico'] from \Kewico\Service\UserCardInfo
 * @var string $part 'main' (role, business unit) or 'app' (mobile app fields)
 */
if (!is_array($info ?? null)) {
    return;
}
$value = function ($v): string {
    $v = is_string($v) ? trim($v) : $v;

    return ($v === null || $v === '') ? 'N/A' : h((string)$v);
};
$rows = ($part ?? 'main') === 'app'
    ? [
        __('APP Operating System') => $info['app_os'],
        __('APP Last Usage Date') => $info['app_last_usage'],
        __('APP Current Version') => $info['app_version'],
    ]
    : [
        __('Role') => $info['role'],
        __('Business Unit') => $info['business_unit'],
    ];
foreach ($rows as $title => $v): ?>
                    <li>
                        <span class="cnt_ttl_usr"><?= h($title) ?></span>
                        <span class="cnt_usr ellipsis-view" title="<?= $value($v) ?>"><?= $value($v) ?></span>
                    </li>
<?php endforeach;
