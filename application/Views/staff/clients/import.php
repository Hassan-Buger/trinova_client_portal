<style>
.dx{--ink:#17312d;--muted:#637a73;--teal:#0d9488;--line:#dce8e4;max-width:1120px}.dx-hero{position:relative;overflow:hidden;background:linear-gradient(135deg,#123f39,#0d766e);color:#fff;border-radius:26px;padding:32px;box-shadow:0 22px 54px -34px #123f39}.dx-hero:after{content:"";position:absolute;width:230px;height:230px;border:42px solid rgba(255,255,255,.07);border-radius:50%;right:-70px;top:-100px}.dx-kicker{font-size:12px;font-weight:900;letter-spacing:.13em;text-transform:uppercase;color:#a7e8df}.dx-hero h2{font-size:30px;margin:8px 0}.dx-hero p{max-width:670px;line-height:1.65;color:#d6f0eb;margin:0}.dx-grid{display:grid;grid-template-columns:1.4fr .8fr;gap:20px;margin-top:20px}.dx-card{background:#fff;border:1px solid rgba(24,64,54,.07);border-radius:24px;padding:26px;box-shadow:0 18px 42px -36px #17312d}.dx-drop{border:2px dashed #8bcfc6;border-radius:20px;padding:30px;text-align:center;background:#f4fbf9;transition:.2s}.dx-drop:hover{transform:translateY(-2px);border-color:var(--teal)}.dx-drop input{width:100%;margin:18px 0 0}.dx-btn{display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:13px;padding:12px 18px;font-weight:800;text-decoration:none;cursor:pointer}.dx-primary{background:var(--teal);color:#fff;box-shadow:0 10px 20px -12px #0d9488}.dx-secondary{background:#edf5f2;color:#176f67}.dx-list{margin:14px 0 0;padding-left:20px;color:var(--muted);line-height:1.75}.dx-note{margin-top:18px;padding:14px 16px;border-radius:15px;background:#fff6e9;color:#a85d1d;font-size:13px;font-weight:700}@media(max-width:780px){.dx-grid{grid-template-columns:1fr}.dx-hero{padding:25px}.dx-hero h2{font-size:25px}}
</style>
<div class="dx">
<?php if(empty($upload)): ?>
    <section class="dx-hero">
        <div class="dx-kicker">Business Clients Importer</div>
        <h2>Import Business Clients</h2>
        <p>Upload, map and validate a business client CSV before anything is written. Safe preview: database changes happen only after you review the preview and confirm the import.</p>
    </section>
    <div class="dx-grid">
        <section class="dx-card">
            <h3 style="margin-top:0;color:var(--ink)">Upload business clients CSV</h3>
            <form action="/staff/clients/import/upload" method="POST" enctype="multipart/form-data" data-ajax-form>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
                <label class="dx-drop" style="display:block">
                    <strong style="display:block;color:var(--ink);font-size:17px">Choose a CSV prepared for Business Clients Importer</strong>
                    <span style="display:block;color:var(--muted);margin-top:7px">Business clients only. Maximum 5 MB and 5,000 rows · CSV only</span>
                    <input type="file" name="csv_file" accept=".csv,text/csv" required>
                </label>
                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:18px">
                    <button class="dx-btn dx-primary">Upload and map columns</button>
                    <a class="dx-btn dx-secondary" data-no-ajax href="/staff/clients/import/template">Download template</a>
                    <a class="dx-btn dx-secondary" href="/staff/clients">Back to clients</a>
                </div>
            </form>
            <div class="dx-note">
                💡 <strong>Safe preview:</strong> Uploading and mapping a CSV does not delete or replace existing clients. Database changes happen only after you review the preview and confirm the import.
            </div>
        </section>
        <aside class="dx-card">
            <h3 style="margin-top:0;color:var(--ink)">Expected columns</h3>
            <ul class="dx-list">
                <li><strong>Client Name</strong> (required)</li>
                <li>Company No., UTR, VAT No.</li>
                <li>PAYE Ref & Office Number</li>
                <li>Director(s) / Contact(s)</li>
                <li>Email, Phone, Registered Address</li>
                <li>EOY (Year End) & Filing Deadlines</li>
                <li>Confirmation Statement & VAT Quarter</li>
            </ul>
            <p style="color:var(--muted);font-size:13px;line-height:1.6;margin-top:14px">The title row is optional. Headers may appear on row one or row two. Sensitive identifiers are never shown in the report.</p>
        </aside>
    </div>
<?php else: ?>
    <?php $mappedCount=count(array_filter($upload['mapping']??[],static fn($index)=>$index!==''&&$index!==null)); ?>
    <section class="dx-hero">
        <div class="dx-kicker">Column Mapping</div>
        <h2>Review Column Mapping</h2>
        <p><strong><?= htmlspecialchars($upload['filename']) ?></strong> · <?= (int)$upload['row_count'] ?> rows detected · <?= $mappedCount ?> portal fields mapped automatically. Review any field marked Ignore.</p>
    </section>
    <section class="dx-card" style="margin-top:20px">
        <form action="/staff/clients/import/preview" method="POST" data-ajax-form>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
            <input type="hidden" name="token" value="<?= htmlspecialchars($upload['token']) ?>">
            <div style="display:grid;grid-template-columns:minmax(190px,1fr) minmax(220px,1.3fr);gap:12px;align-items:center">
            <?php foreach($fields as $key=>$field): ?>
                <?php $selectedIndex=$upload['mapping'][$key]??null; ?>
                <label style="font-weight:700;color:var(--ink)"><?= htmlspecialchars($field['header']) ?><?= $field['required']?' *':'' ?></label>
                <select name="mapping[<?= htmlspecialchars($key) ?>]" style="padding:11px;border:1px solid #dce8e4;border-radius:11px;background:<?= $selectedIndex===null?'#fff8ee':'#fff' ?>">
                    <option value="">Ignore / not available</option>
                    <?php foreach($upload['headers'] as $i=>$header): ?><option value="<?= $i ?>" <?= $selectedIndex===$i?'selected':'' ?>><?= htmlspecialchars($header) ?></option><?php endforeach; ?>
                </select>
            <?php endforeach; ?>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:22px">
                <button class="dx-btn dx-primary">Validate and preview</button>
                <a class="dx-btn dx-secondary" href="/staff/clients/import">Cancel mapping</a>
            </div>
        </form>
    </section>
<?php endif; ?>
</div>
