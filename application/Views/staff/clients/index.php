<div class="tn-screen" style="max-width:1120px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px">
        <div>
            <h2 style="margin:0;font-size:24px;font-weight:800;letter-spacing:-.02em">Clients Overview</h2>
            <p style="margin:4px 0 0;color:#61756e;font-size:14px">Manage practice client accounts, AML status, and entity profiles.</p>
        </div>
    </div>

    <div style="background:#fff;border-radius:24px;padding:12px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -24px rgba(16,54,45,.4)">
        <table style="width:100%;border-collapse:collapse;text-align:left">
            <thead>
                <tr style="border-bottom:1px solid rgba(20,60,50,.08);color:#8a9a94;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.05em">
                    <th style="padding:14px 16px">Client Name</th>
                    <th style="padding:14px 16px">Email</th>
                    <th style="padding:14px 16px">Phone</th>
                    <th style="padding:14px 16px">AML Status</th>
                    <th style="padding:14px 16px">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clients as $c): ?>
                    <tr style="border-bottom:1px solid rgba(20,60,50,.06)">
                        <td style="padding:16px;font-weight:700;font-size:15px"><?= htmlspecialchars($c['name']) ?></td>
                        <td style="padding:16px;color:#61756e;font-size:14px"><?= htmlspecialchars($c['email']) ?></td>
                        <td style="padding:16px;color:#61756e;font-size:14px"><?= htmlspecialchars($c['phone'] ?? '—') ?></td>
                        <td style="padding:16px">
                            <span style="font-size:11.5px;font-weight:700;padding:5px 12px;border-radius:999px;<?= $c['aml_status'] === 'Complete' ? 'background:#e2f3ea;color:#3f9d6d;' : 'background:#fdecdc;color:#e07d24;' ?>">
                                <?= htmlspecialchars($c['aml_status']) ?>
                            </span>
                        </td>
                        <td style="padding:16px">
                            <a href="/staff/clients/<?= $c['id'] ?>" style="font-weight:700;font-size:13.5px;color:#0d9488">View Overview &rarr;</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
