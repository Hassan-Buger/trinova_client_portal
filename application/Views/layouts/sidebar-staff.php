<?php
$uri = $_SERVER['REQUEST_URI'] ?? '';

$nav = [
    ['href' => '/staff/dashboard', 'label' => 'Dashboard', 'icon' => 'grid'],
    ['href' => '/staff/clients', 'label' => 'Clients', 'icon' => 'user'],
    ['href' => '/staff/documents', 'label' => 'Documents', 'icon' => 'file'],
    ['href' => '/staff/messages', 'label' => 'Messages', 'icon' => 'chat'],
    ['href' => '/staff/requests', 'label' => 'Requests', 'icon' => 'upload'],
    ['href' => '/staff/deadlines', 'label' => 'Deadlines', 'icon' => 'cal'],
    ['href' => '/staff/audit', 'label' => 'Audit Log', 'icon' => 'shield'],
    ['href' => '/staff/users', 'label' => 'User Admin', 'icon' => 'user'],
];

function renderStaffIcon(string $ic): string {
    $map = [
        'grid' => '<path d="M3 3h7v7H3z M14 3h7v7h-7z M14 14h7v7h-7z M3 14h7v7H3z"/>',
        'upload' => '<path d="M12 15V3 m7 8 5-5 5 5 M20 16.5V19a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2.5"/>',
        'file' => '<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z M14 3v6h6 M9 14h6 M9 18h4"/>',
        'chat' => '<path d="M21 15a2 2 0 0 1-2 2H8l-4 4V5a2 2 0 0 1 2-2h13a2 2 0 0 1 2 2z"/>',
        'cal' => '<path d="M8 2v4 M16 2v4 M4 9h16 M5 4h14a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/>',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
        'user' => '<path d="M19 21a7 7 0 0 0-14 0 M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/>',
    ];
    return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . ($map[$ic] ?? '') . '</svg>';
}
?>

<aside class="tn-side" style="width:262px;flex-shrink:0;background:#fff;border-right:1px solid rgba(20,60,50,.07);display:flex;flex-direction:column;padding:22px 16px;position:sticky;top:0;height:100vh">
    <div class="tn-sidebar-brand" style="display:flex;align-items:center;padding:4px 8px 22px">
        <img class="tn-brand-logo" src="/assets/images/trinova-staff-portal-logo.png" alt="TriNova Staff Portal">
    </div>
    <nav style="display:flex;flex-direction:column;gap:3px;flex:1">
        <?php foreach ($nav as $item): ?>
            <?php
            $active = (rtrim($uri, '/') === rtrim($item['href'], '/'));
            $style = 'display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:15px;cursor:pointer;font-weight:600;font-size:14.5px;transition:background .12s;' .
                     ($active ? 'background:#e6ecf5;color:#41556f;' : 'color:#5f726c;');
            ?>
            <a href="<?= $item['href'] ?>" class="tn-navitem" style="<?= $style ?>" title="<?= $item['label'] ?>">
                <?= renderStaffIcon($item['icon']) ?>
                <span class="tn-navlabel"><?= $item['label'] ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
    <form action="/logout" method="post" style="margin:8px 0 0">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
    <button type="submit" class="tn-navitem" style="display:flex;width:100%;align-items:center;gap:12px;padding:12px 14px;border:0;background:transparent;border-radius:15px;cursor:pointer;color:#61756e;font-weight:600;font-size:14.5px">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4 m16 17 5-5-5-5 M21 12H9"/>
        </svg>
        <span class="tn-logout-label tn-navlabel">Logout</span>
    </button>
    </form>
</aside>
