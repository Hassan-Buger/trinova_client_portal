<div class="tn-screen" style="max-width:1120px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px">
        <div style="display:flex;align-items:center;gap:16px">
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
        <button onclick="document.getElementById('addEntityModal').style.display='flex'" style="background:#41556f;color:#fff;padding:11px 20px;border-radius:14px;font-weight:700;font-size:13.5px;cursor:pointer;border:none;box-shadow:0 8px 18px -8px rgba(65,85,111,.7)">+ Link Business Entity</button>
    </div>

    <!-- Add Entity Modal -->
    <div id="addEntityModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(20,40,35,.45);backdrop-filter:blur(6px);z-index:99;align-items:center;justify-content:center;padding:20px">
        <div style="background:#fff;border-radius:24px;width:100%;max-width:480px;padding:32px;box-shadow:0 24px 60px -28px rgba(0,0,0,.4);animation:tnpop .25s ease">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
                <h3 style="margin:0;font-size:19px;font-weight:800">Link Business Entity</h3>
                <button onclick="document.getElementById('addEntityModal').style.display='none'" style="background:none;border:none;font-size:24px;cursor:pointer;color:#8a9a94">&times;</button>
            </div>

            <form action="/staff/clients/add-entity" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
                <input type="hidden" name="client_id" value="<?= $client['id'] ?>">

                <div style="margin-bottom:16px">
                    <label style="display:block;font-size:13px;font-weight:700;color:#3a4d47;margin-bottom:6px">Company / Entity Name</label>
                    <input type="text" name="company_name" placeholder="e.g. Acme Services Ltd" required style="width:100%;padding:13px 16px;border:1.5px solid #e0e9e5;border-radius:14px;font-size:14px;background:#fbfdfc">
                </div>

                <div style="margin-bottom:16px">
                    <label style="display:block;font-size:13px;font-weight:700;color:#3a4d47;margin-bottom:6px">Company Number (Optional)</label>
                    <input type="text" name="company_number" placeholder="08942104" style="width:100%;padding:13px 16px;border:1.5px solid #e0e9e5;border-radius:14px;font-size:14px;background:#fbfdfc">
                </div>

                <div style="margin-bottom:24px">
                    <label style="display:block;font-size:13px;font-weight:700;color:#3a4d47;margin-bottom:6px">Tax Reference / UTR (Optional)</label>
                    <input type="text" name="tax_reference" placeholder="9482104821" style="width:100%;padding:13px 16px;border:1.5px solid #e0e9e5;border-radius:14px;font-size:14px;background:#fbfdfc">
                </div>

                <div style="display:flex;justify-content:flex-end;gap:12px">
                    <button type="button" onclick="document.getElementById('addEntityModal').style.display='none'" style="background:#f0f5f3;color:#5f726c;border:none;padding:12px 20px;border-radius:12px;font-weight:700;font-size:14px;cursor:pointer">Cancel</button>
                    <button type="submit" style="background:#0d9488;color:#fff;border:none;padding:12px 24px;border-radius:12px;font-weight:700;font-size:14px;cursor:pointer">Link Entity</button>
                </div>
            </form>
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
