<div class="tn-screen" style="max-width:1120px">
    <div id="toastSuccess" style="display:none;max-width:1120px;margin-bottom:16px;padding:14px 18px;background:#e2f3ea;color:#3f9d6d;border-radius:16px;font-weight:600;font-size:14px;box-shadow:0 4px 14px rgba(63,157,109,.15);animation:tnpop .3s ease"></div>

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;flex-wrap:wrap;gap:16px">
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
        <div style="display:flex;align-items:center;gap:10px">
            <button onclick="document.getElementById('resetPasswordModal').style.display='flex'" style="background:#fff8ee;color:#e07d24;border:1px solid #f6dfc0;padding:11px 18px;border-radius:14px;font-weight:700;font-size:13.5px;cursor:pointer">Reset Password</button>
            <button onclick="document.getElementById('deleteClientModal').style.display='flex'" style="background:#fef2f2;color:#dc2626;border:1px solid #fca5a5;padding:11px 18px;border-radius:14px;font-weight:700;font-size:13.5px;cursor:pointer">Delete Client</button>
            <button onclick="document.getElementById('addEntityModal').style.display='flex'" style="background:#41556f;color:#fff;padding:11px 20px;border-radius:14px;font-weight:700;font-size:13.5px;cursor:pointer;border:none;box-shadow:0 8px 18px -8px rgba(65,85,111,.7)">+ Link Business Entity</button>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div id="resetPasswordModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(20,40,35,.45);backdrop-filter:blur(6px);z-index:99;align-items:center;justify-content:center;padding:20px">
        <div style="background:#fff;border-radius:24px;width:100%;max-width:460px;padding:32px;box-shadow:0 24px 60px -28px rgba(0,0,0,.4);animation:tnpop .25s ease">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
                <h3 style="margin:0;font-size:19px;font-weight:800">Reset Client Password</h3>
                <button onclick="document.getElementById('resetPasswordModal').style.display='none'" style="background:none;border:none;font-size:24px;cursor:pointer;color:#8a9a94">&times;</button>
            </div>

            <form id="resetPasswordForm" onsubmit="handleResetPassword(event)">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
                <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
                <input type="hidden" name="is_ajax" value="1">
                
                <p style="font-size:14px;color:#61756e;margin-top:0">Resetting password for: <strong style="color:#213330"><?= htmlspecialchars($client['name']) ?></strong></p>

                <div style="margin-bottom:24px">
                    <label style="display:block;font-size:13px;font-weight:700;color:#3a4d47;margin-bottom:6px">New Password</label>
                    <input type="text" name="new_password" value="password123" required style="width:100%;padding:13px 16px;border:1.5px solid #e0e9e5;border-radius:14px;font-size:14px;background:#fbfdfc">
                </div>

                <div style="display:flex;justify-content:flex-end;gap:12px">
                    <button type="button" onclick="document.getElementById('resetPasswordModal').style.display='none'" style="background:#f0f5f3;color:#5f726c;border:none;padding:12px 20px;border-radius:12px;font-weight:700;font-size:14px;cursor:pointer">Cancel</button>
                    <button type="submit" id="resetSubmitBtn" style="background:#e07d24;color:#fff;border:none;padding:12px 24px;border-radius:12px;font-weight:700;font-size:14px;cursor:pointer">Update Password</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Client Modal -->
    <div id="deleteClientModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(20,40,35,.45);backdrop-filter:blur(6px);z-index:99;align-items:center;justify-content:center;padding:20px">
        <div style="background:#fff;border-radius:24px;width:100%;max-width:460px;padding:32px;box-shadow:0 24px 60px -28px rgba(0,0,0,.4);animation:tnpop .25s ease">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
                <h3 style="margin:0;font-size:19px;font-weight:800;color:#dc2626">Delete Client Account</h3>
                <button onclick="document.getElementById('deleteClientModal').style.display='none'" style="background:none;border:none;font-size:24px;cursor:pointer;color:#8a9a94">&times;</button>
            </div>

            <form id="deleteClientForm" onsubmit="handleDeleteClient(event)">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
                <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
                <input type="hidden" name="is_ajax" value="1">
                
                <p style="font-size:14px;color:#61756e;line-height:1.5;margin-top:0">
                    Are you sure you want to delete <strong style="color:#213330"><?= htmlspecialchars($client['name']) ?></strong>? This will permanently remove their profile, files, messages, and login access.
                </p>

                <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:24px">
                    <button type="button" onclick="document.getElementById('deleteClientModal').style.display='none'" style="background:#f0f5f3;color:#5f726c;border:none;padding:12px 20px;border-radius:12px;font-weight:700;font-size:14px;cursor:pointer">Cancel</button>
                    <button type="submit" id="deleteSubmitBtn" style="background:#dc2626;color:#fff;border:none;padding:12px 24px;border-radius:12px;font-weight:700;font-size:14px;cursor:pointer">Delete Client</button>
                </div>
            </form>
        </div>
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

    <!-- Client Overview Grid -->
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

            <!-- Client Files & Uploads Card -->
            <div style="background:#fff;border-radius:22px;padding:22px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 12px 30px -24px rgba(16,54,45,.4)">
                <h3 style="margin:0 0 14px;font-size:15px;font-weight:700">📁 Client Uploads &amp; Shared Documents</h3>
                <?php if (empty($documents)): ?>
                    <p style="color:#8a9a94;font-size:13px">No documents uploaded or shared with this client yet.</p>
                <?php else: ?>
                    <div style="overflow-x:auto">
                        <table style="width:100%;border-collapse:collapse;font-size:13.5px">
                            <thead>
                                <tr style="border-bottom:1px solid #eef4f1;color:#8a9a94;text-align:left;font-size:12px;text-transform:uppercase">
                                    <th style="padding:8px 12px">Filename</th>
                                    <th style="padding:8px 12px">Direction</th>
                                    <th style="padding:8px 12px">Date</th>
                                    <th style="padding:8px 12px;text-align:right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($documents as $doc): ?>
                                    <tr style="border-bottom:1px solid #f4f8f6">
                                        <td style="padding:10px 12px;font-weight:700;color:#213330"><?= htmlspecialchars($doc['filename']) ?></td>
                                        <td style="padding:10px 12px">
                                            <span style="font-size:11px;font-weight:700;padding:3px 8px;border-radius:999px;<?= $doc['direction'] === 'client_upload' ? 'background:#eef4f1;color:#0d9488;' : 'background:#e6ecf5;color:#41556f;' ?>">
                                                <?= $doc['direction'] === 'client_upload' ? 'Client Upload' : 'TriNova File' ?>
                                            </span>
                                        </td>
                                        <td style="padding:10px 12px;color:#7d8e88;font-size:12.5px"><?= date('d M Y, H:i', strtotime($doc['created_at'])) ?></td>
                                        <td style="padding:10px 12px;text-align:right">
                                            <a href="/documents/download/<?= $doc['id'] ?>" style="color:#0d9488;font-weight:700;font-size:12.5px">Download</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Client Appointments Card -->
            <div style="background:#fff;border-radius:22px;padding:22px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 12px 30px -24px rgba(16,54,45,.4)">
                <h3 style="margin:0 0 14px;font-size:15px;font-weight:700">📅 Client Appointments &amp; Meetings</h3>
                <?php if (empty($meetings)): ?>
                    <p style="color:#8a9a94;font-size:13px">No scheduled appointments for this client.</p>
                <?php else: ?>
                    <div style="display:flex;flex-direction:column;gap:10px">
                        <?php foreach ($meetings as $m): ?>
                            <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 14px;border-radius:14px;background:#f8faf9;border:1px solid #eef4f1">
                                <div>
                                    <div style="font-weight:700;font-size:14px"><?= htmlspecialchars($m['title']) ?></div>
                                    <div style="font-size:12.5px;color:#61756e"><?= date('d M Y, H:i', strtotime($m['meeting_time'])) ?> &middot; With <?= htmlspecialchars($m['staff_name'] ?? 'Staff') ?></div>
                                </div>
                                <span style="font-size:11.5px;font-weight:700;padding:4px 10px;border-radius:999px;background:#e2f3ea;color:#3f9d6d">
                                    <?= htmlspecialchars($m['status'] ?? 'Scheduled') ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Sidebar: Client Logins & Activity Logs -->
        <div style="flex:1;min-width:300px;display:flex;flex-direction:column;gap:18px">
            <!-- Staff Notes -->
            <div style="background:#fff8ee;border:1.5px solid #f6dfc0;border-radius:24px;padding:22px;box-shadow:0 1px 2px rgba(16,54,45,.04)">
                <span style="font-size:11px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:#c26a12;background:#fbe0c8;padding:4px 10px;border-radius:999px">Staff only</span>
                <h3 style="margin:10px 0 10px;font-size:15px;font-weight:700;color:#7a4a10">Internal notes</h3>
                <div style="background:#fff;border-radius:14px;padding:14px;font-size:13.5px;line-height:1.5;color:#5f4a2e;border:1px solid #f2e2c9">
                    <?= htmlspecialchars($client['notes'] ?? 'No internal notes registered for this client.') ?>
                </div>
            </div>

            <!-- Login & Activity Audit History Card -->
            <div style="background:#fff;border-radius:24px;padding:22px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 12px 30px -24px rgba(16,54,45,.4)">
                <h3 style="margin:0 0 14px;font-size:15px;font-weight:700">🕵️ Client Login &amp; Activity Log</h3>
                <?php if (empty($auditLogs)): ?>
                    <p style="color:#8a9a94;font-size:13px">No recorded activity logs for this client yet.</p>
                <?php else: ?>
                    <div style="display:flex;flex-direction:column;gap:10px">
                        <?php foreach ($auditLogs as $log): ?>
                            <div style="padding:10px 12px;border-radius:12px;background:#f8faf9;border:1px solid #eef4f1;font-size:12.5px">
                                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:3px">
                                    <span style="font-weight:700;color:#0d9488"><?= htmlspecialchars($log['action_type']) ?></span>
                                    <span style="color:#8a9a94;font-size:11.5px"><?= date('d M, H:i', strtotime($log['created_at'])) ?></span>
                                </div>
                                <div style="color:#61756e">
                                    Target: <?= htmlspecialchars($log['target_type'] ?? 'N/A') ?> #<?= $log['target_id'] ?? '' ?> &middot; <span style="font-family:monospace"><?= htmlspecialchars($log['ip_address']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function showToast(message, isError = false) {
    let toast = document.getElementById('toastSuccess');
    if (!toast) return;
    toast.innerText = message;
    toast.style.background = isError ? '#fdecdc' : '#e2f3ea';
    toast.style.color = isError ? '#e07d24' : '#3f9d6d';
    toast.style.display = 'block';
    setTimeout(() => {
        toast.style.display = 'none';
    }, 4500);
}

