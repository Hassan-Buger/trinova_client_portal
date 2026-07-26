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
            <button onclick="document.getElementById('addEntityModal').style.display='flex'" style="background:#41556f;color:#fff;padding:11px 20px;border-radius:14px;font-weight:700;font-size:13.5px;cursor:pointer;border:none;box-shadow:0 8px 18px -8px rgba(65,85,111,.7)">+ Link Business Entity</button>
            <details style="position:relative">
                <summary style="list-style:none;background:#f0f5f3;color:#61756e;padding:11px 16px;border-radius:14px;font-weight:700;font-size:13px;cursor:pointer">Account actions</summary>
                <div style="position:absolute;right:0;top:48px;background:#fff;border:1px solid #e0e9e5;border-radius:14px;padding:10px;min-width:190px;z-index:10;box-shadow:0 16px 34px -18px rgba(16,54,45,.45)">
                    <a href="/staff/users?q=<?= urlencode($client['email']) ?>" style="display:block;padding:9px 10px;font-size:13px;font-weight:700;color:#0d9488">Manage portal user</a>
                    <button onclick="document.getElementById('deleteClientModal').style.display='flex'" style="width:100%;text-align:left;background:none;color:#dc2626;border:0;padding:9px 10px;font-weight:700;font-size:13px;cursor:pointer">Permanently delete client</button>
                </div>
            </details>
        </div>
    </div>

    <!-- Delete Client Modal -->
    <div id="deleteClientModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(20,40,35,.45);backdrop-filter:blur(6px);z-index:99;align-items:center;justify-content:center;padding:20px">
        <div style="background:#fff;border-radius:24px;width:100%;max-width:460px;padding:32px;box-shadow:0 24px 60px -28px rgba(0,0,0,.4);animation:tnpop .25s ease">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
                <h3 style="margin:0;font-size:19px;font-weight:800;color:#dc2626">Delete Client Account</h3>
                <button onclick="document.getElementById('deleteClientModal').style.display='none'" style="background:none;border:none;font-size:24px;cursor:pointer;color:#8a9a94">&times;</button>
            </div>

            <form action="/staff/clients/delete" method="POST" data-ajax-form>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
                <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
                <input type="hidden" name="is_ajax" value="1">
                
                <p style="font-size:14px;color:#61756e;line-height:1.5;margin-top:0">
                    Are you sure you want to delete <strong style="color:#213330"><?= htmlspecialchars($client['name']) ?></strong>? This will permanently remove their profile, files, messages, and login access.
                </p>
                <label style="display:block;font-size:12.5px;font-weight:700;color:#3a4d47;margin-top:18px">Type DELETE to confirm</label>
                <input type="text" name="confirm_delete" pattern="DELETE" required autocomplete="off" style="width:100%;padding:11px 14px;margin-top:6px;border:1.5px solid #fca5a5;border-radius:12px;background:#fffafa">

                <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:24px">
                    <button type="button" onclick="document.getElementById('deleteClientModal').style.display='none'" style="background:#f0f5f3;color:#5f726c;border:none;padding:12px 20px;border-radius:12px;font-weight:700;font-size:14px;cursor:pointer">Cancel</button>
                    <button type="submit" data-loading-text="Deleting…" style="background:#dc2626;color:#fff;border:none;padding:12px 24px;border-radius:12px;font-weight:700;font-size:14px;cursor:pointer">Delete Permanently</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Entity Modal -->
    <div id="addEntityModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(20,40,35,.45);backdrop-filter:blur(6px);z-index:99;align-items:center;justify-content:center;padding:20px">
        <div style="background:#fff;border-radius:24px;width:100%;max-width:680px;max-height:92vh;overflow:auto;padding:32px;box-shadow:0 24px 60px -28px rgba(0,0,0,.4);animation:tnpop .25s ease">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
                <h3 style="margin:0;font-size:19px;font-weight:800">Link Business Entity</h3>
                <button onclick="document.getElementById('addEntityModal').style.display='none'" style="background:none;border:none;font-size:24px;cursor:pointer;color:#8a9a94">&times;</button>
            </div>

            <form action="/staff/clients/add-entity" method="POST" data-ajax-form>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
                <input type="hidden" name="client_id" value="<?= $client['id'] ?>">

                <div style="margin-bottom:16px">
                    <label style="display:block;font-size:13px;font-weight:700;color:#3a4d47;margin-bottom:6px">Entity Name</label>
                    <input type="text" name="entity_name" placeholder="e.g. Example Services Ltd" required style="width:100%;padding:13px 16px;border:1.5px solid #e0e9e5;border-radius:14px;font-size:14px;background:#fbfdfc">
                </div>
                <input name="entity_type" list="profileEntityTypes" placeholder="Entity type" required style="width:100%;padding:13px;margin-bottom:12px;border:1px solid #e0e9e5;border-radius:12px"><datalist id="profileEntityTypes"><option value="Limited Company"><option value="Personal Tax Return"><option value="Sole Trader"><option value="Partnership"><option value="Other"></datalist>
                <div data-entity-fields style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:15px"><input name="company_number" placeholder="Company number"><input name="vat_number" placeholder="VAT registration number"><input name="ct_utr" placeholder="Corporation Tax UTR"><input name="personal_utr" placeholder="Personal UTR"><input type="date" name="accounting_year_end" title="Accounting year end"><input name="tax_year" placeholder="Tax year"><input name="custom_attribute_label" placeholder="Other reference label"><input name="custom_attribute_value" placeholder="Other reference value"></div>
                <div style="font-size:13px;font-weight:800;color:#41556f">Important dates</div>
                <?php foreach (["Accounts Due","Corporation Tax Due","Next VAT Return Due"] as $suggestion): ?><div data-deadline-row style="display:grid;grid-template-columns:1fr 160px;gap:9px;margin-top:8px"><input name="deadline_type[]" value="<?= $suggestion ?>"><input type="date" name="deadline_due_date[]"></div><?php endforeach; ?>

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
	                    <h3 style="margin:0 0 14px;font-size:15px;font-weight:700">Linked Business Entities</h3>
                    <?php if (empty($entities)): ?>
                        <p style="color:#8a9a94;font-size:13px">No linked businesses.</p>
                    <?php else: ?>
                        <?php foreach ($entities as $b): ?>
	                            <div style="padding:13px 0;border-bottom:1px solid rgba(20,60,50,.07)"><div style="display:flex;justify-content:space-between;gap:10px"><strong><?= htmlspecialchars($b['company_name']) ?></strong><span style="font-size:11px;font-weight:700;background:#eef4f1;padding:4px 9px;border-radius:999px"><?= htmlspecialchars($b['entity_type']) ?></span></div><?php if($b['company_number']): ?><div style="font-size:12px;color:#61756e;margin-top:5px">Company no: <?= htmlspecialchars($b['company_number']) ?></div><?php endif; ?><?php if($b['tax_reference']): ?><div style="font-size:12px;color:#61756e">Tax ref: <?= htmlspecialchars($b['tax_reference']) ?></div><?php endif; ?><?php foreach($b['attributes'] as $attribute): ?><div style="font-size:12px;color:#61756e"><?= htmlspecialchars($attribute['label'] ?? '') ?>: <strong><?= htmlspecialchars($attribute['value'] ?? '') ?></strong></div><?php endforeach; ?></div>
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

            <div style="background:#fff;border-radius:22px;padding:22px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 12px 30px -24px rgba(16,54,45,.4)"><h3 style="margin:0 0 14px;font-size:15px">Important Dates by Entity</h3><?php if(empty($deadlineGroups)): ?><p style="color:#8a9a94;font-size:13px">Link an entity before adding important dates.</p><?php endif; ?><?php foreach($deadlineGroups as $group): ?><section style="border-top:1px solid #eef4f1;padding-top:13px;margin-top:13px"><div style="display:flex;justify-content:space-between"><strong><?= htmlspecialchars($group['entity_name']) ?></strong><span style="font-size:11px;color:#61756e"><?= htmlspecialchars($group['entity_type']) ?></span></div><?php if(empty($group['deadlines'])): ?><p style="font-size:12px;color:#8a9a94">No dates recorded for this entity.</p><?php endif; ?><?php foreach($group['deadlines'] as $deadline): ?><div style="display:flex;justify-content:space-between;gap:12px;padding:8px 0;font-size:13px"><span><?= htmlspecialchars($deadline['type']) ?></span><strong><?= date('d M Y',strtotime($deadline['due_date'])) ?></strong></div><?php endforeach; ?><form action="/staff/deadlines/create" method="POST" data-ajax-form style="display:grid;grid-template-columns:1fr 145px auto;gap:8px;margin-top:10px"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>"><input type="hidden" name="client_id" value="<?= (int)$client['id'] ?>"><input type="hidden" name="entity_id" value="<?= (int)$group['entity_id'] ?>"><input type="hidden" name="return_to" value="/staff/clients/<?= (int)$client['id'] ?>"><input name="type" placeholder="Date type" required><input type="date" name="due_date" required><button style="border:0;border-radius:10px;background:#0d9488;color:#fff;font-weight:700;padding:0 12px">Add</button></form></section><?php endforeach; ?></div>

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
