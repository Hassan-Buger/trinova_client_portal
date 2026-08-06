<div class="tn-screen" style="max-width:1120px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px">
        <div>
            <p style="margin:0;color:#61756e;font-size:14.5px">Issue new requests to client accounts and manage status lifecycles.</p>
        </div>
        <button onclick="document.getElementById('newReqModal').style.display='flex'" style="background:#41556f;color:#fff;padding:12px 22px;border-radius:14px;font-weight:700;font-size:14px;cursor:pointer;border:none;box-shadow:0 8px 18px -8px rgba(65,85,111,.7)">+ Issue Document Request</button>
    </div>

    <?php
    $hasRequestFilters = ($filters['search'] ?? '') !== '' || !empty($filters['client_id']) || !empty($filters['created_by']) || ($filters['status'] ?? '') !== '' || ($filters['due_from'] ?? '') !== '' || ($filters['due_to'] ?? '') !== '' || ($filters['timing'] ?? '') !== '';
    ?>
    <form action="/staff/requests" method="GET" data-ajax-form style="background:#fff;border-radius:22px;padding:18px;margin-bottom:20px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -24px rgba(16,54,45,.3)">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px">
            <input type="search" name="q" value="<?= htmlspecialchars($filters['search'] ?? '') ?>" data-ajax-search placeholder="Search title, instructions, client…" aria-label="Search requests" style="padding:11px 14px;border:1.5px solid #e0e9e5;border-radius:13px;background:#fbfdfc">
            <select name="client_id" onchange="this.form.requestSubmit()" aria-label="Filter by client" style="padding:11px;border:1.5px solid #e0e9e5;border-radius:13px;background:#fbfdfc"><option value="">All clients</option><?php foreach ($clients as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)($filters['client_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select>
            <select name="status" onchange="this.form.requestSubmit()" aria-label="Filter by status" style="padding:11px;border:1.5px solid #e0e9e5;border-radius:13px;background:#fbfdfc"><option value="">All statuses</option><?php foreach ($statuses as $status): ?><option value="<?= htmlspecialchars($status) ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>><?= htmlspecialchars($status) ?></option><?php endforeach; ?></select>
            <select name="created_by" onchange="this.form.requestSubmit()" aria-label="Filter by creator" style="padding:11px;border:1.5px solid #e0e9e5;border-radius:13px;background:#fbfdfc"><option value="">All staff creators</option><?php foreach ($staff as $member): ?><option value="<?= (int)$member['id'] ?>" <?= (int)($filters['created_by'] ?? 0) === (int)$member['id'] ? 'selected' : '' ?>><?= htmlspecialchars($member['name']) ?></option><?php endforeach; ?></select>
            <select name="timing" onchange="this.form.requestSubmit()" aria-label="Filter by timing" style="padding:11px;border:1.5px solid #e0e9e5;border-radius:13px;background:#fbfdfc"><option value="">Any due timing</option><option value="overdue" <?= ($filters['timing'] ?? '') === 'overdue' ? 'selected' : '' ?>>Overdue only</option><option value="upcoming" <?= ($filters['timing'] ?? '') === 'upcoming' ? 'selected' : '' ?>>Upcoming only</option></select>
            <input type="date" name="due_from" value="<?= htmlspecialchars($filters['due_from'] ?? '') ?>" onchange="this.form.requestSubmit()" aria-label="Due from" title="Due from" style="padding:10px;border:1.5px solid #e0e9e5;border-radius:13px;background:#fbfdfc">
            <input type="date" name="due_to" value="<?= htmlspecialchars($filters['due_to'] ?? '') ?>" onchange="this.form.requestSubmit()" aria-label="Due to" title="Due to" style="padding:10px;border:1.5px solid #e0e9e5;border-radius:13px;background:#fbfdfc">
            <div style="display:flex;gap:8px"><select name="sort" onchange="this.form.requestSubmit()" aria-label="Sort requests" style="flex:1;padding:11px;border:1.5px solid #e0e9e5;border-radius:13px;background:#fbfdfc"><option value="due_asc" <?= ($filters['sort'] ?? '') === 'due_asc' ? 'selected' : '' ?>>Due soonest</option><option value="due_desc" <?= ($filters['sort'] ?? '') === 'due_desc' ? 'selected' : '' ?>>Due latest</option><option value="newest" <?= ($filters['sort'] ?? '') === 'newest' ? 'selected' : '' ?>>Newest issued</option><option value="client_asc" <?= ($filters['sort'] ?? '') === 'client_asc' ? 'selected' : '' ?>>Client A–Z</option></select><select name="per_page" onchange="this.form.requestSubmit()" aria-label="Results per page" style="width:100px;padding:11px 8px;border:1.5px solid #e0e9e5;border-radius:13px;background:#fbfdfc"><?php foreach ([10,20,50] as $size): ?><option value="<?= $size ?>" <?= (int)$pagination['per_page'] === $size ? 'selected' : '' ?>><?= $size ?> / page</option><?php endforeach; ?></select></div>
        </div>
        <?php if ($hasRequestFilters): ?><div style="margin-top:13px;text-align:right"><a href="/staff/requests" style="color:#e07d24;font-size:12.5px;font-weight:800">Reset filters</a></div><?php endif; ?>
    </form>

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
                    <label style="display:block;font-size:13px;font-weight:700;color:#3a4d47;margin-bottom:6px">Company or personal record</label>
                    <select name="entity_id" required style="width:100%;padding:13px 16px;border:1.5px solid #e0e9e5;border-radius:14px;font-size:14px;background:#fbfdfc">
                        <option value="">-- Select record --</option>
                        <?php foreach ($entities as $entity): ?>
                            <option value="<?= (int)$entity['id'] ?>"><?= htmlspecialchars($entity['company_name']) ?> — <?= htmlspecialchars(ucfirst($entity['entity_scope'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="margin-bottom:16px">
                    <label style="display:block;font-size:13px;font-weight:700;color:#3a4d47;margin-bottom:6px">Request Title</label>
                    <input type="text" name="title" placeholder="e.g. Q2 Bank Statements & Receipts" required style="width:100%;padding:13px 16px;border:1.5px solid #e0e9e5;border-radius:14px;font-size:14px;background:#fbfdfc">
                </div>

                <div style="margin-bottom:16px">
                    <label style="display:block;font-size:13px;font-weight:700;color:#3a4d47;margin-bottom:6px">Due Date</label>
                    <input type="date" name="due_date" min="<?= date('Y-m-d') ?>" required style="width:100%;padding:13px 16px;border:1.5px solid #e0e9e5;border-radius:14px;font-size:14px;background:#fbfdfc">
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

    <!-- Delete Request Confirmation Modal -->
    <div id="deleteReqModal" style="display:none;position:fixed;inset:0;background:rgba(20,40,35,.45);backdrop-filter:blur(6px);z-index:199;align-items:center;justify-content:center;padding:20px">
        <div style="background:#fff;border-radius:24px;width:100%;max-width:440px;padding:32px;box-shadow:0 24px 60px -28px rgba(0,0,0,.4);animation:tnpop .25s ease">
            <h3 style="margin:0 0 12px;font-size:19px;font-weight:800">Delete Request?</h3>
            <p style="color:#61756e;font-size:14px;margin:0 0 24px">This document request will be soft-deleted and can be restored from Trash.</p>
            <form action="/staff/requests/delete" method="POST" data-ajax-form>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
                <input type="hidden" name="request_id" id="deleteReqId">
                <div style="display:flex;justify-content:flex-end;gap:12px">
                    <button type="button" onclick="document.getElementById('deleteReqModal').style.display='none'" style="background:#f0f5f3;color:#5f726c;border:none;padding:11px 20px;border-radius:12px;font-weight:700;cursor:pointer">Cancel</button>
                    <button type="submit" style="background:#dc2626;color:#fff;border:none;padding:11px 22px;border-radius:12px;font-weight:700;cursor:pointer">Delete</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Requests List Table -->
    <div style="background:#fff;border-radius:24px;padding:26px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -24px rgba(16,54,45,.4)">
        <!-- Bulk Action Bar -->
        <div id="reqBulkBar" style="display:none;align-items:center;gap:12px;padding:12px 16px;background:#fff8ee;border-radius:14px;margin-bottom:16px;border:1px solid #f6dfc0">
            <span id="reqBulkCount" style="font-weight:700;color:#e07d24;font-size:13.5px">0 selected</span>
            <form action="/staff/requests/bulk-delete" method="POST" data-ajax-form style="display:inline">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
                <div id="reqBulkIds"></div>
                <button type="submit" onclick="return confirm('Delete all selected requests? They can be restored from Trash.')" style="background:#dc2626;color:#fff;border:none;padding:8px 18px;border-radius:10px;font-weight:700;font-size:13px;cursor:pointer">Delete Selected</button>
            </form>
            <button type="button" onclick="tnReqSelectNone()" style="background:#f0f5f3;color:#5f726c;border:none;padding:8px 14px;border-radius:10px;font-weight:700;font-size:13px;cursor:pointer">Clear</button>
        </div>
        <?php if (empty($requests)): ?>
            <div style="padding:48px 24px;text-align:center;color:#8a9a94"><?= $hasRequestFilters ? 'No requests match the selected filters.' : 'No document requests active.' ?></div>
        <?php else: ?>
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;text-align:left">
                    <thead>
                        <tr style="border-bottom:2px solid #eef4f1;color:#7d8e88;font-size:12.5px;text-transform:uppercase;letter-spacing:.05em">
                            <th style="padding:12px 10px;font-weight:700;width:36px"><input type="checkbox" id="reqCheckAll" aria-label="Select all requests" onchange="tnReqToggleAll(this)" style="width:16px;height:16px;cursor:pointer"></th>
                            <th style="padding:12px 16px;font-weight:700">Client</th>
                            <th style="padding:12px 16px;font-weight:700">Title &amp; Instructions</th>
                            <th style="padding:12px 16px;font-weight:700">Issued By</th>
                            <th style="padding:12px 16px;font-weight:700">Due Date</th>
                            <th style="padding:12px 16px;font-weight:700">Lifecycle Status</th>
                            <th style="padding:12px 16px;font-weight:700;text-align:right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                            <tr style="border-bottom:1px solid #eef4f1">
                                <td style="padding:10px 10px"><input type="checkbox" class="tn-req-check" value="<?= (int)$req['id'] ?>" aria-label="Select request <?= htmlspecialchars($req['title']) ?>" onchange="tnReqUpdateBar()" style="width:16px;height:16px;cursor:pointer"></td>
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
                                        <select name="status" onchange="this.form.requestSubmit()" style="padding:6px 12px;border-radius:10px;font-size:12.5px;font-weight:700;border:1px solid #e0e9e5;background:#fbfdfc;cursor:pointer">
                                            <option value="Awaiting Client" <?= $req['status'] === 'Awaiting Client' ? 'selected' : '' ?>>Awaiting Client</option>
                                            <option value="Uploaded" <?= $req['status'] === 'Uploaded' ? 'selected' : '' ?>>Uploaded</option>
                                            <option value="Under Review" <?= $req['status'] === 'Under Review' ? 'selected' : '' ?>>Under Review</option>
                                            <option value="Completed" <?= $req['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                        </select>
                                    </form>
                                </td>
                                <td style="padding:16px;text-align:right">
                                    <button type="button" onclick="tnReqDelete(<?= (int)$req['id'] ?>)" style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;padding:8px 12px;border-radius:10px;font-weight:700;font-size:13px;cursor:pointer">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <?php if (($pagination['total'] ?? 0) > 0): ?>
            <?php $page=(int)$pagination['page']; $pages=(int)$pagination['total_pages']; $query=array_filter(['q'=>$filters['search'],'client_id'=>$filters['client_id'],'created_by'=>$filters['created_by'],'status'=>$filters['status'],'due_from'=>$filters['due_from'],'due_to'=>$filters['due_to'],'timing'=>$filters['timing'],'sort'=>$filters['sort'],'per_page'=>$pagination['per_page']], static fn($v)=>$v!==''&&$v!==0); ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding-top:18px;margin-top:12px;border-top:1px solid #eef4f1;gap:12px;flex-wrap:wrap;color:#61756e;font-size:13px"><span>Showing <?= (($page-1)*$pagination['per_page'])+1 ?>–<?= min($pagination['total'],$page*$pagination['per_page']) ?> of <?= $pagination['total'] ?> requests</span><div style="display:flex;gap:8px;align-items:center"><?php if($page>1): ?><a href="/staff/requests?<?= http_build_query($query+['page'=>$page-1]) ?>" style="padding:8px 12px;border-radius:10px;background:#eef4f1;font-weight:700">Previous</a><?php endif; ?><strong>Page <?= $page ?> of <?= $pages ?></strong><?php if($page<$pages): ?><a href="/staff/requests?<?= http_build_query($query+['page'=>$page+1]) ?>" style="padding:8px 12px;border-radius:10px;background:#eef4f1;font-weight:700">Next</a><?php endif; ?></div></div>
        <?php endif; ?>
    </div>
</div>

<script>
function tnReqDelete(id) {
    document.getElementById('deleteReqId').value = id;
    document.getElementById('deleteReqModal').style.display = 'flex';
}
function tnReqToggleAll(el) {
    document.querySelectorAll('.tn-req-check').forEach(c => { c.checked = el.checked; });
    tnReqUpdateBar();
}
function tnReqSelectNone() {
    document.querySelectorAll('.tn-req-check, #reqCheckAll').forEach(c => { c.checked = false; });
    tnReqUpdateBar();
}
function tnReqUpdateBar() {
    const checked = [...document.querySelectorAll('.tn-req-check:checked')];
    document.getElementById('reqBulkCount').textContent = checked.length + ' selected';
    const container = document.getElementById('reqBulkIds');
    container.innerHTML = '';
    checked.forEach(c => {
        const inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = 'ids[]'; inp.value = c.value;
        container.appendChild(inp);
    });
    document.getElementById('reqBulkBar').style.display = checked.length > 0 ? 'flex' : 'none';
}
</script>
