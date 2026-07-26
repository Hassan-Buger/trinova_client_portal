<div class="tn-screen" style="max-width:1120px">
    <div style="margin-bottom:24px">
        <p style="margin:0;color:#61756e;font-size:14.5px">Official tax returns, financial statements, and documents prepared for you by our team.</p>
    </div>

    <div style="background:#fff;border-radius:24px;padding:26px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -24px rgba(16,54,45,.4)">
        <?php if (empty($documents)): ?>
            <div style="padding:48px 24px;text-align:center;color:#8a9a94">
                <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:12px">
                    <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                </svg>
                <div style="font-size:16px;font-weight:700;color:#3a4d47;margin-bottom:4px">No Shared Documents</div>
                <div style="font-size:14px">Documents delivered by your accounting team will appear here for secure download.</div>
            </div>
        <?php else: ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:16px">
                <?php foreach ($documents as $doc): ?>
                    <div style="border:1px solid rgba(20,60,50,.08);border-radius:20px;padding:20px;display:flex;flex-direction:column;justify-space-between;background:#fbfdfc">
                        <div>
                            <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px">
                                <div style="width:42px;height:42px;border-radius:12px;background:#e6ecf5;color:#41556f;display:flex;align-items:center;justify-content:center">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                                        <polyline points="14 3 14 9 20 9"></polyline>
                                    </svg>
                                </div>
                                <div>
                                    <div style="font-weight:700;font-size:15px;color:#213330"><?= htmlspecialchars($doc['filename']) ?></div>
                                    <div style="font-size:12.5px;color:#7d8e88;margin-top:2px">Delivered <?= date('d M Y', strtotime($doc['created_at'])) ?></div>
                                </div>
                            </div>
                            <p style="margin:0 0 16px;font-size:13.5px;color:#61756e;line-height:1.4">
                                <?= htmlspecialchars($doc['description'] ?: 'Official document provided by TriNova Accounting.') ?>
                            </p>
                        </div>
                        <div style="display:flex;align-items:center;justify-content:space-between;padding-top:14px;border-top:1px solid #eef4f1">
                            <span style="font-size:12px;color:#41556f;font-weight:600">By <?= htmlspecialchars($doc['uploaded_by_name'] ?? 'TriNova Team') ?></span>
                            <div style="display:flex;align-items:center;gap:7px">
                            <?php $previewable = in_array(strtolower(pathinfo((string)$doc['filename'], PATHINFO_EXTENSION)), ['pdf', 'png', 'jpg', 'jpeg', 'txt', 'csv'], true); ?>
                            <?php if ($previewable): ?>
                                <a href="/client/documents/download/<?= (int)$doc['id'] ?>?preview=1" target="_blank" rel="noopener" data-no-ajax style="background:#f0f5f3;color:#41556f;padding:9px 14px;border-radius:12px;font-weight:700;font-size:13px">View</a>
                            <?php endif; ?>
                            <a href="/documents/download/<?= $doc['id'] ?>" style="background:#0d9488;color:#fff;padding:9px 16px;border-radius:12px;font-weight:700;font-size:13px;display:inline-flex;align-items:center;gap:6px">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="7 10 12 15 17 10"></polyline>
                                    <line x1="12" y1="15" x2="12" y2="3"></line>
                                </svg>
                                Download
                            </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
