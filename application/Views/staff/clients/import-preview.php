<div style="display:flex;justify-content:space-between;gap:14px;align-items:center;flex-wrap:wrap;margin-bottom:18px"><div><h2 style="margin:0 0 5px">Import Preview</h2><p style="margin:0;color:#61756e">No database changes have been made.</p></div><a href="/staff/clients/import" style="color:#0d9488;font-weight:700">Cancel import</a></div>
<div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px"><?php foreach($preview['summary'] as $key=>$value): ?><span style="background:#fff;padding:9px 13px;border-radius:12px"><strong><?= (int)$value ?></strong> <?= htmlspecialchars(str_replace('_',' ',$key)) ?></span><?php endforeach; ?></div>
<div style="overflow:auto;background:#fff;border-radius:20px;padding:14px"><table style="width:100%;border-collapse:collapse;min-width:1050px"><thead><tr><?php foreach(['Row','Company','Director placeholders','Plan / Match','Warnings','Errors'] as $h): ?><th style="text-align:left;padding:12px;border-bottom:1px solid #e5eeeb"><?= $h ?></th><?php endforeach; ?></tr></thead><tbody><?php foreach($preview['rows'] as $row): ?><tr><td style="padding:12px;vertical-align:top"><?= (int)$row['line'] ?></td><td style="padding:12px;vertical-align:top"><strong><?= htmlspecialchars($row['data']['client_name']??'') ?></strong></td><td style="padding:12px;vertical-align:top"><strong><?= htmlspecialchars(implode('; ',$row['directors'])) ?></strong><?php if($row['director_plan']['create']??[]): ?><small style="display:block;color:#0d9488;margin-top:5px">Create: <?= htmlspecialchars(implode('; ',$row['director_plan']['create'])) ?></small><?php endif; ?><?php if($row['director_plan']['reuse']??[]): ?><small style="display:block;color:#61756e;margin-top:3px">Reuse: <?= htmlspecialchars(implode('; ',$row['director_plan']['reuse'])) ?></small><?php endif; ?><?php if($row['directors']): ?><small style="display:block;color:#a85a0d;margin-top:5px">Name-only records; no company email, phone or login will be assigned.</small><?php endif; ?></td><td style="padding:12px;vertical-align:top"><strong><?= htmlspecialchars(ucfirst($row['action'])) ?></strong><?php if($row['match']): ?><small style="display:block;color:#61756e">Matched by <?= htmlspecialchars($row['match']['field']) ?>: <?= htmlspecialchars($row['match']['value']) ?></small><?php endif; ?></td><td style="padding:12px;vertical-align:top;color:#a85a0d"><?= htmlspecialchars(implode('; ',$row['warnings'])) ?></td><td style="padding:12px;vertical-align:top;color:#b42318"><?= htmlspecialchars(implode('; ',$row['errors'])) ?></td></tr><?php endforeach; ?></tbody></table></div>
<div style="background:#fff8ee;border:1px solid #f6dfc0;padding:14px 18px;border-radius:14px;margin-top:16px;color:#8b4c12">VAT deadlines use a provisional configurable <?= (int)\Application\Config\ClientCsv::VAT_DEADLINE_OFFSET_DAYS ?>-day offset and must be confirmed before relying on reminders.</div>
<form action="/staff/clients/import/commit" method="POST" data-ajax-form data-import-commit-form style="margin-top:18px">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
    <input type="hidden" name="token" value="<?= htmlspecialchars($preview['token']) ?>">
    <button type="submit" data-loading-text="Creating client records..." style="border:0;border-radius:13px;background:#0d9488;color:#fff;padding:13px 24px;font-weight:800;cursor:pointer">Confirm and commit valid rows</button>
</form>

<div id="csvCommitOverlay" class="tn-import-loading" hidden role="status" aria-live="polite" aria-label="Client import in progress">
    <div class="tn-import-loading__card">
        <span class="tn-import-loading__spinner" aria-hidden="true"></span>
        <strong>Creating client records</strong>
        <span>Please keep this page open. This may take a moment.</span>
    </div>
</div>
<style>
    .tn-import-loading{position:fixed;inset:0;z-index:500;display:grid;place-items:center;padding:24px;background:rgba(14,38,34,.48);backdrop-filter:blur(8px)}
    .tn-import-loading[hidden]{display:none}
    .tn-import-loading__card{width:min(420px,100%);display:flex;flex-direction:column;align-items:center;gap:10px;padding:34px 30px;border-radius:24px;background:#fff;box-shadow:0 28px 80px -34px rgba(6,45,38,.65);color:#18332d;text-align:center}
    .tn-import-loading__card strong{font-size:20px}
    .tn-import-loading__card span:last-child{color:#61756e}
    .tn-import-loading__spinner{width:44px;height:44px;margin-bottom:6px;border:4px solid #d9efeb;border-top-color:#0d9488;border-radius:50%;animation:tn-import-spin .8s linear infinite}
    @keyframes tn-import-spin{to{transform:rotate(360deg)}}
    @media (prefers-reduced-motion:reduce){.tn-import-loading__spinner{animation-duration:1.8s}}
</style>
