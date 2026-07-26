<div class="tn-screen" style="max-width:1120px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px">
        <div>
            <p style="margin:0;color:#61756e;font-size:14.5px">Monitor and update statutory tax deadlines for all practice clients.</p>
        </div>
        <button onclick="document.getElementById('newDeadlineModal').style.display='flex'" style="background:#41556f;color:#fff;padding:12px 22px;border-radius:14px;font-weight:700;font-size:14px;cursor:pointer;border:none;box-shadow:0 8px 18px -8px rgba(65,85,111,.7)">+ Add Deadline</button>
    </div>

    <?php $hasDeadlineFilters = ($filters['search'] ?? '') !== '' || !empty($filters['client_id']) || ($filters['type'] ?? '') !== '' || ($filters['status'] ?? '') !== '' || ($filters['due_from'] ?? '') !== '' || ($filters['due_to'] ?? '') !== '' || ($filters['timing'] ?? '') !== ''; ?>
    <form action="/staff/deadlines" method="GET" data-ajax-form style="background:#fff;border-radius:22px;padding:18px;margin-bottom:20px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -24px rgba(16,54,45,.3)">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px">
            <input type="search" name="q" value="<?= htmlspecialchars($filters['search'] ?? '') ?>" data-ajax-search placeholder="Search client…" aria-label="Search deadlines" style="padding:11px 14px;border:1.5px solid #e0e9e5;border-radius:13px;background:#fbfdfc">
            <select name="client_id" onchange="this.form.requestSubmit()" aria-label="Filter by client" style="padding:11px;border:1.5px solid #e0e9e5;border-radius:13px;background:#fbfdfc"><option value="">All clients</option><?php foreach($clients as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)($filters['client_id'] ?? 0)===(int)$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select>
            <select name="type" onchange="this.form.requestSubmit()" aria-label="Filter by deadline type" style="padding:11px;border:1.5px solid #e0e9e5;border-radius:13px;background:#fbfdfc"><option value="">All deadline types</option><?php foreach($types as $type): ?><option value="<?= htmlspecialchars($type) ?>" <?= ($filters['type'] ?? '')===$type?'selected':'' ?>><?= htmlspecialchars($type) ?></option><?php endforeach; ?></select>
            <select name="status" onchange="this.form.requestSubmit()" aria-label="Filter by status" style="padding:11px;border:1.5px solid #e0e9e5;border-radius:13px;background:#fbfdfc"><option value="">All statuses</option><?php foreach($statuses as $status): ?><option value="<?= htmlspecialchars($status) ?>" <?= ($filters['status'] ?? '')===$status?'selected':'' ?>><?= htmlspecialchars($status) ?></option><?php endforeach; ?></select>
            <select name="timing" onchange="this.form.requestSubmit()" aria-label="Filter by timing" style="padding:11px;border:1.5px solid #e0e9e5;border-radius:13px;background:#fbfdfc"><option value="">Any due timing</option><option value="overdue" <?= ($filters['timing'] ?? '')==='overdue'?'selected':'' ?>>Overdue only</option><option value="upcoming" <?= ($filters['timing'] ?? '')==='upcoming'?'selected':'' ?>>Upcoming only</option></select>
            <input type="date" name="due_from" value="<?= htmlspecialchars($filters['due_from'] ?? '') ?>" onchange="this.form.requestSubmit()" aria-label="Due from" title="Due from" style="padding:10px;border:1.5px solid #e0e9e5;border-radius:13px;background:#fbfdfc">
            <input type="date" name="due_to" value="<?= htmlspecialchars($filters['due_to'] ?? '') ?>" onchange="this.form.requestSubmit()" aria-label="Due to" title="Due to" style="padding:10px;border:1.5px solid #e0e9e5;border-radius:13px;background:#fbfdfc">
            <div style="display:flex;gap:8px"><select name="sort" onchange="this.form.requestSubmit()" aria-label="Sort deadlines" style="flex:1;padding:11px;border:1.5px solid #e0e9e5;border-radius:13px;background:#fbfdfc"><option value="due_asc" <?= ($filters['sort'] ?? '')==='due_asc'?'selected':'' ?>>Due soonest</option><option value="due_desc" <?= ($filters['sort'] ?? '')==='due_desc'?'selected':'' ?>>Due latest</option><option value="newest" <?= ($filters['sort'] ?? '')==='newest'?'selected':'' ?>>Newest created</option><option value="client_asc" <?= ($filters['sort'] ?? '')==='client_asc'?'selected':'' ?>>Client A–Z</option></select><select name="per_page" onchange="this.form.requestSubmit()" aria-label="Results per page" style="width:100px;padding:11px 8px;border:1.5px solid #e0e9e5;border-radius:13px;background:#fbfdfc"><?php foreach([10,20,50] as $size): ?><option value="<?= $size ?>" <?= (int)$pagination['per_page']===$size?'selected':'' ?>><?= $size ?> / page</option><?php endforeach; ?></select></div>
        </div>
        <?php if($hasDeadlineFilters): ?><div style="margin-top:13px;text-align:right"><a href="/staff/deadlines" style="color:#e07d24;font-size:12.5px;font-weight:800">Reset filters</a></div><?php endif; ?>
    </form>

    <!-- New Deadline Modal -->
    <div id="newDeadlineModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(20,40,35,.45);backdrop-filter:blur(6px);z-index:99;align-items:center;justify-content:center;padding:20px">
        <div style="background:#fff;border-radius:24px;width:100%;max-width:500px;padding:32px;box-shadow:0 24px 60px -28px rgba(0,0,0,.4);animation:tnpop .25s ease">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
                <h3 style="margin:0;font-size:20px;font-weight:800">Add Compliance Deadline</h3>
                <button onclick="document.getElementById('newDeadlineModal').style.display='none'" style="background:none;border:none;font-size:24px;cursor:pointer;color:#8a9a94">&times;</button>
            </div>

            <form action="/staff/deadlines/create" method="POST" data-ajax-form>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
                
                <div style="margin-bottom:16px"><label style="display:block;font-size:13px;font-weight:700;color:#3a4d47;margin-bottom:6px">Client entity</label><input type="hidden" name="client_id" id="deadlineClientId"><select name="entity_id" required onchange="document.getElementById('deadlineClientId').value=this.options[this.selectedIndex].dataset.client" style="width:100%;padding:13px 16px;border:1.5px solid #e0e9e5;border-radius:14px;font-size:14px;background:#fbfdfc"><option value="">-- Select Entity --</option><?php foreach($entities as $entity): ?><option value="<?= (int)$entity['id'] ?>" data-client="<?= (int)$entity['client_id'] ?>"><?= htmlspecialchars($entity['client_name'].' — '.$entity['company_name']) ?></option><?php endforeach; ?></select></div>

                <div style="margin-bottom:16px">
                    <label style="display:block;font-size:13px;font-weight:700;color:#3a4d47;margin-bottom:6px">Deadline Type</label>
                    <input name="type" list="deadlineTypes" required placeholder="e.g. Accounts Due" style="width:100%;padding:13px 16px;border:1.5px solid #e0e9e5;border-radius:14px;font-size:14px;background:#fbfdfc"><datalist id="deadlineTypes"><?php foreach(array_unique(array_merge($types,['Accounts Due','Corporation Tax Due','Next VAT Return Due','Tax Return Due','Payment Due','Payment on Account'])) as $type): ?><option value="<?= htmlspecialchars($type) ?>"><?php endforeach; ?></datalist>
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
            <div style="padding:48px 24px;text-align:center;color:#8a9a94"><?= $hasDeadlineFilters ? 'No deadlines match the selected filters.' : 'No deadlines logged in matrix.' ?></div>
        <?php else: ?>
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;text-align:left">
                    <thead>
                        <tr style="border-bottom:2px solid #eef4f1;color:#7d8e88;font-size:12.5px;text-transform:uppercase;letter-spacing:.05em">
                            <th style="padding:12px 16px;font-weight:700">Client</th>
                            <th style="padding:12px 16px;font-weight:700">Entity</th><th style="padding:12px 16px;font-weight:700">Type</th>
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
                                    <?= htmlspecialchars($d['entity_name'] ?? 'Entity') ?>
                                </td><td style="padding:16px;font-weight:700;color:#41556f">
                                    <?= htmlspecialchars($d['type']) ?>
                                </td>
                                <td style="padding:16px;color:#213330;font-weight:600">
                                    <?= date('d M Y', strtotime($d['due_date'])) ?>
                                </td>
                                <td style="padding:16px">
                                    <form action="/staff/deadlines/update-status" method="POST" data-ajax-form style="margin:0;display:inline-block">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
                                        <input type="hidden" name="deadline_id" value="<?= $d['id'] ?>">
                                        <select name="status" onchange="this.form.requestSubmit()" style="padding:6px 12px;border-radius:10px;font-size:12.5px;font-weight:700;border:1px solid #e0e9e5;background:#fbfdfc;cursor:pointer">
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
        <?php if (($pagination['total'] ?? 0) > 0): ?>
            <?php $page=(int)$pagination['page']; $pages=(int)$pagination['total_pages']; $query=array_filter(['q'=>$filters['search'],'client_id'=>$filters['client_id'],'type'=>$filters['type'],'status'=>$filters['status'],'due_from'=>$filters['due_from'],'due_to'=>$filters['due_to'],'timing'=>$filters['timing'],'sort'=>$filters['sort'],'per_page'=>$pagination['per_page']], static fn($v)=>$v!==''&&$v!==0); ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding-top:18px;margin-top:12px;border-top:1px solid #eef4f1;gap:12px;flex-wrap:wrap;color:#61756e;font-size:13px"><span>Showing <?= (($page-1)*$pagination['per_page'])+1 ?>–<?= min($pagination['total'],$page*$pagination['per_page']) ?> of <?= $pagination['total'] ?> deadlines</span><div style="display:flex;gap:8px;align-items:center"><?php if($page>1): ?><a href="/staff/deadlines?<?= http_build_query($query+['page'=>$page-1]) ?>" style="padding:8px 12px;border-radius:10px;background:#eef4f1;font-weight:700">Previous</a><?php endif; ?><strong>Page <?= $page ?> of <?= $pages ?></strong><?php if($page<$pages): ?><a href="/staff/deadlines?<?= http_build_query($query+['page'=>$page+1]) ?>" style="padding:8px 12px;border-radius:10px;background:#eef4f1;font-weight:700">Next</a><?php endif; ?></div></div>
        <?php endif; ?>
    </div>
</div>
