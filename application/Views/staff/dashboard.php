<div class="tn-screen" style="max-width:1120px">
    <p style="margin:0 0 22px;color:#61756e;font-size:15px">Overview of tasks, recent uploads, unread messages, and compliance deadlines.</p>

    <!-- Staff Stat Grid -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px">
        <div style="background:#fff;border-radius:22px;padding:22px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -24px rgba(16,54,45,.4)">
            <div style="font-size:32px;font-weight:800;letter-spacing:-.02em"><?= (int)($recentUploadsCount ?? 0) ?></div>
            <div style="color:#61756e;font-size:13.5px;font-weight:600;margin-top:2px">Recent uploads</div>
        </div>
        <div style="background:#fff;border-radius:22px;padding:22px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -24px rgba(16,54,45,.4)">
            <div style="font-size:32px;font-weight:800;letter-spacing:-.02em"><?= (int)($unreadMessagesCount ?? 0) ?></div>
            <div style="color:#61756e;font-size:13.5px;font-weight:600;margin-top:2px">Unread messages</div>
        </div>
        <div style="background:#fff;border-radius:22px;padding:22px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -24px rgba(16,54,45,.4)">
            <div style="font-size:32px;font-weight:800;letter-spacing:-.02em;color:#e07d24"><?= (int)($overdueRequestsCount ?? 0) ?></div>
            <div style="color:#61756e;font-size:13.5px;font-weight:600;margin-top:2px">Overdue requests</div>
        </div>
        <div style="background:#fff;border-radius:22px;padding:22px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -24px rgba(16,54,45,.4)">
            <div style="font-size:32px;font-weight:800;letter-spacing:-.02em;color:#e07d24"><?= (int)($amlActionCount ?? 0) ?></div>
            <div style="color:#61756e;font-size:13.5px;font-weight:600;margin-top:2px">AML actions</div>
        </div>
    </div>

    <!-- Staff Main Split -->
    <div style="display:flex;gap:20px;flex-wrap:wrap">
        <!-- Practice Activity Feed from Audit Log -->
        <div style="flex:1.5;min-width:320px;background:#fff;border-radius:24px;padding:26px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -24px rgba(16,54,45,.4)">
            <h3 style="margin:0 0 16px;font-size:17px;font-weight:700">Recent practice activity</h3>
            <?php if (empty($recentActivity)): ?>
                <div style="padding:20px 0;color:#8a9a94;font-size:13.5px">No audit log entries recorded yet.</div>
            <?php else: ?>
                <?php foreach ($recentActivity as $act): ?>
                    <div style="display:flex;align-items:center;gap:14px;padding:13px 0;border-bottom:1px solid rgba(20,60,50,.07)">
                        <div style="width:9px;height:9px;border-radius:50%;background:#0d9488;flex-shrink:0"></div>
                        <div style="flex:1;font-size:14px">
                            <span style="font-weight:700"><?= htmlspecialchars($act['user_name'] ?? 'System') ?></span>
                            <span style="color:#5f726c"><?= htmlspecialchars($act['action_type']) ?> on <?= htmlspecialchars($act['target_type']) ?></span>
                        </div>
                        <div style="color:#8a9a94;font-size:12.5px;font-weight:500;white-space:nowrap">
                            <?= date('H:i, d M', strtotime($act['created_at'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Quick Search & Actions -->
        <div style="flex:1;min-width:280px;background:#fff;border-radius:24px;padding:26px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -24px rgba(16,54,45,.4)">
            <h3 style="margin:0 0 16px;font-size:17px;font-weight:700">Quick search</h3>
            <div style="position:relative;margin-bottom:18px">
                <input placeholder="Search client, company or email" style="width:100%;padding:14px 16px;border:1.5px solid #e0e9e5;border-radius:15px;font-size:14px;background:#fbfdfc">
            </div>
            <div style="display:flex;flex-direction:column;gap:11px">
                <a href="/staff/clients" style="display:flex;align-items:center;gap:10px;background:#0d9488;color:#fff;padding:13px 16px;border-radius:14px;font-weight:700;font-size:14.5px;justify-content:center">
                    + Create client
                </a>
                <a href="/staff/requests" style="display:flex;align-items:center;gap:10px;background:#fdecdc;color:#e07d24;padding:13px 16px;border-radius:14px;font-weight:700;font-size:14.5px;justify-content:center">
                    Request documents
                </a>
            </div>
        </div>
    </div>
</div>
