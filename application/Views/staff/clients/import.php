<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:22px;flex-wrap:wrap">
    <div><h2 style="margin:0 0 6px">Import Business Clients</h2><p style="margin:0;color:#61756e">Upload, map and validate a CSV before anything is written.</p></div>
    <a href="/staff/clients" style="color:#0d9488;font-weight:700">Back to clients</a>
</div>
<section style="background:#fff;border-radius:22px;padding:24px;box-shadow:0 14px 34px -24px rgba(16,54,45,.35)">
<?php if(empty($upload)): ?>
    <h3 style="margin-top:0">1. Upload CSV</h3>
    <p style="color:#61756e;font-size:14px">Business clients only. Maximum 5 MB and 5,000 rows. No invitations or emails are sent.</p>
    <p style="margin:0 0 16px;color:#61756e;font-size:13px"><strong>Safe preview:</strong> uploading and mapping a CSV does not delete or replace existing clients. Database changes happen only after you review the preview and confirm the import.</p>
    <form action="/staff/clients/import/upload" method="POST" enctype="multipart/form-data" data-ajax-form>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
        <input type="file" name="csv_file" accept=".csv,text/csv" required style="display:block;width:100%;padding:18px;border:2px dashed #b9d5ce;border-radius:16px;margin:18px 0">
        <button style="border:0;border-radius:13px;background:#0d9488;color:#fff;padding:12px 22px;font-weight:800">Upload and map columns</button>
    </form>
<?php else: ?>
    <?php $mappedCount=count(array_filter($upload['mapping']??[],static fn($index)=>$index!==''&&$index!==null)); ?>
    <h3 style="margin-top:0">2. Review column mapping</h3>
    <p style="color:#61756e;font-size:14px"><strong><?= htmlspecialchars($upload['filename']) ?></strong> · <?= (int)$upload['row_count'] ?> rows detected · <?= $mappedCount ?> portal fields mapped automatically. Review any field marked Ignore.</p>
    <form action="/staff/clients/import/preview" method="POST" data-ajax-form>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
        <input type="hidden" name="token" value="<?= htmlspecialchars($upload['token']) ?>">
        <div style="display:grid;grid-template-columns:minmax(190px,1fr) minmax(220px,1.3fr);gap:10px;align-items:center">
        <?php foreach($fields as $key=>$field): ?>
            <?php $selectedIndex=$upload['mapping'][$key]??null; ?>
            <label style="font-weight:700"><?= htmlspecialchars($field['header']) ?><?= $field['required']?' *':'' ?></label>
            <select name="mapping[<?= htmlspecialchars($key) ?>]" style="padding:11px;border:1px solid #dce8e4;border-radius:11px;background:<?= $selectedIndex===null?'#fff8ee':'#fff' ?>">
                <option value="">Ignore / not available</option>
                <?php foreach($upload['headers'] as $i=>$header): ?><option value="<?= $i ?>" <?= $selectedIndex===$i?'selected':'' ?>><?= htmlspecialchars($header) ?></option><?php endforeach; ?>
            </select>
        <?php endforeach; ?>
        </div>
        <button style="margin-top:20px;border:0;border-radius:13px;background:#0d9488;color:#fff;padding:12px 22px;font-weight:800">Validate and preview</button>
    </form>
    <?php
    $mappingDiagnostics=[];
    foreach($fields as $key=>$field){
        $index=$upload['mapping'][$key]??null;
        $mappingDiagnostics[]=[
            'portal_field'=>$field['header'],
            'required'=>(bool)$field['required'],
            'csv_column'=>$index!==null&&isset($upload['headers'][$index])?(string)$upload['headers'][$index]:'Ignore / not available',
            'mapped'=>$index!==null,
        ];
    }
    ?>
    <script>
    (() => {
      const mapping = <?= json_encode($mappingDiagnostics,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
      console.groupCollapsed(<?= json_encode('[Client CSV Import] column mapping: '.(string)$upload['filename'],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>);
      console.table(mapping);
      const unmappedRequired = mapping.filter(field => field.required && !field.mapped);
      if (unmappedRequired.length) console.error('[Client CSV Import] required fields are unmapped', unmappedRequired);
      console.groupEnd();
    })();
    </script>
<?php endif; ?>
</section>
