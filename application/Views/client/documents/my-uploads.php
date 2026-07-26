<div class="tn-screen" style="max-width:1120px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px">
        <div>
            <p style="margin:0;color:#61756e;font-size:14.5px">All files you have uploaded to TriNova Accounting.</p>
        </div>
        <a href="/client/documents/upload" style="background:#0d9488;color:#fff;padding:12px 22px;border-radius:14px;font-weight:700;font-size:14px;box-shadow:0 8px 18px -8px rgba(13,148,136,.7)">+ Upload New File</a>
    </div>

    <div style="background:#fff;border-radius:24px;padding:26px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -24px rgba(16,54,45,.4)">
        <?php if (empty($documents)): ?>
            <div style="padding:48px 24px;text-align:center;color:#8a9a94">
                <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:12px">
                    <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                    <polyline points="13 2 13 9 20 9"></polyline>
                </svg>
                <div style="font-size:16px;font-weight:700;color:#3a4d47;margin-bottom:4px">No Uploaded Documents Yet</div>
                <div style="font-size:14px">Upload your statements, receipts, and tax records whenever you are ready.</div>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;text-align:left">
                    <thead>
                        <tr style="border-bottom:2px solid #eef4f1;color:#7d8e88;font-size:12.5px;text-transform:uppercase;letter-spacing:.05em">
                            <th style="padding:12px 16px;font-weight:700">Filename</th>
                            <th style="padding:12px 16px;font-weight:700">Description</th>
                            <th style="padding:12px 16px;font-weight:700">Date Uploaded</th>
                            <th style="padding:12px 16px;font-weight:700">Status</th>
                            <th style="padding:12px 16px;font-weight:700;text-align:right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $doc): ?>
                            <tr style="border-bottom:1px solid #eef4f1">
                                <td style="padding:16px;font-weight:700;color:#213330">
                                    <div style="display:flex;align-items:center;gap:10px">
                                        <div style="width:36px;height:36px;border-radius:10px;background:#dff1ee;color:#0d9488;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                                                <polyline points="14 3 14 9 20 9"></polyline>
                                            </svg>
                                        </div>
                                        <span><?= htmlspecialchars($doc['filename']) ?></span>
                                    </div>
                                </td>
                                <td style="padding:16px;color:#61756e;font-size:14px">
                                    <?= htmlspecialchars($doc['description'] ?: 'No description provided') ?>
                                </td>
                                <td style="padding:16px;color:#7d8e88;font-size:13.5px">
                                    <?= date('d M Y, H:i', strtotime($doc['created_at'])) ?>
                                </td>
                                <td style="padding:16px">
                                    <span style="padding:5px 12px;border-radius:999px;font-size:12px;font-weight:700;background:#e2f3ea;color:#3f9d6d">
                                        <?= htmlspecialchars($doc['status']) ?>
                                    </span>
                                </td>
                                <td style="padding:16px;text-align:right">
                                    <?php $previewable = in_array(strtolower(pathinfo((string)$doc['filename'], PATHINFO_EXTENSION)), ['pdf', 'png', 'jpg', 'jpeg', 'txt', 'csv'], true); ?>
                                    <?php if ($previewable): ?>
                                        <a href="/client/documents/view/<?= (int)$doc['id'] ?>" target="_blank" rel="noopener" data-no-ajax style="background:#fff;color:#41556f;border:1px solid #dfe8e4;padding:8px 14px;border-radius:10px;font-weight:700;font-size:13px;display:inline-flex;margin-right:6px">View</a>
                                    <?php endif; ?>
                                    <a href="/documents/download/<?= $doc['id'] ?>" style="background:#f0f5f3;color:#0d9488;padding:8px 14px;border-radius:10px;font-weight:700;font-size:13px;display:inline-flex;align-items:center;gap:6px">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                            <polyline points="7 10 12 15 17 10"></polyline>
                                            <line x1="12" y1="15" x2="12" y2="3"></line>
                                        </svg>
                                        Download
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
