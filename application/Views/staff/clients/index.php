<div class="tn-screen" style="max-width:1120px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px">
        <div>
            <h2 style="margin:0;font-size:24px;font-weight:800;letter-spacing:-.02em">Clients Overview</h2>
            <p style="margin:4px 0 0;color:#61756e;font-size:14px">Manage practice client accounts, AML status, and entity profiles.</p>
        </div>
        <button onclick="document.getElementById('newClientModal').style.display='flex'" style="background:#0d9488;color:#fff;padding:12px 22px;border-radius:14px;font-weight:700;font-size:14px;cursor:pointer;border:none;box-shadow:0 8px 18px -8px rgba(13,148,136,.7)">+ Create Client Account</button>
    </div>

    <!-- Create Client Modal -->
    <div id="newClientModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(20,40,35,.45);backdrop-filter:blur(6px);z-index:99;align-items:center;justify-content:center;padding:20px">
        <div style="background:#fff;border-radius:24px;width:100%;max-width:520px;padding:32px;box-shadow:0 24px 60px -28px rgba(0,0,0,.4);animation:tnpop .25s ease">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
                <h3 style="margin:0;font-size:20px;font-weight:800">Create Client Account</h3>
                <button onclick="document.getElementById('newClientModal').style.display='none'" style="background:none;border:none;font-size:24px;cursor:pointer;color:#8a9a94">&times;</button>
            </div>

            <form action="/staff/clients/create" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
                
                <div style="margin-bottom:16px">
                    <label style="display:block;font-size:13px;font-weight:700;color:#3a4d47;margin-bottom:6px">Client Contact Name</label>
                    <input type="text" name="name" placeholder="e.g. David Miller" required style="width:100%;padding:13px 16px;border:1.5px solid #e0e9e5;border-radius:14px;font-size:14px;background:#fbfdfc">
                </div>

                <div style="margin-bottom:16px">
                    <label style="display:block;font-size:13px;font-weight:700;color:#3a4d47;margin-bottom:6px">Email Address</label>
                    <input type="email" name="email" placeholder="david@millergroup.co.uk" required style="width:100%;padding:13px 16px;border:1.5px solid #e0e9e5;border-radius:14px;font-size:14px;background:#fbfdfc">
                </div>

                <div style="margin-bottom:16px">
                    <label style="display:block;font-size:13px;font-weight:700;color:#3a4d47;margin-bottom:6px">Phone Number</label>
                    <input type="text" name="phone" placeholder="07700 900888" style="width:100%;padding:13px 16px;border:1.5px solid #e0e9e5;border-radius:14px;font-size:14px;background:#fbfdfc">
                </div>

                <div style="margin-bottom:16px">
                    <label style="display:block;font-size:13px;font-weight:700;color:#3a4d47;margin-bottom:6px">Registered Address</label>
                    <input type="text" name="address" placeholder="10 Station Road, Leeds LS2 8AB" style="width:100%;padding:13px 16px;border:1.5px solid #e0e9e5;border-radius:14px;font-size:14px;background:#fbfdfc">
                </div>

                <div style="margin-bottom:24px">
                    <label style="display:block;font-size:13px;font-weight:700;color:#3a4d47;margin-bottom:6px">Initial AML Status</label>
                    <select name="aml_status" style="width:100%;padding:13px 16px;border:1.5px solid #e0e9e5;border-radius:14px;font-size:14px;background:#fbfdfc">
                        <option value="Action Required">Action Required</option>
                        <option value="Complete">Complete</option>
                    </select>
                </div>

                <div style="display:flex;justify-content:flex-end;gap:12px">
                    <button type="button" onclick="document.getElementById('newClientModal').style.display='none'" style="background:#f0f5f3;color:#5f726c;border:none;padding:12px 20px;border-radius:12px;font-weight:700;font-size:14px;cursor:pointer">Cancel</button>
                    <button type="submit" style="background:#0d9488;color:#fff;border:none;padding:12px 24px;border-radius:12px;font-weight:700;font-size:14px;cursor:pointer">Create Client Account</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Clients Table -->
    <div style="background:#fff;border-radius:24px;padding:12px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -24px rgba(16,54,45,.4)">
        <table style="width:100%;border-collapse:collapse;text-align:left">
            <thead>
                <tr style="border-bottom:1px solid rgba(20,60,50,.08);color:#8a9a94;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.05em">
                    <th style="padding:14px 16px">Client Name</th>
                    <th style="padding:14px 16px">Email</th>
                    <th style="padding:14px 16px">Phone</th>
                    <th style="padding:14px 16px">AML Status</th>
                    <th style="padding:14px 16px">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clients as $c): ?>
                    <tr style="border-bottom:1px solid rgba(20,60,50,.06)">
                        <td style="padding:16px;font-weight:700;font-size:15px"><?= htmlspecialchars($c['name']) ?></td>
                        <td style="padding:16px;color:#61756e;font-size:14px"><?= htmlspecialchars($c['email']) ?></td>
                        <td style="padding:16px;color:#61756e;font-size:14px"><?= htmlspecialchars($c['phone'] ?? '—') ?></td>
                        <td style="padding:16px">
                            <span style="font-size:11.5px;font-weight:700;padding:5px 12px;border-radius:999px;<?= $c['aml_status'] === 'Complete' ? 'background:#e2f3ea;color:#3f9d6d;' : 'background:#fdecdc;color:#e07d24;' ?>">
                                <?= htmlspecialchars($c['aml_status']) ?>
                            </span>
                        </td>
                        <td style="padding:16px">
                            <a href="/staff/clients/<?= $c['id'] ?>" style="font-weight:700;font-size:13.5px;color:#0d9488">View Overview &rarr;</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
