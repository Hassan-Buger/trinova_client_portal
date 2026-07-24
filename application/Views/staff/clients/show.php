<div class="tn-screen" style="max-width:1120px">
    <div style="display:flex;align-items:center;gap:16px;margin-bottom:22px">
        <div style="width:56px;height:56px;border-radius:17px;background:#41556f;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:22px">
            <?= strtoupper(substr($client['name'], 0, 2)) ?>
        </div>
        <div>
            <div style="display:flex;align-items:center;gap:10px">
                <h2 style="margin:0;font-size:24px;font-weight:800;letter-spacing:-.02em"><?= htmlspecialchars($client['name']) ?></h2>
                <span style="font-size:12px;font-weight:700;color:#3f9d6d;background:#e2f3ea;padding:5px 12px;border-radius:999px">Active client</span>
            </div>
            <div style="color:#61756e;font-size:13.5px;margin-top:3px">
                <?= htmlspecialchars($client['email']) ?> &middot; <?= htmlspecialchars($client['phone'] ?? 'No phone') ?>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:20px;flex-wrap:wrap;align-items:flex-start">
        <div style="flex:2;min-width:340px;display:flex;flex-direction:column;gap:18px">
            <div style="display:flex;gap:18px;flex-wrap:wrap">
                <!-- Entities Card -->
                <div style="flex:1;min-width:220px;background:#fff;border-radius:22px;padding:22px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 12px 30px -24px rgba(16,54,45,.4)">
                    <h3 style="margin:0 0 14px;font-size:15px;font-weight:700">Businesses &amp; entities</h3>
                    <?php if (empty($entities)): ?>
                        <p style="color:#8a9a94;font-size:13px">No linked businesses.</p>
                    <?php else: ?>
                        <?php foreach ($entities as $b): ?>
                            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid rgba(20,60,50,.07)">
                                <span style="font-weight:600;font-size:14px"><?= htmlspecialchars($b['company_name']) ?></span>
                                <span style="font-size:11.5px;font-weight:600;color:#61756e;background:#f0f5f3;padding:4px 10px;border-radius:999px">
                                    <?= htmlspecialchars($b['company_number'] ? 'Company' : 'Individual') ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Outstanding Items -->
                <div style="flex:1;min-width:220px;background:#fff;border-radius:22px;padding:22px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 12px 30px -24px rgba(16,54,45,.4)">
                    <h3 style="margin:0 0 14px;font-size:15px;font-weight:700">Outstanding items</h3>
                    <?php if (empty($outstanding)): ?>
                        <p style="color:#3f9d6d;font-size:13px;font-weight:600">All items up to date.</p>
                    <?php else: ?>
                        <?php foreach ($outstanding as $o): ?>
                            <div style="display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid rgba(20,60,50,.07)">
                                <span style="width:8px;height:8px;border-radius:50%;background:#ef8f3c;flex-shrink:0"></span>
                                <span style="flex:1;font-weight:600;font-size:14px"><?= htmlspecialchars($o['title']) ?></span>
                                <span style="font-size:12px;color:#e07d24;font-weight:600">Due <?= date('d M', strtotime($o['due_date'])) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Staff Only Internal Notes -->
        <div style="flex:1;min-width:280px;background:#fff8ee;border:1.5px solid #f6dfc0;border-radius:24px;padding:24px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 12px 30px -24px rgba(16,54,45,.4)">
            <div style="display:flex;align-items:center;gap:9px;margin-bottom:14px">
                <span style="font-size:11px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:#c26a12;background:#fbe0c8;padding:5px 11px;border-radius:999px">Staff only</span>
            </div>
            <h3 style="margin:0 0 14px;font-size:16px;font-weight:700;color:#7a4a10">Internal notes</h3>
            <div style="background:#fff;border-radius:15px;padding:15px 16px;font-size:14px;line-height:1.55;color:#5f4a2e;border:1px solid #f2e2c9">
                <?= htmlspecialchars($client['notes'] ?? 'No internal notes registered for this client.') ?>
            </div>
        </div>
    </div>
</div>
