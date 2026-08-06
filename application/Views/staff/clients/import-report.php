<?php
$summary=$result['summary']??[];
$downloadQuery=!empty($result['import_id'])?'import_id='.(int)$result['import_id']:'token='.urlencode((string)($result['token']??''));
$primary=['total'=>'Rows processed','created'=>'Companies created','updated'=>'Companies matched','failed'=>'Failed'];
$secondary=['placeholder_directors_created'=>'Directors created','placeholder_directors_reused'=>'Directors reused','deadlines_created'=>'Deadlines created','flagged'=>'Rows with warnings'];
$resultLabel=static function(array $row):string{if(($row['result']??'')==='failed')return 'Failed';return !empty($row['warnings'])?'Imported with warnings':'Imported';};
$resultClass=static function(array $row):string{if(($row['result']??'')==='failed')return ' is-failed';return !empty($row['warnings'])?' has-warnings':'';};
$diagnosticRows=array_map(static fn(array $row):array=>[
    'row'=>(int)($row['line']??0),
    'company'=>(string)($row['data']['client_name']??''),
    'result'=>(string)($row['result']??'unknown'),
    'stage'=>(string)(($row['failure_stage']??'')?:((($row['result']??'')==='failed')?'unknown (older report)':'completed')),
    'reference'=>(string)($row['diagnostic_reference']??''),
    'database_state'=>(string)($row['database_state']??''),
    'database_driver_code'=>(string)($row['database_driver_code']??''),
    'warnings'=>implode('; ',$row['warnings']??[]),
    'errors'=>implode('; ',$row['errors']??[]),
],$result['rows']??[]);
?>
<style>
.import-report{--ink:#19332f;--muted:#667b75;--teal:#0d9488;--line:#dfebe7;max-width:1180px}.ir-hero{position:relative;overflow:hidden;background:linear-gradient(135deg,#123f3a 0%,#0b766d 72%,#109f91 100%);color:#fff;border-radius:24px;padding:28px 30px;margin-bottom:18px;box-shadow:0 22px 44px -34px rgba(4,76,70,.9)}.ir-hero:after{content:"";position:absolute;width:240px;height:240px;border:45px solid rgba(255,255,255,.07);border-radius:50%;right:-80px;top:-110px}.ir-kicker{font-size:12px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;color:#a8e7df}.ir-hero h2{margin:6px 0 5px;font-size:28px;line-height:1.15}.ir-hero p{margin:0;color:#d5efeb}.ir-actions{position:relative;z-index:1;display:flex;gap:9px;flex-wrap:wrap;margin-top:20px}.ir-btn{display:inline-flex;align-items:center;justify-content:center;border-radius:11px;padding:10px 14px;font-weight:800;text-decoration:none}.ir-btn-primary{background:#fff;color:#087d73}.ir-btn-secondary{background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.24)}.ir-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:11px;margin-bottom:12px}.ir-stat{background:#fff;border:1px solid var(--line);border-radius:16px;padding:16px}.ir-stat strong{display:block;font-size:25px;color:var(--ink);line-height:1}.ir-stat span{display:block;margin-top:7px;font-size:12px;color:var(--muted);font-weight:700}.ir-secondary{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px}.ir-chip{background:#eef7f4;color:#42645c;padding:8px 11px;border-radius:999px;font-size:12px;font-weight:700}.ir-table-card{background:#fff;border:1px solid var(--line);border-radius:20px;overflow:hidden}.ir-table-scroll{overflow-x:auto}.ir-table{width:100%;border-collapse:collapse;table-layout:fixed}.ir-table th{padding:14px;text-align:left;background:#f7faf9;color:#536b65;font-size:11px;letter-spacing:.08em;text-transform:uppercase}.ir-table td{padding:15px 14px;border-top:1px solid #edf3f1;vertical-align:top;color:var(--ink);font-size:13px;line-height:1.45;overflow-wrap:anywhere}.ir-result{display:inline-flex;background:#e4f5ed;color:#27845b;border-radius:999px;padding:5px 9px;font-weight:800}.ir-result.has-warnings{background:#fff1dc;color:#b86716}.ir-result.is-failed{background:#fee9e7;color:#b42318}.ir-warning{color:#9a5a16}.ir-link-result{color:#557069}.ir-link-result strong{color:var(--ink)}@media(max-width:860px){.ir-stats{grid-template-columns:repeat(2,1fr)}.ir-table{min-width:900px}.ir-hero{padding:24px 21px}}@media(max-width:480px){.ir-stats{grid-template-columns:1fr 1fr}.ir-stat{padding:13px}.ir-stat strong{font-size:21px}.ir-actions .ir-btn{width:100%}}
</style>
<div class="import-report">
  <section class="ir-hero">
    <div class="ir-kicker">Verified import record<?= !empty($result['import_id'])?' #'.(int)$result['import_id']:'' ?></div>
    <h2>Import complete</h2>
    <p><?= htmlspecialchars((string)($result['filename']??'Client CSV')) ?> · completed <?= htmlspecialchars(date('d M Y, H:i',strtotime((string)($result['completed_at']??'now')))) ?></p>
    <div class="ir-actions">
      <a data-no-ajax class="ir-btn ir-btn-primary" href="/staff/clients/import/report.csv?<?= htmlspecialchars($downloadQuery) ?>">Download report CSV</a>
      <a class="ir-btn ir-btn-secondary" href="/staff/clients">View clients</a>
      <form action="/staff/clients/import/batch/delete" method="POST" data-ajax-form style="margin:0;display:inline-block">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
        <input type="hidden" name="batch_id" value="<?= (int)($result['import_id'] ?? 0) ?>">
        <input type="hidden" name="token" value="<?= htmlspecialchars((string)($result['token'] ?? '')) ?>">
        <button type="submit" data-loading-text="Moving batch to Trash..." onclick="return confirm('Move only the client companies created by this import batch to Trash? Matched existing clients will not be changed.')" class="ir-btn" style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;cursor:pointer">Delete Import Batch</button>
      </form>
    </div>
  </section>
  <div class="ir-stats"><?php foreach($primary as $key=>$label): ?><div class="ir-stat"><strong><?= (int)($summary[$key]??0) ?></strong><span><?= htmlspecialchars($label) ?></span></div><?php endforeach; ?></div>
  <div class="ir-secondary"><?php foreach($secondary as $key=>$label): ?><span class="ir-chip"><strong><?= (int)($summary[$key]??0) ?></strong> <?= htmlspecialchars($label) ?></span><?php endforeach; ?></div>
  <section class="ir-table-card"><div class="ir-table-scroll"><table class="ir-table"><colgroup><col style="width:7%"><col style="width:17%"><col style="width:11%"><col style="width:20%"><col style="width:15%"><col style="width:30%"></colgroup><thead><tr><th>Row</th><th>Company</th><th>Result</th><th>Director placeholders</th><th>Link result</th><th>Warnings / errors</th></tr></thead><tbody><?php foreach(($result['rows']??[]) as $row): ?><tr><td><?= (int)$row['line'] ?></td><td><strong><?= htmlspecialchars($row['data']['client_name']??'') ?></strong></td><td><span class="ir-result<?= $resultClass($row) ?>"><?= htmlspecialchars($resultLabel($row)) ?></span></td><td><?= htmlspecialchars(implode('; ',$row['directors']??[])) ?></td><td class="ir-link-result"><strong><?= (int)($row['placeholder_directors_created']??0) ?></strong> created · <strong><?= (int)($row['placeholder_directors_reused']??0) ?></strong> reused<br><?= (int)($row['directors_needing_details']??0) ?> need details</td><td class="ir-warning"><?= htmlspecialchars(implode('; ',array_merge($row['warnings']??[],$row['errors']??[]))) ?: 'No issues' ?></td></tr><?php endforeach; ?></tbody></table></div></section>
</div>
<script>
(() => {
  const rows = <?= json_encode($diagnosticRows,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
  console.groupCollapsed(`Client CSV import #<?= (int)($result['import_id']??0) ?> diagnostics (${rows.length} rows)`);
  console.table(rows);
  const failures = rows.filter(row => row.result === 'failed');
  if (failures.length) console.error('Failed client CSV rows', failures);
  console.groupEnd();
})();
</script>
