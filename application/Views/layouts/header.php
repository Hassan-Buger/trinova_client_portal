<?php
$role = \Application\Core\Session::get('role', 'client');
$userName = \Application\Core\Session::get('user_name', 'User');
$avatarInitial = strtoupper(substr($userName, 0, 1));
$avatarBg = ($role === 'staff') ? '#41556f' : '#0d9488';
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$isDashboard = in_array($currentPath, ['/staff/dashboard', '/client/dashboard'], true);

$pillOn = 'padding:8px 18px;border-radius:999px;font-weight:700;font-size:13.5px;cursor:pointer;background:#0d9488;color:#fff;transition:all .15s';
$pillOff = 'padding:8px 18px;border-radius:999px;font-weight:700;font-size:13.5px;cursor:pointer;color:#61756e;transition:all .15s';
?>
<header class="tn-topbar" style="display:flex;align-items:center;gap:16px;padding:18px 32px;position:sticky;top:0;background:rgba(238,244,241,.82);backdrop-filter:blur(10px);z-index:5">
    <div style="min-width:0">
        <h1 id="portal-page-title" class="tn-pagetitle" style="margin:0;font-size:23px;font-weight:800;letter-spacing:-.02em"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h1>
        <div id="portalWelcomeText" class="tn-welcome-text" <?= $isDashboard ? '' : 'hidden' ?>>Welcome back, <?= htmlspecialchars($userName) ?> · <?= $role === 'staff' ? 'Staff portal' : 'Client portal' ?></div>
    </div>
    <div style="flex:1"></div>
    <div class="tn-notification-wrap">
        <button id="portalNotificationButton" class="tn-notification-button" type="button" aria-label="Notifications" aria-expanded="false" aria-controls="portalNotificationPanel">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path>
                <path d="M13.7 21a2 2 0 0 1-3.4 0"></path>
            </svg>
            <span id="portalNotificationBadge" class="tn-notification-badge" hidden></span>
        </button>
        <section id="portalNotificationPanel" class="tn-notification-panel" hidden aria-label="Recent notifications">
            <div class="tn-notification-heading">Recent notifications</div>
            <div id="portalNotificationList" class="tn-notification-list"><div class="tn-notification-empty">No new notifications</div></div>
        </section>
    </div>
    <div style="width:44px;height:44px;border-radius:14px;background:<?= $avatarBg ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:16px">
        <?= $avatarInitial ?>
    </div>
</header>
