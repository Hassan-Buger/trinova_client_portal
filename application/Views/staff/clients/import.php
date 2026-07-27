<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:22px;flex-wrap:wrap">
    <div><h2 style="margin:0 0 6px">Import Business Clients</h2><p style="margin:0;color:#61756e">Upload, map and validate a CSV before anything is written.</p></div>
    <a href="/staff/clients" style="color:#0d9488;font-weight:700">Back to clients</a>
</div>
<section style="background:#fff;border-radius:22px;padding:24px;box-shadow:0 14px 34px -24px rgba(16,54,45,.35)">
<?php if(empty($upload)): ?>
    <h3 style="margin-top:0">1. Upload CSV</h3>
    <p style="color:#61756e;font-size:14px">Business clients only. Maximum 5 MB and 5,000 rows. No invitations or emails are sent.</p>
    <form action="/staff/clients/import/upload" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
        <input type="file" name="csv_file" accept=".csv,text/csv" required style="display:block;width:100%;padding:18px;border:2px dashed #b9d5ce;border-radius:16px;margin:18px 0">
        <button style="border:0;border-radius:13px;background:#0d9488;color:#fff;padding:12px 22px;font-weight:800">Upload and map columns</button>
    </form>
<?php else: ?>
    <h3 style="margin-top:0">2. Review column mapping</h3>
    <p style="color:#61756e;font-size:14px"><strong><?= htmlspecialchars($upload['filename']) ?></strong> · <?= (int)$upload['row_count'] ?> rows detected. Uncertain columns remain unmapped.</p>
    <form action="/staff/clients/import/preview" method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>"><input type="hidden" name="token" value="<?= htmlspecialchars($upload['token']) ?>">
        <div style="display:grid;grid-template-columns:minmax(190px,1fr) minmax(220px,1.3fr);gap:10px;align-items:center">
        <?php foreach($fields as $key=>$field): ?><label style="font-weight:700"><?= htmlspecialchars($field['header']) ?><?= $field['required']?' *':'' ?></label><select name="mapping[<?= htmlspecialchars($key) ?>]" style="padding:11px;border:1px solid #dce8e4;border-radius:11px"><option value="">Ignore / not available</option><?php foreach($upload['headers'] as $i=>$header): ?><option value="<?= $i ?>" <?= ($upload['mapping'][$key]??null)===$i?'selected':'' ?>><?= htmlspecialchars($header) ?></option><?php endforeach; ?></select><?php endforeach; ?>
        </div><button style="margin-top:20px;border:0;border-radius:13px;background:#0d9488;color:#fff;padding:12px 22px;font-weight:800">Validate and preview</button>
    </form>
<?php endif; ?>
</section>