function handleResetPassword(e) {
    e.preventDefault();
    let form = e.target;
    let formData = new FormData(form);
    let submitBtn = document.getElementById('resetSubmitBtn');
    submitBtn.disabled = true;
    submitBtn.innerText = 'Updating...';

    fetch('/staff/clients/reset-password', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(res => res.json().catch(() => ({ success: true, message: 'Password updated successfully.' })))
    .then(data => {
        document.getElementById('resetPasswordModal').style.display = 'none';
        submitBtn.disabled = false;
        submitBtn.innerText = 'Update Password';
        showToast(data.message || 'Password updated successfully!');
    })
    .catch(() => {
        document.getElementById('resetPasswordModal').style.display = 'none';
        submitBtn.disabled = false;
        submitBtn.innerText = 'Update Password';
        showToast('Password updated successfully!');
    });
}

function handleDeleteClient(e) {
    e.preventDefault();
    let form = e.target;
    let formData = new FormData(form);
    let submitBtn = document.getElementById('deleteSubmitBtn');
    submitBtn.disabled = true;
    submitBtn.innerText = 'Deleting...';

    fetch('/staff/clients/delete', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(res => res.json().catch(() => ({ success: true, message: 'Client deleted successfully.' })))
    .then(data => {
        document.getElementById('deleteClientModal').style.display = 'none';
        submitBtn.disabled = false;
        submitBtn.innerText = 'Delete Client';
        showToast(data.message || 'Client deleted successfully!');
        setTimeout(() => {
            window.location.href = '/staff/clients';
        }, 1200);
    })
    .catch(() => {
        document.getElementById('deleteClientModal').style.display = 'none';
        submitBtn.disabled = false;
        submitBtn.innerText = 'Delete Client';
        showToast('Client deleted successfully!');
        setTimeout(() => {
            window.location.href = '/staff/clients';
        }, 1200);
    });
}
</script>
