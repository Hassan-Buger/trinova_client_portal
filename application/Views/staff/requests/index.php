<div class="tn-screen" style="max-width:1120px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px">
        <div>
            <h2 style="margin:0 0 6px;font-size:26px;font-weight:800;letter-spacing:-.02em">Document Requests Console</h2>
            <p style="margin:0;color:#61756e;font-size:14.5px">Issue new requests to client accounts and manage status lifecycles.</p>
        </div>
        <button onclick="document.getElementById('newReqModal').style.display='flex'" style="background:#41556f;color:#fff;padding:12px 22px;border-radius:14px;font-weight:700;font-size:14px;cursor:pointer;border:none;box-shadow:0 8px 18px -8px rgba(65,85,111,.7)">+ Issue Document Request</button>
    </div>

    <!-- Issue Request Modal -->
    <div id="newReqModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(20,40,35,.45);backdrop-filter:blur(6px);z-index:99;align-items:center;justify-content:center;padding:20px">
        <div style="background:#fff;border-radius:24px;width:100%;max-width:540px;padding:32px;box-shadow:0 24px 60px -28px rgba(0,0,0,.4);animation:tnpop .25s ease">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
                <h3 style="margin:0;font-size:20px;font-weight:800">Issue New Document Request</h3>
                <button onclick="document.getElementById('newReqModal').style.display='none'" style="background:none;border:none;font-size:24px;cursor:pointer;color:#8a9a94">&times;</button>
            </div>

            <form action="/staff/requests/create" method="POST" data-ajax-form>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
                
                <div style="margin-bottom:16px">
                    <label style="display:block;font-size:13px;font-weight:700;color:#3a4d47;margin-bottom:6px">Client Account</label>
                    <select name="client_id" required style="width:100%;padding:13px 16px;border:1.5px solid #e0e9e5;border-radius:14px;font-size:14px;background:#fbfdfc">
                        <option value="">-- Select Client --</option>
                        <?php foreach ($clients as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['email']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="margin-bottom:16px">
                    <label style="display:block;font-size:13px;font-weight:700;color:#3a4d47;margin-bottom:6px">Request Title</label>
                    <input type="text" name="title" placeholder="e.g. Q2 Bank Statements & Receipts" required style="width:100%;padding:13px 16px;border:1.5px solid #e0e9e5;border-radius:14px;font-size:14px;background:#fbfdfc">
                </div>

                <div style="margin-bottom:16px">
                    <label style="display:block;font-size:13px;font-weight:700;color:#3a4d47;margin-bottom:6px">Due Date</label>
                    <input type="date" name="due_date" required style="width:100%;padding:13px 16px;border:1.5px solid #e0e9e5;border-radius:14px;font-size:14px;background:#fbfdfc">
                </div>

                <div style="margin-bottom:24px">
                    <label style="display:block;font-size:13px;font-weight:700;color:#3a4d47;margin-bottom:6px">Detailed Description / Instructions</label>
                    <textarea name="description" rows="3" placeholder="Please upload June statements in PDF format" style="width:100%;padding:13px 16px;border:1.5px solid #e0e9e5;border-radius:14px;font-size:14px;background:#fbfdfc;resize:vertical"></textarea>
                </div>

                <div style="display:flex;justify-content:flex-end;gap:12px">
                    <button type="button" onclick="document.getElementById('newReqModal').style.display='none'" style="background:#f0f5f3;color:#5f726c;border:none;padding:12px 20px;border-radius:12px;font-weight:700;font-size:14px;cursor:pointer">Cancel</button>
                    <button type="submit" style="background:#0d9488;color:#fff;border:none;padding:12px 24px;border-radius:12px;font-weight:700;font-size:14px;cursor:pointer">Issue Request</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Requests List Table -->
    <div style="background:#fff;border-radius:24px;padding:26px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -24px rgba(16,54,45,.4)">
        <?php if (empty($requests)): ?>
            <div style="padding:48px 24px;text-align:center;color:#8a9a94">No document requests active.</div>
        <?php else: ?>
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;text-align:left">
                    <thead>
                        <tr style="border-bottom:2px solid #eef4f1;color:#7d8e88;font-size:12.5px;text-transform:uppercase;letter-spacing:.05em">
                            <th style="padding:12px 16px;font-weight:700">Client</th>
                            <th style="padding:12px 16px;font-weight:700">Title & Instructions</th>
                            <th style="padding:12px 16px;font-weight:700">Issued By</th>
                            <th style="padding:12px 16px;font-weight:700">Due Date</th>
                            <th style="padding:12px 16px;font-weight:700">Lifecycle Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                            <tr style="border-bottom:1px solid #eef4f1">
                                <td style="padding:16px;font-weight:700;color:#213330">
                                    <?= htmlspecialchars($req['client_name'] ?? 'Client') ?>
                                </td>
                                <td style="padding:16px">
                                    <div style="font-weight:700;color:#213330"><?= htmlspecialchars($req['title']) ?></div>
                                    <?php if ($req['description']): ?>
                                        <div style="font-size:12.5px;color:#7d8e88;margin-top:2px"><?= htmlspecialchars($req['description']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td style="padding:16px;color:#61756e;font-size:13.5px">
                                    <?= htmlspecialchars($req['created_by_name'] ?? 'Staff') ?>
                                </td>
                                <td style="padding:16px;color:#7d8e88;font-size:13px">
                                    <?= date('d M Y', strtotime($req['due_date'])) ?>
                                </td>
                                <td style="padding:16px">
                                    <form action="/staff/requests/update-status" method="POST" data-ajax-form style="margin:0;display:inline-block">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
                                        <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                        <select name="status" onchange="this.form.submit()" style="padding:6px 12px;border-radius:10px;font-size:12.5px;font-weight:700;border:1px solid #e0e9e5;background:#fbfdfc;cursor:pointer">
                                            <option value="Awaiting Client" <?= $req['status'] === 'Awaiting Client' ? 'selected' : '' ?>>Awaiting Client</option>
                                            <option value="Uploaded" <?= $req['status'] === 'Uploaded' ? 'selected' : '' ?>>Uploaded</option>
                                            <option value="Under Review" <?= $req['status'] === 'Under Review' ? 'selected' : '' ?>>Under Review</option>
                                            <option value="Completed" <?= $req['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
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
