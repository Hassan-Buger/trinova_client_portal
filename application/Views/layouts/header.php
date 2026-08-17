<?php
$role = \Application\Core\Session::get('role', 'client');
$userName = (string) \Application\Core\Session::get('user_name', 'User');
$avatarInitial = strtoupper(substr($userName, 0, 1));
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$isDashboard = in_array($currentPath, ['/staff/dashboard', '/client/dashboard'], true);
?>
<header class="tn-topbar<?= $role === 'staff' ? ' tn-topbar--staff' : '' ?>">
    <button id="tnMobileMenuToggle" class="tn-mobile-toggle" type="button" aria-label="Toggle Navigation Menu" aria-expanded="false">
        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
    </button>
    <div class="tn-topbar__title">
        <h1 id="portal-page-title" class="tn-pagetitle"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h1>
        <div id="portalWelcomeText" class="tn-welcome-text" <?= $isDashboard ? '' : 'hidden' ?>>Welcome back, <?= htmlspecialchars($userName) ?><span aria-hidden="true"> · </span><?= $role === 'staff' ? 'Staff portal' : 'Client portal' ?></div>
    </div>
    <div class="tn-topbar__actions">
        <div class="tn-notification-wrap">
            <button id="portalNotificationButton" class="tn-notification-button" type="button" aria-label="Notifications" aria-expanded="false" aria-controls="portalNotificationPanel">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.7 21a2 2 0 0 1-3.4 0"></path></svg>
                <span id="portalNotificationBadge" class="tn-notification-badge" hidden></span>
            </button>
            <section id="portalNotificationPanel" class="tn-notification-panel" hidden aria-label="Recent notifications">
                <div class="tn-notification-heading">Recent notifications</div>
                <div id="portalNotificationList" class="tn-notification-list"><div class="tn-notification-empty">No new notifications</div></div>
            </section>
        </div>
        <div class="tn-profile" aria-label="Signed in as <?= htmlspecialchars($userName) ?>">
            <span class="tn-profile__avatar"><?= htmlspecialchars($avatarInitial) ?></span>
            <span class="tn-profile__identity"><strong><?= htmlspecialchars($userName) ?></strong><small><?= $role === 'staff' ? 'Staff' : 'Client' ?></small></span>
        </div>
    </div>
</header>
