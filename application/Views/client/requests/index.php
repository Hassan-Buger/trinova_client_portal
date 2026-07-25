<div class="tn-screen" style="max-width:1120px">
    <div style="margin-bottom:24px">
        <p style="margin:0;color:#61756e;font-size:14.5px">Documents requested by your accounting team for upcoming tax & compliance submissions.</p>
    </div>

    <div style="background:#fff;border-radius:24px;padding:26px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -24px rgba(16,54,45,.4)">
        <?php if (empty($requests)): ?>
            <div style="padding:48px 24px;text-align:center;color:#8a9a94">
                <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:12px">
                    <polyline points="9 11 12 14 22 4"></polyline>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                </svg>
                <div style="font-size:16px;font-weight:700;color:#3a4d47;margin-bottom:4px">All Caught Up!</div>
                <div style="font-size:14px">You have no outstanding document requests at this time.</div>
            </div>
        <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:16px">
                <?php foreach ($requests as $req): ?>
                    <?php
                    $isOverdue = (strtotime($req['due_date']) < time() && $req['status'] !== 'Completed');
                    $statusColor = match($req['status']) {
                        'Completed' => 'background:#e2f3ea;color:#3f9d6d;',
                        'Uploaded' => 'background:#dff1ee;color:#0d9488;',
                        'Under Review' => 'background:#e6ecf5;color:#41556f;',
                        default => 'background:#fdecdc;color:#e07d24;'
                    };
                    ?>
                    <div style="border:1px solid rgba(20,60,50,.08);border-radius:20px;padding:22px;display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap">
                        <div style="flex:1;min-width:280px">
                            <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
                                <h3 style="margin:0;font-size:17px;font-weight:800;color:#213330"><?= htmlspecialchars($req['title']) ?></h3>
                                <span style="padding:4px 12px;border-radius:999px;font-size:12px;font-weight:700;<?= $statusColor ?>">
                                    <?= htmlspecialchars($req['status']) ?>
                                </span>
                            </div>
                            <p style="margin:0 0 8px;color:#61756e;font-size:14px;line-height:1.4">
                                <?= htmlspecialchars($req['description'] ?: 'No additional instructions provided.') ?>
                            </p>
                            <div style="display:flex;align-items:center;gap:16px;font-size:13px;color:#7d8e88">
                                <span>Due: <strong style="<?= $isOverdue ? 'color:#d9534f' : 'color:#3a4d47' ?>"><?= date('d M Y', strtotime($req['due_date'])) ?></strong></span>
                                <span>Issued <?= date('d M Y', strtotime($req['created_at'])) ?></span>
                            </div>
                        </div>

                        <?php if ($req['status'] !== 'Completed'): ?>
                            <a href="/client/documents/upload?request_id=<?= $req['id'] ?>" style="background:#ef8f3c;color:#fff;padding:12px 22px;border-radius:14px;font-weight:700;font-size:14px;box-shadow:0 8px 16px -8px rgba(239,143,60,.9);white-space:nowrap">
                                Upload Document
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
