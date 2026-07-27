<div class="tn-screen" style="max-width:1120px">
    <div id="toastSuccess" style="display:none;max-width:1120px;margin-bottom:16px;padding:14px 18px;background:#e2f3ea;color:#3f9d6d;border-radius:16px;font-weight:600;font-size:14px;box-shadow:0 4px 14px rgba(63,157,109,.15);animation:tnpop .3s ease"></div>

    <div class="tn-client-toolbar" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;flex-wrap:wrap;gap:16px">
        <div>
            <p style="margin:0;color:#61756e;font-size:14px">Manage practice client accounts, AML status, and entity profiles.</p>
        </div>
        <div class="tn-client-toolbar-actions" style="display:flex;align-items:center;gap:12px">
            <form action="/staff/clients" method="GET" data-ajax-form style="display:flex;align-items:center;gap:8px;margin:0">
                <input type="search" name="q" value="<?= htmlspecialchars($search ?? '') ?>" data-ajax-search placeholder="Search clients…" aria-label="Search clients" style="padding:11px 16px;border:1.5px solid #e0e9e5;border-radius:14px;font-size:14px;background:#fff;min-width:240px">
                <select name="per_page" onchange="this.form.requestSubmit()" aria-label="Results per page" style="padding:11px 12px;border:1.5px solid #e0e9e5;border-radius:14px;background:#fff;color:#61756e">
                    <?php foreach ([10, 20, 50] as $size): ?>
                        <option value="<?= $size ?>" <?= (int)($pagination['per_page'] ?? 10) === $size ? 'selected' : '' ?>><?= $size ?> / page</option>
                    <?php endforeach; ?>
                </select>
            </form>
            <button class="tn-create-client-button" onclick="document.getElementById('newClientModal').style.display='flex'" style="background:#0d9488;color:#fff;padding:12px 22px;border-radius:14px;font-weight:700;font-size:14px;cursor:pointer;border:none;box-shadow:0 8px 18px -8px rgba(13,148,136,.7)">+ Create Client Account</button>
        </div>
    </div>

    <!-- Create Client Modal -->
    <div id="newClientModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(20,40,35,.45);backdrop-filter:blur(6px);z-index:99;align-items:center;justify-content:center;padding:20px">
        <div style="background:#fff;border-radius:24px;width:100%;max-width:620px;max-height:92vh;overflow:auto;padding:32px;box-shadow:0 24px 60px -28px rgba(0,0,0,.4);animation:tnpop .25s ease">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
                <h3 style="margin:0;font-size:20px;font-weight:800">Create Client Account</h3>
                <button onclick="document.getElementById('newClientModal').style.display='none'" style="background:none;border:none;font-size:24px;cursor:pointer;color:#8a9a94">&times;</button>
            </div>

            <form action="/staff/clients/create" method="POST" data-ajax-form>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
                
                <div style="margin-bottom:16px">
                    <label style="display:block;font-size:13px;font-weight:700;color:#3a4d47;margin-bottom:6px">Client Contact Name</label>
                    <input type="text" name="name" placeholder="e.g. David Miller" required style="width:100%;padding:13px 16px;border:1.5px solid #e0e9e5;border-radius:14px;font-size:14px;background:#fbfdfc">
                </div>

                <div style="margin-bottom:16px">
                    <label style="display:block;font-size:13px;font-weight:700;color:#3a4d47;margin-bottom:6px">Email Address</label>
                    <input type="email" name="email" placeholder="new.client@example.invalid" required style="width:100%;padding:13px 16px;border:1.5px solid #e0e9e5;border-radius:14px;font-size:14px;background:#fbfdfc">
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

                <fieldset style="border:1px solid #e0e9e5;border-radius:16px;padding:16px;margin:0 0 20px">
                    <legend style="padding:0 8px;font-size:13px;font-weight:800;color:#41556f">Initial linked entity (optional)</legend>
                    <input name="entity_name" placeholder="Entity name" style="width:100%;padding:11px;margin-bottom:9px;border:1px solid #e0e9e5;border-radius:11px">
                    <input name="entity_type" list="entityTypes" placeholder="Entity type, e.g. Limited Company" style="width:100%;padding:11px;margin-bottom:9px;border:1px solid #e0e9e5;border-radius:11px">
                    <select name="entity_scope" style="width:100%;padding:11px;margin-bottom:9px;border:1px solid #e0e9e5;border-radius:11px"><option value="company">Shared company record</option><option value="personal">Private personal record</option></select>
                    <datalist id="entityTypes"><option value="Limited Company"><option value="Personal Tax Return"><option value="Sole Trader"><option value="Partnership"><option value="Other"></datalist>
                    <div data-entity-fields style="display:grid;grid-template-columns:1fr 1fr;gap:9px"><input name="company_number" placeholder="Company number"><input name="vat_number" placeholder="VAT number"><input name="ct_utr" placeholder="Corporation Tax UTR"><input name="personal_utr" placeholder="Personal UTR"><input type="date" name="accounting_year_end" title="Accounting year end"><input name="tax_year" placeholder="Tax year, e.g. 2026/27"></div>
                    <div style="margin-top:12px;font-size:12px;font-weight:800;color:#61756e">Important dates for this entity</div>
                    <?php foreach ([['Accounts Due',''],['Corporation Tax Due',''],['Next VAT Return Due','']] as $row): ?><div data-deadline-row style="display:grid;grid-template-columns:1fr 145px;gap:8px;margin-top:8px"><input name="deadline_type[]" value="<?= $row[0] ?>" placeholder="Date type"><input type="date" name="deadline_due_date[]"></div><?php endforeach; ?>
                </fieldset>

                <div style="display:flex;justify-content:flex-end;gap:12px">
                    <button type="button" onclick="document.getElementById('newClientModal').style.display='none'" style="background:#f0f5f3;color:#5f726c;border:none;padding:12px 20px;border-radius:12px;font-weight:700;font-size:14px;cursor:pointer">Cancel</button>
                    <button type="submit" style="background:#0d9488;color:#fff;border:none;padding:12px 24px;border-radius:12px;font-weight:700;font-size:14px;cursor:pointer">Create Client Account</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Clients Table -->
    <div class="tn-client-list" style="background:#fff;border-radius:24px;padding:12px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -24px rgba(16,54,45,.4)">
        <table id="clientsTable" style="width:100%;border-collapse:collapse;text-align:left">
            <thead>
                <tr style="border-bottom:1px solid rgba(20,60,50,.08);color:#8a9a94;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.05em">
                    <th style="padding:14px 16px">Client Name</th>
                    <th style="padding:14px 16px">Email</th>
                    <th style="padding:14px 16px">Phone</th>
                    <th style="padding:14px 16px">AML Status</th>
                    <th style="padding:14px 16px;text-align:right">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($clients)): ?>
                    <tr class="tn-client-empty"><td colspan="5" style="padding:42px 16px;text-align:center;color:#7d8e88">No clients match the current search.</td></tr>
                <?php endif; ?>
                <?php foreach ($clients as $c): ?>
                    <tr class="client-row" id="client-row-<?= $c['id'] ?>" style="border-bottom:1px solid rgba(20,60,50,.06);transition:all .3s ease">
                        <td data-label="Client" style="padding:16px;font-weight:700;font-size:15px" class="client-name"><?= htmlspecialchars($c['name']) ?></td>
                        <td data-label="Email" style="padding:16px;color:#61756e;font-size:14px" class="client-email"><?= htmlspecialchars($c['email']) ?></td>
                        <td data-label="Phone" style="padding:16px;color:#61756e;font-size:14px"><?= htmlspecialchars($c['phone'] ?? '—') ?></td>
                        <td data-label="AML Status" style="padding:16px">
                            <span style="font-size:11.5px;font-weight:700;padding:5px 12px;border-radius:999px;white-space:nowrap;display:inline-block;<?= $c['aml_status'] === 'Complete' ? 'background:#e2f3ea;color:#3f9d6d;' : 'background:#fdecdc;color:#e07d24;' ?>">
                                <?= htmlspecialchars($c['aml_status']) ?>
                            </span>
                        </td>
                        <td data-label="Action" style="padding:16px;text-align:right">
                            <div style="display:inline-flex;align-items:center;gap:10px">
                                <a href="/staff/clients/<?= $c['id'] ?>" style="font-weight:700;font-size:13px;color:#0d9488;background:#eef4f1;padding:7px 13px;border-radius:10px">View Profile &rarr;</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (($pagination['total'] ?? 0) > 0): ?>
            <?php
            $currentPage = (int) $pagination['page'];
            $totalPages = (int) $pagination['total_pages'];
            $queryBase = ['q' => $search ?? '', 'per_page' => $pagination['per_page']];
            ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px 8px;gap:12px;flex-wrap:wrap;color:#61756e;font-size:13px">
                <span>Showing <?= (($currentPage - 1) * $pagination['per_page']) + 1 ?>–<?= min($pagination['total'], $currentPage * $pagination['per_page']) ?> of <?= $pagination['total'] ?> clients</span>
                <div style="display:flex;gap:8px;align-items:center">
                    <?php if ($currentPage > 1): ?>
                        <a href="/staff/clients?<?= http_build_query($queryBase + ['page' => $currentPage - 1]) ?>" style="padding:8px 12px;border-radius:10px;background:#eef4f1;color:#0d9488;font-weight:700">Previous</a>
                    <?php endif; ?>
                    <span style="padding:8px 10px;font-weight:700">Page <?= $currentPage ?> of <?= $totalPages ?></span>
                    <?php if ($currentPage < $totalPages): ?>
                        <a href="/staff/clients?<?= http_build_query($queryBase + ['page' => $currentPage + 1]) ?>" style="padding:8px 12px;border-radius:10px;background:#eef4f1;color:#0d9488;font-weight:700">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
