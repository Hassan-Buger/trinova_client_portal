<div class="tn-screen" style="max-width:1120px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px">
        <div>
            <p style="margin:0;color:#61756e;font-size:14.5px">Manage practice staff members and invitation-only client accounts.</p>
        </div>
        <button onclick="document.getElementById('newUserModal').style.display='flex'" style="background:#41556f;color:#fff;padding:12px 22px;border-radius:14px;font-weight:700;font-size:14px;cursor:pointer;border:none;box-shadow:0 8px 18px -8px rgba(65,85,111,.7)">+ Provision User Account</button>
    </div>

    <?php $hasUserFilters = ($filters['search'] ?? '') !== '' || ($filters['role'] ?? '') !== '' || ($filters['status'] ?? '') !== '' || ($filters['login'] ?? '') !== ''; ?>
    <form action="/staff/users" method="GET" data-ajax-form style="background:#fff;border-radius:22px;padding:18px;margin-bottom:20px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -24px rgba(16,54,45,.3)">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px">
            <input type="search" name="q" value="<?= htmlspecialchars($filters['search'] ?? '') ?>" data-ajax-search placeholder="Search name or email…" aria-label="Search users" style="padding:11px 14px;border:1.5px solid #e0e9e5;border-radius:13px;background:#fbfdfc">
            <select name="role" onchange="this.form.requestSubmit()" aria-label="Filter by role" style="padding:11px;border:1.5px solid #e0e9e5;border-radius:13px;background:#fbfdfc"><option value="">All roles</option><option value="client" <?= ($filters['role'] ?? '')==='client'?'selected':'' ?>>Clients</option><option value="staff" <?= ($filters['role'] ?? '')==='staff'?'selected':'' ?>>Staff</option></select>
            <select name="status" onchange="this.form.requestSubmit()" aria-label="Filter by status" style="padding:11px;border:1.5px solid #e0e9e5;border-radius:13px;background:#fbfdfc"><option value="">All statuses</option><option value="active" <?= ($filters['status'] ?? '')==='active'?'selected':'' ?>>Active</option><option value="suspended" <?= ($filters['status'] ?? '')==='suspended'?'selected':'' ?>>Suspended</option><option value="pending_activation" <?= ($filters['status'] ?? '')==='pending_activation'?'selected':'' ?>>Pending activation</option></select>
            <select name="login" onchange="this.form.requestSubmit()" aria-label="Filter by login activity" style="padding:11px;border:1.5px solid #e0e9e5;border-radius:13px;background:#fbfdfc"><option value="">Any login activity</option><option value="never" <?= ($filters['login'] ?? '')==='never'?'selected':'' ?>>Never logged in</option><option value="logged_in" <?= ($filters['login'] ?? '')==='logged_in'?'selected':'' ?>>Has logged in</option><option value="recent_30" <?= ($filters['login'] ?? '')==='recent_30'?'selected':'' ?>>Active in last 30 days</option></select>
            <select name="sort" onchange="this.form.requestSubmit()" aria-label="Sort users" style="padding:11px;border:1.5px solid #e0e9e5;border-radius:13px;background:#fbfdfc"><option value="name_asc" <?= ($filters['sort'] ?? '')==='name_asc'?'selected':'' ?>>Name A–Z</option><option value="newest" <?= ($filters['sort'] ?? '')==='newest'?'selected':'' ?>>Newest accounts</option><option value="last_login" <?= ($filters['sort'] ?? '')==='last_login'?'selected':'' ?>>Most recent login</option><option value="role" <?= ($filters['sort'] ?? '')==='role'?'selected':'' ?>>Group by role</option></select>
            <select name="per_page" onchange="this.form.requestSubmit()" aria-label="Results per page" style="padding:11px;border:1.5px solid #e0e9e5;border-radius:13px;background:#fbfdfc"><?php foreach([10,20,50] as $size): ?><option value="<?= $size ?>" <?= (int)$pagination['per_page']===$size?'selected':'' ?>><?= $size ?> per page</option><?php endforeach; ?></select>
        </div>
        <?php if($hasUserFilters): ?><div style="margin-top:13px;text-align:right"><a href="/staff/users" style="color:#e07d24;font-size:12.5px;font-weight:800">Reset filters</a></div><?php endif; ?>
    </form>

    <!-- Provision User Modal -->
    <div id="newUserModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(20,40,35,.45);backdrop-filter:blur(6px);z-index:99;align-items:center;justify-content:center;padding:20px">
        <div style="background:#fff;border-radius:24px;width:100%;max-width:500px;padding:32px;box-shadow:0 24px 60px -28px rgba(0,0,0,.4);animation:tnpop .25s ease">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
                <h3 style="margin:0;font-size:20px;font-weight:800">Provision New User Account</h3>
                <button onclick="document.getElementById('newUserModal').style.display='none'" style="background:none;border:none;font-size:24px;cursor:pointer;color:#8a9a94">&times;</button>
            </div>

            <form action="/staff/users/create" method="POST" data-ajax-form>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
                
                <div style="margin-bottom:16px">
                    <label style="display:block;font-size:13px;font-weight:700;color:#3a4d47;margin-bottom:6px">Full Name</label>
                    <input type="text" name="name" placeholder="e.g. Sarah Jenkins" required style="width:100%;padding:13px 16px;border:1.5px solid #e0e9e5;border-radius:14px;font-size:14px;background:#fbfdfc">
                </div>

                <div style="margin-bottom:16px">
                    <label style="display:block;font-size:13px;font-weight:700;color:#3a4d47;margin-bottom:6px">Email Address</label>
                    <input type="email" name="email" placeholder="new.user@example.invalid" required style="width:100%;padding:13px 16px;border:1.5px solid #e0e9e5;border-radius:14px;font-size:14px;background:#fbfdfc">
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

    <div id="userResetModal" style="display:none;position:fixed;inset:0;background:rgba(20,40,35,.45);backdrop-filter:blur(6px);z-index:99;align-items:center;justify-content:center;padding:20px">
        <div style="background:#fff;border-radius:24px;width:100%;max-width:460px;padding:32px;box-shadow:0 24px 60px -28px rgba(0,0,0,.4);animation:tnpop .25s ease">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px"><h3 style="margin:0;font-size:19px;font-weight:800">Reset User Password</h3><button type="button" onclick="document.getElementById('userResetModal').style.display='none'" style="border:0;background:none;font-size:24px;color:#8a9a94;cursor:pointer">&times;</button></div>
            <form action="/staff/users/reset-password" method="POST" data-ajax-form>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
                <input type="hidden" name="user_id" id="reset_user_id">
                <p style="font-size:14px;color:#61756e">Set a new password for <strong id="reset_user_name" style="color:#213330"></strong>. The user will receive a security notification.</p>
                <label style="display:block;font-size:13px;font-weight:700;margin:18px 0 6px">New password</label>
                <input type="password" name="new_password" minlength="8" autocomplete="new-password" required style="width:100%;padding:13px 16px;border:1.5px solid #e0e9e5;border-radius:14px;background:#fbfdfc">
                <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:22px"><button type="button" onclick="document.getElementById('userResetModal').style.display='none'" style="border:0;background:#f0f5f3;color:#5f726c;padding:11px 18px;border-radius:12px;font-weight:700">Cancel</button><button type="submit" data-loading-text="Updating…" style="border:0;background:#e07d24;color:#fff;padding:11px 20px;border-radius:12px;font-weight:700">Update Password</button></div>
            </form>
        </div>
    </div>

    <!-- Delete User Confirmation Modal -->
    <div id="deleteUserModal" style="display:none;position:fixed;inset:0;background:rgba(20,40,35,.45);backdrop-filter:blur(6px);z-index:199;align-items:center;justify-content:center;padding:20px">
        <div style="background:#fff;border-radius:24px;width:100%;max-width:440px;padding:32px;box-shadow:0 24px 60px -28px rgba(0,0,0,.4)">
            <h3 style="margin:0 0 12px;font-size:19px;font-weight:800">Delete User Account?</h3>
            <p style="color:#61756e;font-size:14px;margin:0 0 24px">This user account will be soft-deleted and moved to Trash.</p>
            <form action="/staff/users/delete" method="POST" data-ajax-form>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
                <input type="hidden" name="user_id" id="deleteUserId">
                <div style="display:flex;justify-content:flex-end;gap:12px">
                    <button type="button" onclick="document.getElementById('deleteUserModal').style.display='none'" style="background:#f0f5f3;color:#5f726c;border:none;padding:11px 20px;border-radius:12px;font-weight:700;cursor:pointer">Cancel</button>
                    <button type="submit" style="background:#dc2626;color:#fff;border:none;padding:11px 22px;border-radius:12px;font-weight:700;cursor:pointer">Delete User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Users List Table -->
    <div style="background:#fff;border-radius:24px;padding:26px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -24px rgba(16,54,45,.4)">
        <!-- Bulk Action Bar -->
        <div id="userBulkBar" style="display:none;align-items:center;gap:12px;padding:12px 16px;background:#fff8ee;border-radius:14px;margin-bottom:16px;border:1px solid #f6dfc0">
            <span id="userBulkCount" style="font-weight:700;color:#e07d24;font-size:13.5px">0 selected</span>
            <form action="/staff/users/bulk-delete" method="POST" data-ajax-form style="display:inline">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
                <div id="userBulkIds"></div>
                <button type="submit" onclick="return confirm('Delete all selected users? They can be restored from Trash.')" style="background:#dc2626;color:#fff;border:none;padding:8px 18px;border-radius:10px;font-weight:700;font-size:13px;cursor:pointer">Delete Selected</button>
            </form>
            <button type="button" onclick="tnUserSelectNone()" style="background:#f0f5f3;color:#5f726c;border:none;padding:8px 14px;border-radius:10px;font-weight:700;font-size:13px;cursor:pointer">Clear</button>
        </div>

        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;text-align:left">
                <thead>
                    <tr style="border-bottom:2px solid #eef4f1;color:#7d8e88;font-size:12.5px;text-transform:uppercase;letter-spacing:.05em">
                        <th style="padding:12px 10px;width:36px"><input type="checkbox" id="userCheckAll" aria-label="Select all users" onchange="tnUserToggleAll(this)" style="width:16px;height:16px;cursor:pointer"></th>
                        <th style="padding:12px 16px;font-weight:700">User Name</th>
                        <th style="padding:12px 16px;font-weight:700">Email</th>
                        <th style="padding:12px 16px;font-weight:700">Role</th>
                        <th style="padding:12px 16px;font-weight:700">Status</th>
                        <th style="padding:12px 16px;font-weight:700">Last Login</th>
                        <th style="padding:12px 16px;font-weight:700;text-align:right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($users)): ?><tr><td colspan="7" style="padding:42px;text-align:center;color:#7d8e88"><?= $hasUserFilters ? 'No users match the selected filters.' : 'No users found.' ?></td></tr><?php endif; ?>
                    <?php foreach ($users as $u): ?>
                        <tr style="border-bottom:1px solid #eef4f1">
                            <td style="padding:12px 10px"><input type="checkbox" class="tn-user-check" value="<?= (int)$u['id'] ?>" aria-label="Select user" onchange="tnUserUpdateBar()" style="width:16px;height:16px;cursor:pointer"></td>
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
                            <td style="padding:16px;white-space:nowrap;min-width:145px">
                                <?php if ($u['status'] === 'active'): ?>
                                    <span style="display:inline-flex;align-items:center;justify-content:center;white-space:nowrap;line-height:1;padding:7px 12px;border-radius:999px;font-size:11.5px;font-weight:700;background:#e2f3ea;color:#3f9d6d">Active</span>
                                <?php elseif ($u['status'] === 'pending_activation'): ?>
                                    <span style="display:inline-flex;align-items:center;justify-content:center;white-space:nowrap;line-height:1;padding:7px 12px;border-radius:999px;font-size:11.5px;font-weight:700;background:#e6ecf5;color:#41556f">Pending activation</span>
                                <?php else: ?>
                                    <span style="display:inline-flex;align-items:center;justify-content:center;white-space:nowrap;line-height:1;padding:7px 12px;border-radius:999px;font-size:11.5px;font-weight:700;background:#fdecdc;color:#e07d24">Suspended</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding:16px;color:#7d8e88;font-size:13px">
                                <?= $u['last_login_at'] ? date('d M Y, H:i', strtotime($u['last_login_at'])) : 'Never' ?>
                            </td>
                            <td style="padding:16px;text-align:right;min-width:170px">
                                <div style="display:flex;flex-direction:column;align-items:stretch;gap:8px;width:150px;margin-left:auto">
                                <?php if($u['status'] !== 'pending_activation'): ?>
                                    <button type="button" onclick="openUserReset(<?= (int)$u['id'] ?>, <?= htmlspecialchars(json_encode($u['name'], JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>)" style="width:100%;min-height:36px;background:#fff8ee;color:#e07d24;border:1px solid #f6dfc0;padding:8px 12px;border-radius:11px;font-weight:700;font-size:12px;cursor:pointer">Reset Password</button>
                                <?php else: ?>
                                    <form action="/staff/users/resend-activation" method="POST" data-ajax-form style="margin:0;width:100%">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                        <button type="submit" style="width:100%;min-height:36px;background:#eef4f1;color:#0d9488;border:1px solid #d7e7e2;padding:8px 12px;border-radius:11px;font-weight:700;font-size:12px;cursor:pointer">Resend Activation</button>
                                    </form>
                                <?php endif; ?>
                                <?php if(!empty($u['client_id'])): ?><a href="/staff/clients/<?= (int)$u['client_id'] ?>" style="display:flex;align-items:center;justify-content:center;width:100%;min-height:36px;background:#eef4f1;color:#0d9488;padding:8px 12px;border-radius:11px;font-weight:700;font-size:12px">Client Profile</a><?php endif; ?>
                                <?php if($u['status'] !== 'pending_activation'): ?><form action="/staff/users/toggle-status" method="POST" data-ajax-form style="margin:0;width:100%">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <input type="hidden" name="status" value="<?= $u['status'] === 'active' ? 'suspended' : 'active' ?>">
                                    <button type="submit" style="width:100%;min-height:36px;background:#f0f5f3;color:#5f726c;border:none;padding:8px 12px;border-radius:11px;font-weight:700;font-size:12.5px;cursor:pointer">
                                        <?= $u['status'] === 'active' ? 'Suspend' : 'Activate' ?>
                                    </button>
                                </form><?php endif; ?>
                                <button type="button" onclick="tnDeleteUser(<?= (int)$u['id'] ?>)" style="width:100%;min-height:36px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;padding:8px 12px;border-radius:11px;font-weight:700;font-size:12.5px;cursor:pointer">Delete</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if(($pagination['total'] ?? 0)>0): ?><?php $page=(int)$pagination['page'];$pages=(int)$pagination['total_pages'];$query=array_filter(['q'=>$filters['search'],'role'=>$filters['role'],'status'=>$filters['status'],'login'=>$filters['login'],'sort'=>$filters['sort'],'per_page'=>$pagination['per_page']],static fn($v)=>$v!==''&&$v!==0); ?><div style="display:flex;justify-content:space-between;align-items:center;padding-top:18px;margin-top:12px;border-top:1px solid #eef4f1;gap:12px;flex-wrap:wrap;color:#61756e;font-size:13px"><span>Showing <?= (($page-1)*$pagination['per_page'])+1 ?>–<?= min($pagination['total'],$page*$pagination['per_page']) ?> of <?= $pagination['total'] ?> users</span><div style="display:flex;gap:8px;align-items:center"><?php if($page>1): ?><a href="/staff/users?<?= http_build_query($query+['page'=>$page-1]) ?>" style="padding:8px 12px;border-radius:10px;background:#eef4f1;font-weight:700">Previous</a><?php endif; ?><strong>Page <?= $page ?> of <?= $pages ?></strong><?php if($page<$pages): ?><a href="/staff/users?<?= http_build_query($query+['page'=>$page+1]) ?>" style="padding:8px 12px;border-radius:10px;background:#eef4f1;font-weight:700">Next</a><?php endif; ?></div></div><?php endif; ?>
    </div>
</div>

<script>
function openUserReset(id, name) {
    document.getElementById('reset_user_id').value = id;
    document.getElementById('reset_user_name').textContent = name;
    document.getElementById('userResetModal').style.display = 'flex';
}
function tnDeleteUser(id) {
    document.getElementById('deleteUserId').value = id;
    document.getElementById('deleteUserModal').style.display = 'flex';
}
function tnUserToggleAll(el) {
    document.querySelectorAll('.tn-user-check').forEach(c => { c.checked = el.checked; });
    tnUserUpdateBar();
}
function tnUserSelectNone() {
    document.querySelectorAll('.tn-user-check, #userCheckAll').forEach(c => { c.checked = false; });
    tnUserUpdateBar();
}
function tnUserUpdateBar() {
    const checked = [...document.querySelectorAll('.tn-user-check:checked')];
    document.getElementById('userBulkCount').textContent = checked.length + ' selected';
    const container = document.getElementById('userBulkIds');
    container.innerHTML = '';
    checked.forEach(c => {
        const inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = 'ids[]'; inp.value = c.value;
        container.appendChild(inp);
    });
    document.getElementById('userBulkBar').style.display = checked.length > 0 ? 'flex' : 'none';
}
</script>
