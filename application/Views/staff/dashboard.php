<?php
$metrics = [
    ['label' => 'Recent uploads', 'value' => (int) ($recentUploadsCount ?? 0), 'context' => 'Documents received recently', 'icon' => 'upload', 'tone' => 'teal'],
    ['label' => 'Unread messages', 'value' => (int) ($unreadMessagesCount ?? 0), 'context' => 'Conversations awaiting review', 'icon' => 'message', 'tone' => 'teal'],
    ['label' => 'Overdue requests', 'value' => (int) ($overdueRequestsCount ?? 0), 'context' => 'Client requests past due', 'icon' => 'clock', 'tone' => 'warning'],
    ['label' => 'AML actions', 'value' => (int) ($amlActionCount ?? 0), 'context' => 'Client records requiring action', 'icon' => 'shield', 'tone' => 'warning'],
];

$metricIcons = [
    'upload' => '<path d="M12 16V4m0 0L7.5 8.5M12 4l4.5 4.5"/><path d="M5 14v4a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4"/>',
    'message' => '<path d="M20 15a3 3 0 0 1-3 3H8l-4 3V7a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3Z"/><path d="M8 9h8M8 13h5"/>',
    'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="M12 8v4m0 4h.01"/>',
];

$activityLabels = [
    'login' => 'Signed in to the staff portal',
    'logout' => 'Signed out of the staff portal',
    'upload' => 'Uploaded a document',
    'staff_message_sent' => 'Sent a staff message',
    'client_message_sent' => 'Received a client message',
    'create' => 'Created a record',
    'delete' => 'Moved a record to Trash',
    'restore' => 'Restored a record from Trash',
    'password_reset_complete' => 'Completed a password reset',
    'account_activated' => 'Activated an account',
];

$humanizeActivity = static function (array $activity) use ($activityLabels): string {
    $action = (string) ($activity['action_type'] ?? 'updated');
    if (isset($activityLabels[$action])) return $activityLabels[$action];
    return ucfirst(str_replace('_', ' ', $action));
};
?>

<div class="tn-screen staff-dashboard">
    <header class="staff-dashboard__intro">
        <p>Overview of tasks, recent uploads, unread messages, and compliance deadlines.</p>
        <span class="staff-dashboard__date"><?= date('l, j F') ?></span>
    </header>

    <section class="staff-metrics" aria-label="Practice overview">
        <?php foreach ($metrics as $metric): ?>
            <article class="staff-metric staff-metric--<?= $metric['tone'] ?>">
                <div class="staff-metric__top">
                    <span class="staff-metric__label"><?= htmlspecialchars($metric['label']) ?></span>
                    <span class="staff-metric__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><?= $metricIcons[$metric['icon']] ?></svg>
                    </span>
                </div>
                <strong class="staff-metric__value"><?= $metric['value'] ?></strong>
                <span class="staff-metric__context"><?= htmlspecialchars($metric['context']) ?></span>
            </article>
        <?php endforeach; ?>
    </section>

    <div class="staff-dashboard__grid">
        <section class="staff-panel staff-activity" aria-labelledby="practice-activity-title">
            <header class="staff-panel__header">
                <div>
                    <span class="staff-panel__eyebrow">Latest updates</span>
                    <h2 id="practice-activity-title">Recent practice activity</h2>
                </div>
                <a class="staff-panel__link" href="/staff/audit">View audit log
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M14 7l5 5-5 5"/></svg>
                </a>
            </header>

            <?php if (empty($recentActivity)): ?>
                <div class="staff-empty">
                    <span class="staff-empty__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M5 4h14v16H5zM8 8h8M8 12h8M8 16h5"/></svg></span>
                    <strong>No recent activity</strong>
                    <span>Practice updates will appear here as work is completed.</span>
                </div>
            <?php else: ?>
                <div class="staff-activity__list">
                    <?php foreach ($recentActivity as $act): ?>
                        <?php $activityTime = strtotime((string) $act['created_at']); ?>
                        <article class="staff-activity__item">
                            <span class="staff-activity__marker" aria-hidden="true"></span>
                            <div class="staff-activity__content">
                                <strong><?= htmlspecialchars($act['user_name'] ?? 'System') ?></strong>
                                <span><?= htmlspecialchars($humanizeActivity($act)) ?></span>
                            </div>
                            <time datetime="<?= date('c', $activityTime) ?>">
                                <strong><?= date('H:i', $activityTime) ?></strong>
                                <span><?= date('d M', $activityTime) ?></span>
                            </time>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <aside class="staff-panel staff-command" aria-labelledby="quick-actions-title">
            <header class="staff-panel__header staff-panel__header--compact">
                <div>
                    <span class="staff-panel__eyebrow">Command centre</span>
                    <h2 id="quick-actions-title">Quick actions</h2>
                </div>
            </header>

            <form class="staff-search" action="/staff/clients" method="GET" role="search">
                <label for="dashboardClientSearch">Find a client</label>
                <div class="staff-search__control">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                    <input id="dashboardClientSearch" type="search" name="q" placeholder="Name, company or email" autocomplete="off">
                    <button type="submit" aria-label="Search clients">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M14 7l5 5-5 5"/></svg>
                    </button>
                </div>
            </form>

            <div class="staff-command__actions">
                <a class="staff-action staff-action--primary" href="/staff/clients">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    <span><strong>Create client</strong><small>Add a new portal account</small></span>
                    <svg class="staff-action__arrow" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                </a>
                <a class="staff-action staff-action--secondary" href="/staff/requests">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M6 3h9l4 4v14H6zM14 3v5h5M9 13h7M9 17h5"/></svg>
                    <span><strong>Request documents</strong><small>Ask a client for files</small></span>
                    <svg class="staff-action__arrow" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                </a>
            </div>

            <?php if (($overdueRequestsCount ?? 0) > 0 || ($amlActionCount ?? 0) > 0 || ($unreadMessagesCount ?? 0) > 0): ?>
                <div class="staff-attention">
                    <div class="staff-attention__heading"><span>Needs attention</span><span class="staff-attention__dot" aria-hidden="true"></span></div>
                    <?php if (($overdueRequestsCount ?? 0) > 0): ?><a href="/staff/requests"><span>Overdue requests</span><strong><?= (int) $overdueRequestsCount ?></strong></a><?php endif; ?>
                    <?php if (($amlActionCount ?? 0) > 0): ?><a href="/staff/clients"><span>AML actions</span><strong><?= (int) $amlActionCount ?></strong></a><?php endif; ?>
                    <?php if (($unreadMessagesCount ?? 0) > 0): ?><a href="/staff/messages"><span>Unread messages</span><strong><?= (int) $unreadMessagesCount ?></strong></a><?php endif; ?>
                </div>
            <?php endif; ?>
        </aside>
    </div>
</div>
