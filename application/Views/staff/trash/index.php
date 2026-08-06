<div class="tn-screen" style="max-width:1120px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px">
        <div>
            <p style="margin:0;color:#61756e;font-size:14.5px">View soft-deleted records across all system modules and restore items back to active status.</p>
        </div>
        <div style="padding:8px 16px;background:#fff8ee;border:1px solid #f6dfc0;border-radius:12px;color:#e07d24;font-weight:700;font-size:13.5px">
            <?= (int)$totalDeleted ?> items in Trash
        </div>
    </div>

    <!-- Category Tabs -->
    <div style="display:flex;gap:8px;margin-bottom:20px;overflow-x:auto;padding-bottom:4px">
        <?php
        $categories = [
            'all'       => ['label' => 'All Items', 'count' => $totalDeleted],
            'clients'   => ['label' => 'Clients', 'count' => count($deletedItems['clients'] ?? [])],
            'entities'  => ['label' => 'Entities', 'count' => count($deletedItems['entities'] ?? [])],
            'documents' => ['label' => 'Documents', 'count' => count($deletedItems['documents'] ?? [])],
            'requests'  => ['label' => 'Requests', 'count' => count($deletedItems['requests'] ?? [])],
            'messages'  => ['label' => 'Messages', 'count' => count($deletedItems['messages'] ?? [])],
            'deadlines' => ['label' => 'Deadlines', 'count' => count($deletedItems['deadlines'] ?? [])],
            'meetings'  => ['label' => 'Meetings', 'count' => count($deletedItems['meetings'] ?? [])],
            'users'     => ['label' => 'Users', 'count' => count($deletedItems['users'] ?? [])],
            'batches'   => ['label' => 'CSV Batches', 'count' => count($deletedItems['batches'] ?? [])],
        ];
        $activeTab = $_GET['tab'] ?? 'all';
        ?>
        <?php foreach ($categories as $key => $cat): ?>
            <a href="/staff/trash?tab=<?= $key ?>" data-ajax-link style="padding:9px 16px;border-radius:12px;font-size:13px;font-weight:700;text-decoration:none;white-space:nowrap;<?= $activeTab === $key ? 'background:#0d9488;color:#fff;' : 'background:#fff;color:#61756e;border:1px solid #e0e9e5;' ?>">
                <?= htmlspecialchars($cat['label']) ?> (<?= $cat['count'] ?>)
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Trash Table Container -->
    <div style="background:#fff;border-radius:24px;padding:26px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -24px rgba(16,54,45,.4)">
        <?php if ($totalDeleted === 0): ?>
            <div style="padding:48px 24px;text-align:center;color:#8a9a94">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:12px">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
                <div style="font-size:16px;font-weight:700;color:#3a4d47;margin-bottom:4px">Trash is Empty</div>
                <div style="font-size:14px">There are currently no soft-deleted records in the system.</div>
            </div>
        <?php else: ?>
            <?php
            // Flatten items for 'all' or filter by activeTab
            $typeMapping = [
                'clients'   => ['type' => 'client', 'label' => 'Client', 'title' => fn($i) => $i['name'] ?? 'Client #'.$i['id']],
                'entities'  => ['type' => 'entity', 'label' => 'Entity', 'title' => fn($i) => $i['company_name'] ?? 'Entity #'.$i['id']],
                'documents' => ['type' => 'document', 'label' => 'Document', 'title' => fn($i) => $i['filename'] ?? 'Doc #'.$i['id']],
                'requests'  => ['type' => 'request', 'label' => 'Document Request', 'title' => fn($i) => $i['title'] ?? 'Request #'.$i['id']],
                'messages'  => ['type' => 'message', 'label' => 'Message', 'title' => fn($i) => substr((string)($i['body'] ?? ''), 0, 50) . '...'],
                'deadlines' => ['type' => 'deadline', 'label' => 'Deadline', 'title' => fn($i) => ($i['type'] ?? 'Deadline') . ' (Due '.($i['due_date'] ?? '').')'],
                'meetings'  => ['type' => 'meeting', 'label' => 'Meeting', 'title' => fn($i) => ($i['type'] ?? 'Meeting') . ' ['.$i['external_booking_reference'].']'],
                'users'     => ['type' => 'user', 'label' => 'User Account', 'title' => fn($i) => ($i['name'] ?? 'User') . ' ('.$i['email'].')'],
                'batches'   => ['type' => 'csv_batch', 'label' => 'CSV Batch', 'title' => fn($i) => 'Import Batch #'.($i['import_id'] ?? $i['id'] ?? '')],
            ];

            $displayItems = [];
            foreach ($deletedItems as $catKey => $itemList) {
                if ($activeTab !== 'all' && $activeTab !== $catKey) continue;
                $config = $typeMapping[$catKey] ?? null;
                if (!$config) continue;

                foreach ($itemList as $item) {
                    $displayItems[] = [
                        'id'          => (int)$item['id'],
                        'target_type' => $config['type'],
                        'module'      => $config['label'],
                        'title'       => $config['title']($item),
                        'deleted_at'  => $item['deleted_at'] ?? $item['updated_at'] ?? null,
                    ];
                }
            }
            ?>

            <?php if (empty($displayItems)): ?>
                <div style="padding:48px 24px;text-align:center;color:#8a9a94">No trashed items under this category.</div>
            <?php else: ?>
                <!-- Bulk Action Bar -->
                <div id="trashBulkBar" style="display:none;align-items:center;gap:12px;padding:12px 16px;background:#e2f3ea;border-radius:14px;margin-bottom:16px;border:1px solid #c2e7d5">
                    <span id="trashBulkCount" style="font-weight:700;color:#3f9d6d;font-size:13.5px">0 selected</span>
                    <form action="/staff/trash/bulk-restore" method="POST" data-ajax-form style="display:inline">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
                        <input type="hidden" name="target_type" id="trashBulkType" value="<?= htmlspecialchars($displayItems[0]['target_type'] ?? '') ?>">
                        <div id="trashBulkIds"></div>
                        <button type="submit" style="background:#0d9488;color:#fff;border:none;padding:8px 18px;border-radius:10px;font-weight:700;font-size:13px;cursor:pointer">Restore Selected</button>
                    </form>
                    <button type="button" onclick="tnTrashSelectNone()" style="background:#f0f5f3;color:#5f726c;border:none;padding:8px 14px;border-radius:10px;font-weight:700;font-size:13px;cursor:pointer">Clear</button>
                </div>

                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse;text-align:left">
                        <thead>
                            <tr style="border-bottom:2px solid #eef4f1;color:#7d8e88;font-size:12.5px;text-transform:uppercase;letter-spacing:.05em">
                                <th style="padding:12px 10px;width:36px"><input type="checkbox" id="trashCheckAll" aria-label="Select all trashed items" onchange="tnTrashToggleAll(this)" style="width:16px;height:16px;cursor:pointer"></th>
                                <th style="padding:12px 16px;font-weight:700">Module / Type</th>
                                <th style="padding:12px 16px;font-weight:700">Item Details</th>
                                <th style="padding:12px 16px;font-weight:700">Deleted Date</th>
                                <th style="padding:12px 16px;font-weight:700;text-align:right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($displayItems as $row): ?>
                                <tr style="border-bottom:1px solid #eef4f1">
                                    <td style="padding:12px 10px"><input type="checkbox" class="tn-trash-check" data-type="<?= htmlspecialchars($row['target_type']) ?>" value="<?= $row['id'] ?>" aria-label="Select trashed item" onchange="tnTrashUpdateBar()" style="width:16px;height:16px;cursor:pointer"></td>
                                    <td style="padding:16px">
                                        <span style="padding:5px 12px;border-radius:999px;font-size:12px;font-weight:700;background:#f0f5f3;color:#41556f">
                                            <?= htmlspecialchars($row['module']) ?>
                                        </span>
                                    </td>
                                    <td style="padding:16px;font-weight:700;color:#213330">
                                        <?= htmlspecialchars($row['title']) ?>
                                    </td>
                                    <td style="padding:16px;color:#7d8e88;font-size:13.5px">
                                        <?= $row['deleted_at'] ? date('d M Y, H:i', strtotime($row['deleted_at'])) : 'Unknown' ?>
                                    </td>
                                    <td style="padding:16px;text-align:right">
                                        <form action="/staff/trash/restore" method="POST" data-ajax-form style="margin:0;display:inline-block">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
                                            <input type="hidden" name="target_type" value="<?= htmlspecialchars($row['target_type']) ?>">
                                            <input type="hidden" name="target_id" value="<?= $row['id'] ?>">
                                            <button type="submit" style="background:#e6f4f1;color:#0d9488;border:1px solid #bce3db;padding:8px 14px;border-radius:10px;font-weight:700;font-size:13px;cursor:pointer">Restore</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function tnTrashToggleAll(el) {
    document.querySelectorAll('.tn-trash-check').forEach(c => { c.checked = el.checked; });
    tnTrashUpdateBar();
}
function tnTrashSelectNone() {
    document.querySelectorAll('.tn-trash-check, #trashCheckAll').forEach(c => { c.checked = false; });
    tnTrashUpdateBar();
}
function tnTrashUpdateBar() {
    const checked = [...document.querySelectorAll('.tn-trash-check:checked')];
    document.getElementById('trashBulkCount').textContent = checked.length + ' selected';
    const container = document.getElementById('trashBulkIds');
    container.innerHTML = '';
    
    if (checked.length > 0) {
        const type = checked[0].dataset.type;
        document.getElementById('trashBulkType').value = type;
        checked.forEach(c => {
            const inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = 'ids[]'; inp.value = c.value;
            container.appendChild(inp);
        });
    }
    document.getElementById('trashBulkBar').style.display = checked.length > 0 ? 'flex' : 'none';
}
</script>
