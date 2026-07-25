<div class="tn-screen" style="max-width:1120px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px">
        <div>
            <h2 style="margin:0 0 6px;font-size:26px;font-weight:800;letter-spacing:-.02em">Document Management</h2>
            <p style="margin:0;color:#61756e;font-size:14.5px">Overview of all client uploads and dispatch new files to clients.</p>
        </div>
        <button onclick="document.getElementById('dispatchModal').style.display='flex'" style="background:#41556f;color:#fff;padding:12px 22px;border-radius:14px;font-weight:700;font-size:14px;cursor:pointer;border:none;box-shadow:0 8px 18px -8px rgba(65,85,111,.7)">+ Dispatch Document to Client</button>
    </div>

    <!-- Dispatch Document Modal -->
    <div id="dispatchModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(20,40,35,.45);backdrop-filter:blur(6px);z-index:99;align-items:center;justify-content:center;padding:20px">
        <div style="background:#fff;border-radius:24px;width:100%;max-width:540px;padding:32px;box-shadow:0 24px 60px -28px rgba(0,0,0,.4);animation:tnpop .25s ease">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
                <h3 style="margin:0;font-size:20px;font-weight:800">Dispatch Document to Client</h3>
                <button onclick="document.getElementById('dispatchModal').style.display='none'" style="background:none;border:none;font-size:24px;cursor:pointer;color:#8a9a94">&times;</button>
            </div>

            <form action="/staff/documents/upload" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
                
                <div style="margin-bottom:16px">
                    <label style="display:block;font-size:13px;font-weight:700;color:#3a4d47;margin-bottom:6px">Select Target Client</label>
                    <select name="client_id" required style="width:100%;padding:13px 16px;border:1.5px solid #e0e9e5;border-radius:14px;font-size:14px;background:#fbfdfc">
                        <option value="">-- Choose Client Account --</option>
                        <?php foreach ($clients as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['email']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="margin-bottom:16px">
                    <label style="display:block;font-size:13px;font-weight:700;color:#3a4d47;margin-bottom:6px">File Artifact</label>
                    <input type="file" name="file" required style="width:100%;padding:12px;border:1.5px solid #e0e9e5;border-radius:14px;font-size:13.5px;background:#fbfdfc">
                </div>

                <div style="margin-bottom:24px">
                    <label style="display:block;font-size:13px;font-weight:700;color:#3a4d47;margin-bottom:6px">Description / Note for Client</label>
                    <textarea name="description" rows="3" placeholder="e.g. Final Corporation Tax return calculation summary 2025" style="width:100%;padding:13px 16px;border:1.5px solid #e0e9e5;border-radius:14px;font-size:14px;background:#fbfdfc;resize:vertical"></textarea>
                </div>

                <div style="display:flex;justify-content:flex-end;gap:12px">
                    <button type="button" onclick="document.getElementById('dispatchModal').style.display='none'" style="background:#f0f5f3;color:#5f726c;border:none;padding:12px 20px;border-radius:12px;font-weight:700;font-size:14px;cursor:pointer">Cancel</button>
                    <button type="submit" style="background:#0d9488;color:#fff;border:none;padding:12px 24px;border-radius:12px;font-weight:700;font-size:14px;cursor:pointer">Upload & Dispatch</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Documents Table -->
    <div style="background:#fff;border-radius:24px;padding:26px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -24px rgba(16,54,45,.4)">
        <?php if (empty($documents)): ?>
            <div style="padding:48px 24px;text-align:center;color:#8a9a94">No documents recorded in the repository.</div>
        <?php else: ?>
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;text-align:left">
                    <thead>
                        <tr style="border-bottom:2px solid #eef4f1;color:#7d8e88;font-size:12.5px;text-transform:uppercase;letter-spacing:.05em">
                            <th style="padding:12px 16px;font-weight:700">Client</th>
                            <th style="padding:12px 16px;font-weight:700">Filename</th>
                            <th style="padding:12px 16px;font-weight:700">Direction</th>
                            <th style="padding:12px 16px;font-weight:700">Uploaded By</th>
                            <th style="padding:12px 16px;font-weight:700">Date</th>
                            <th style="padding:12px 16px;font-weight:700;text-align:right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $doc): ?>
                            <tr style="border-bottom:1px solid #eef4f1">
                                <td style="padding:16px;font-weight:700;color:#213330">
                                    <?= htmlspecialchars($doc['client_name'] ?? 'Client') ?>
                                </td>
                                <td style="padding:16px;font-weight:600">
                                    <?= htmlspecialchars($doc['filename']) ?>
                                    <?php if ($doc['description']): ?>
                                        <div style="font-size:12.5px;color:#7d8e88;font-weight:400;margin-top:2px"><?= htmlspecialchars($doc['description']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td style="padding:16px">
                                    <?php if ($doc['direction'] === 'client_upload'): ?>
                                        <span style="padding:4px 10px;border-radius:999px;font-size:11.5px;font-weight:700;background:#fdecdc;color:#e07d24">Client Upload</span>
                                    <?php else: ?>
                                        <span style="padding:4px 10px;border-radius:999px;font-size:11.5px;font-weight:700;background:#e6ecf5;color:#41556f">From TriNova</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding:16px;color:#61756e;font-size:13.5px">
                                    <?= htmlspecialchars($doc['uploaded_by_name'] ?? 'User') ?>
                                </td>
                                <td style="padding:16px;color:#7d8e88;font-size:13px">
                                    <?= date('d M Y, H:i', strtotime($doc['created_at'])) ?>
                                </td>
                                <td style="padding:16px;text-align:right">
                                    <a href="/documents/download/<?= $doc['id'] ?>" style="background:#f0f5f3;color:#0d9488;padding:8px 14px;border-radius:10px;font-weight:700;font-size:13px;display:inline-flex;align-items:center;gap:6px">
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
