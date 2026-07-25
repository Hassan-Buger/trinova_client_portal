<div class="tn-screen" style="max-width:1120px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px">
        <div>
            <h2 style="margin:0 0 6px;font-size:26px;font-weight:800;letter-spacing:-.02em">Practice Compliance Deadlines</h2>
            <p style="margin:0;color:#61756e;font-size:14.5px">Monitor and update statutory tax deadlines for all practice clients.</p>
        </div>
        <button onclick="document.getElementById('newDeadlineModal').style.display='flex'" style="background:#41556f;color:#fff;padding:12px 22px;border-radius:14px;font-weight:700;font-size:14px;cursor:pointer;border:none;box-shadow:0 8px 18px -8px rgba(65,85,111,.7)">+ Add Deadline</button>
    </div>

    <!-- New Deadline Modal -->
    <div id="newDeadlineModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(20,40,35,.45);backdrop-filter:blur(6px);z-index:99;align-items:center;justify-content:center;padding:20px">
        <div style="background:#fff;border-radius:24px;width:100%;max-width:500px;padding:32px;box-shadow:0 24px 60px -28px rgba(0,0,0,.4);animation:tnpop .25s ease">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
                <h3 style="margin:0;font-size:20px;font-weight:800">Add Compliance Deadline</h3>
                <button onclick="document.getElementById('newDeadlineModal').style.display='none'" style="background:none;border:none;font-size:24px;cursor:pointer;color:#8a9a94">&times;</button>
            </div>

            <form action="/staff/deadlines/create" method="POST" data-ajax-form>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
                
                <div style="margin-bottom:16px">
                    <label style="display:block;font-size:13px;font-weight:700;color:#3a4d47;margin-bottom:6px">Client</label>
                    <select name="client_id" required style="width:100%;padding:13px 16px;border:1.5px solid #e0e9e5;border-radius:14px;font-size:14px;background:#fbfdfc">
                        <option value="">-- Select Client --</option>
                        <?php foreach ($clients as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['email']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="margin-bottom:16px">
                    <label style="display:block;font-size:13px;font-weight:700;color:#3a4d47;margin-bottom:6px">Deadline Type</label>
                    <select name="type" required style="width:100%;padding:13px 16px;border:1.5px solid #e0e9e5;border-radius:14px;font-size:14px;background:#fbfdfc">
                        <option value="VAT">VAT Return</option>
                        <option value="Payroll">Payroll</option>
                        <option value="Accounts">Annual Accounts</option>
                        <option value="Corporation Tax">Corporation Tax</option>
                        <option value="Self Assessment">Self Assessment</option>
                        <option value="Confirmation Statement">Confirmation Statement</option>
                    </select>
                </div>

                <div style="margin-bottom:24px">
                    <label style="display:block;font-size:13px;font-weight:700;color:#3a4d47;margin-bottom:6px">Statutory Due Date</label>
                    <input type="date" name="due_date" required style="width:100%;padding:13px 16px;border:1.5px solid #e0e9e5;border-radius:14px;font-size:14px;background:#fbfdfc">
                </div>

                <div style="display:flex;justify-content:flex-end;gap:12px">
                    <button type="button" onclick="document.getElementById('newDeadlineModal').style.display='none'" style="background:#f0f5f3;color:#5f726c;border:none;padding:12px 20px;border-radius:12px;font-weight:700;font-size:14px;cursor:pointer">Cancel</button>
                    <button type="submit" style="background:#0d9488;color:#fff;border:none;padding:12px 24px;border-radius:12px;font-weight:700;font-size:14px;cursor:pointer">Save Deadline</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Deadlines Table -->
    <div style="background:#fff;border-radius:24px;padding:26px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -24px rgba(16,54,45,.4)">
        <?php if (empty($deadlines)): ?>
            <div style="padding:48px 24px;text-align:center;color:#8a9a94">No deadlines logged in matrix.</div>
        <?php else: ?>
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;text-align:left">
                    <thead>
                        <tr style="border-bottom:2px solid #eef4f1;color:#7d8e88;font-size:12.5px;text-transform:uppercase;letter-spacing:.05em">
                            <th style="padding:12px 16px;font-weight:700">Client</th>
                            <th style="padding:12px 16px;font-weight:700">Type</th>
                            <th style="padding:12px 16px;font-weight:700">Due Date</th>
                            <th style="padding:12px 16px;font-weight:700">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deadlines as $d): ?>
                            <tr style="border-bottom:1px solid #eef4f1">
                                <td style="padding:16px;font-weight:700;color:#213330">
                                    <?= htmlspecialchars($d['client_name'] ?? 'Client') ?>
                                </td>
                                <td style="padding:16px;font-weight:700;color:#41556f">
                                    <?= htmlspecialchars($d['type']) ?>
                                </td>
                                <td style="padding:16px;color:#213330;font-weight:600">
                                    <?= date('d M Y', strtotime($d['due_date'])) ?>
                                </td>
                                <td style="padding:16px">
                                    <form action="/staff/deadlines/update-status" method="POST" data-ajax-form style="margin:0;display:inline-block">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
                                        <input type="hidden" name="deadline_id" value="<?= $d['id'] ?>">
                                        <select name="status" onchange="this.form.submit()" style="padding:6px 12px;border-radius:10px;font-size:12.5px;font-weight:700;border:1px solid #e0e9e5;background:#fbfdfc;cursor:pointer">
                                            <option value="Pending" <?= $d['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="Overdue" <?= $d['status'] === 'Overdue' ? 'selected' : '' ?>>Overdue</option>
                                            <option value="Completed" <?= $d['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
