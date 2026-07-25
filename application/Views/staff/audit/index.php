<div class="tn-screen" style="max-width:1120px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px">
        <div>
            <h2 style="margin:0 0 6px;font-size:26px;font-weight:800;letter-spacing:-.02em">Compliance Audit Trail</h2>
            <p style="margin:0;color:#61756e;font-size:14.5px">Immutable security log of authentication events, uploads, downloads, and system actions.</p>
        </div>
    </div>

    <!-- Filter Bar -->
    <div style="background:#fff;border-radius:24px;padding:20px 24px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -24px rgba(16,54,45,.4);margin-bottom:24px">
        <form action="/staff/audit" method="GET" style="margin:0;display:flex;align-items:center;gap:16px;flex-wrap:wrap">
            <div>
                <label style="display:block;font-size:12px;font-weight:700;color:#7d8e88;margin-bottom:4px">Action Type</label>
                <select name="action" onchange="this.form.submit()" style="padding:10px 14px;border:1.5px solid #e0e9e5;border-radius:12px;font-size:13.5px;background:#fbfdfc">
                    <option value="">All Actions</option>
                    <option value="login" <?= $actionFilter === 'login' ? 'selected' : '' ?>>Login</option>
                    <option value="logout" <?= $actionFilter === 'logout' ? 'selected' : '' ?>>Logout</option>
                    <option value="upload" <?= $actionFilter === 'upload' ? 'selected' : '' ?>>Client Upload</option>
                    <option value="staff_upload" <?= $actionFilter === 'staff_upload' ? 'selected' : '' ?>>Staff Upload</option>
                    <option value="download" <?= $actionFilter === 'download' ? 'selected' : '' ?>>Document Download</option>
                    <option value="request_created" <?= $actionFilter === 'request_created' ? 'selected' : '' ?>>Request Created</option>
                    <option value="message_sent" <?= $actionFilter === 'message_sent' ? 'selected' : '' ?>>Message Sent</option>
                </select>
            </div>

            <div>
                <label style="display:block;font-size:12px;font-weight:700;color:#7d8e88;margin-bottom:4px">Records Limit</label>
                <select name="limit" onchange="this.form.submit()" style="padding:10px 14px;border:1.5px solid #e0e9e5;border-radius:12px;font-size:13.5px;background:#fbfdfc">
                    <option value="25" <?= $limit === 25 ? 'selected' : '' ?>>25 records</option>
                    <option value="50" <?= $limit === 50 ? 'selected' : '' ?>>50 records</option>
                    <option value="100" <?= $limit === 100 ? 'selected' : '' ?>>100 records</option>
                </select>
            </div>

            <?php if (!empty($actionFilter)): ?>
                <a href="/staff/audit" style="margin-top:18px;color:#e07d24;font-size:13px;font-weight:700">Clear Filter</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Audit Logs Table -->
    <div style="background:#fff;border-radius:24px;padding:26px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -24px rgba(16,54,45,.4)">
        <?php if (empty($logs)): ?>
            <div style="padding:48px 24px;text-align:center;color:#8a9a94">No audit log entries matching criteria.</div>
        <?php else: ?>
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;text-align:left">
                    <thead>
                        <tr style="border-bottom:2px solid #eef4f1;color:#7d8e88;font-size:12.5px;text-transform:uppercase;letter-spacing:.05em">
                            <th style="padding:12px 16px;font-weight:700">Timestamp</th>
                            <th style="padding:12px 16px;font-weight:700">User</th>
                            <th style="padding:12px 16px;font-weight:700">Action Type</th>
                            <th style="padding:12px 16px;font-weight:700">Target Entity</th>
                            <th style="padding:12px 16px;font-weight:700">IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr style="border-bottom:1px solid #eef4f1">
                                <td style="padding:16px;color:#7d8e88;font-size:13px;white-space:nowrap">
                                    <?= date('d M Y, H:i:s', strtotime($log['created_at'])) ?>
                                </td>
                                <td style="padding:16px;font-weight:700;color:#213330">
                                    <?= htmlspecialchars($log['user_name'] ?? 'System') ?>
                                    <span style="font-size:11.5px;color:#7d8e88;font-weight:500">(<?= htmlspecialchars($log['user_role'] ?? 'system') ?>)</span>
                                </td>
                                <td style="padding:16px">
                                    <span style="padding:4px 10px;border-radius:999px;font-size:12px;font-weight:700;background:#e6ecf5;color:#41556f">
                                        <?= htmlspecialchars($log['action_type']) ?>
                                    </span>
                                </td>
                                <td style="padding:16px;color:#61756e;font-size:13.5px">
                                    <?= htmlspecialchars($log['target_type']) ?> #<?= $log['target_id'] ?? 'N/A' ?>
                                </td>
                                <td style="padding:16px;font-family:monospace;font-size:13px;color:#5f726c">
                                    <?= htmlspecialchars($log['ip_address']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
