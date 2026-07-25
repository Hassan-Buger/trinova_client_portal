<div class="tn-screen" style="max-width:1120px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px">
        <div>
            <h2 style="margin:0 0 6px;font-size:26px;font-weight:800;letter-spacing:-.02em">User Administration & Provisioning</h2>
            <p style="margin:0;color:#61756e;font-size:14.5px">Manage practice staff members and invitation-only client accounts.</p>
        </div>
        <button onclick="document.getElementById('newUserModal').style.display='flex'" style="background:#41556f;color:#fff;padding:12px 22px;border-radius:14px;font-weight:700;font-size:14px;cursor:pointer;border:none;box-shadow:0 8px 18px -8px rgba(65,85,111,.7)">+ Provision User Account</button>
    </div>

    <!-- Provision User Modal -->
    <div id="newUserModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(20,40,35,.45);backdrop-filter:blur(6px);z-index:99;align-items:center;justify-content:center;padding:20px">
        <div style="background:#fff;border-radius:24px;width:100%;max-width:500px;padding:32px;box-shadow:0 24px 60px -28px rgba(0,0,0,.4);animation:tnpop .25s ease">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
                <h3 style="margin:0;font-size:20px;font-weight:800">Provision New User Account</h3>
                <button onclick="document.getElementById('newUserModal').style.display='none'" style="background:none;border:none;font-size:24px;cursor:pointer;color:#8a9a94">&times;</button>
            </div>

            <form action="/staff/users/create" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
                
                <div style="margin-bottom:16px">
                    <label style="display:block;font-size:13px;font-weight:700;color:#3a4d47;margin-bottom:6px">Full Name</label>
                    <input type="text" name="name" placeholder="e.g. Sarah Jenkins" required style="width:100%;padding:13px 16px;border:1.5px solid #e0e9e5;border-radius:14px;font-size:14px;background:#fbfdfc">
                </div>

                <div style="margin-bottom:16px">
                    <label style="display:block;font-size:13px;font-weight:700;color:#3a4d47;margin-bottom:6px">Email Address</label>
                    <input type="email" name="email" placeholder="sarah@example.co.uk" required style="width:100%;padding:13px 16px;border:1.5px solid #e0e9e5;border-radius:14px;font-size:14px;background:#fbfdfc">
                </div>

                <div style="margin-bottom:16px">
                    <label style="display:block;font-size:13px;font-weight:700;color:#3a4d47;margin-bottom:6px">Account Role</label>
                    <select name="role" required style="width:100%;padding:13px 16px;border:1.5px solid #e0e9e5;border-radius:14px;font-size:14px;background:#fbfdfc">
                        <option value="client">Client Account</option>
                        <option value="staff">Staff Member</option>
                    </select>
                </div>

                <div style="margin-bottom:24px">
                    <label style="display:block;font-size:13px;font-weight:700;color:#3a4d47;margin-bottom:6px">Initial Password</label>
                    <input type="text" name="password" value="password123" required style="width:100%;padding:13px 16px;border:1.5px solid #e0e9e5;border-radius:14px;font-size:14px;background:#fbfdfc">
                </div>

                <div style="display:flex;justify-content:flex-end;gap:12px">
                    <button type="button" onclick="document.getElementById('newUserModal').style.display='none'" style="background:#f0f5f3;color:#5f726c;border:none;padding:12px 20px;border-radius:12px;font-weight:700;font-size:14px;cursor:pointer">Cancel</button>
                    <button type="submit" style="background:#0d9488;color:#fff;border:none;padding:12px 24px;border-radius:12px;font-weight:700;font-size:14px;cursor:pointer">Provision & Invite</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Users List Table -->
    <div style="background:#fff;border-radius:24px;padding:26px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -24px rgba(16,54,45,.4)">
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;text-align:left">
                <thead>
                    <tr style="border-bottom:2px solid #eef4f1;color:#7d8e88;font-size:12.5px;text-transform:uppercase;letter-spacing:.05em">
                        <th style="padding:12px 16px;font-weight:700">User Name</th>
                        <th style="padding:12px 16px;font-weight:700">Email</th>
                        <th style="padding:12px 16px;font-weight:700">Role</th>
                        <th style="padding:12px 16px;font-weight:700">Status</th>
                        <th style="padding:12px 16px;font-weight:700">Last Login</th>
                        <th style="padding:12px 16px;font-weight:700;text-align:right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr style="border-bottom:1px solid #eef4f1">
                            <td style="padding:16px;font-weight:700;color:#213330">
                                <?= htmlspecialchars($u['name']) ?>
                            </td>
                            <td style="padding:16px;color:#61756e;font-size:14px">
                                <?= htmlspecialchars($u['email']) ?>
                            </td>
                            <td style="padding:16px">
                                <?php if ($u['role'] === 'staff'): ?>
                                    <span style="padding:4px 10px;border-radius:999px;font-size:11.5px;font-weight:700;background:#e6ecf5;color:#41556f">Staff</span>
                                <?php else: ?>
                                    <span style="padding:4px 10px;border-radius:999px;font-size:11.5px;font-weight:700;background:#dff1ee;color:#0d9488">Client</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding:16px">
                                <?php if ($u['status'] === 'active'): ?>
                                    <span style="padding:4px 10px;border-radius:999px;font-size:11.5px;font-weight:700;background:#e2f3ea;color:#3f9d6d">Active</span>
                                <?php else: ?>
                                    <span style="padding:4px 10px;border-radius:999px;font-size:11.5px;font-weight:700;background:#fdecdc;color:#e07d24">Suspended</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding:16px;color:#7d8e88;font-size:13px">
                                <?= $u['last_login_at'] ? date('d M Y, H:i', strtotime($u['last_login_at'])) : 'Never' ?>
                            </td>
                            <td style="padding:16px;text-align:right">
                                <form action="/staff/users/toggle-status" method="POST" style="margin:0;display:inline-block">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <input type="hidden" name="status" value="<?= $u['status'] === 'active' ? 'suspended' : 'active' ?>">
                                    <button type="submit" style="background:#f0f5f3;color:#5f726c;border:none;padding:7px 14px;border-radius:10px;font-weight:700;font-size:12.5px;cursor:pointer">
                                        <?= $u['status'] === 'active' ? 'Suspend' : 'Activate' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
