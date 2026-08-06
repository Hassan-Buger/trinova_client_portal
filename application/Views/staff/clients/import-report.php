<?php
$summary = $result['summary'] ?? [];
$rows = $result['rows'] ?? [];
$downloadQuery = !empty($result['import_id'])
    ? 'import_id=' . (int) $result['import_id']
    : 'token=' . urlencode((string) ($result['token'] ?? ''));

$total = (int) ($summary['total'] ?? count($rows));
$created = (int) ($summary['created'] ?? 0);
$updated = (int) ($summary['updated'] ?? 0);
$failed = (int) ($summary['failed'] ?? 0);
$warnings = (int) ($summary['flagged'] ?? 0);
$directorsLinked = (int) ($summary['director_names_detected'] ?? 0);
$deadlinesCreated = (int) ($summary['deadlines_created'] ?? 0);
$hasFailures = $failed > 0;
$allFailed = $total > 0 && $failed >= $total;

if ($allFailed) {
    $statusClass = 'is-error';
    $statusTitle = 'Import needs attention';
    $statusMessage = 'No company records were saved. Review the items below, update the CSV, and try again.';
} elseif ($hasFailures) {
    $statusClass = 'is-warning';
    $statusTitle = 'Import completed with some issues';
    $statusMessage = ($created + $updated) . ' company records were saved. ' . $failed . ' could not be completed.';
} else {
    $statusClass = 'is-success';
    $statusTitle = 'Import successful';
    $statusMessage = $total === 0
        ? 'The import finished and is ready to review.'
        : ($total === 1
        ? 'Your company record is ready to review in Clients.'
        : 'All ' . $total . ' company records are ready to review in Clients.');
}

$completedTimestamp = strtotime((string) ($result['completed_at'] ?? 'now')) ?: time();
$completedDisplay = date('d M Y', $completedTimestamp) . ' at ' . date('H:i', $completedTimestamp);
$primaryStats = [
    ['value' => $total, 'label' => 'Rows processed'],
    ['value' => $created, 'label' => 'New companies'],
    ['value' => $updated, 'label' => 'Companies updated'],
    ['value' => $failed, 'label' => 'Needs attention', 'alert' => $failed > 0],
];

$resultMeta = static function (array $row): array {
    $result = (string) ($row['result'] ?? '');
    if ($result === 'failed') {
        return ['Needs attention', 'is-error'];
    }

    $label = $result === 'created' ? 'Added' : 'Updated';
    return !empty($row['warnings'])
        ? [$label . ' with notes', 'is-warning']
        : [$label, 'is-success'];
};

$friendlyWarning = static function (string $warning): string {
    if (str_contains($warning, 'Director names will be linked as incomplete placeholders')) {
        return 'Director names were linked to the company. Their contact details can be completed later.';
    }
    if (str_contains($warning, 'VAT quarter format appears unusual')) {
        return 'The VAT return schedule could not be recognised and needs review.';
    }
    if (str_contains($warning, 'Accounting year end appears invalid')) {
        return 'The accounting year-end date needs review.';
    }
    if (str_contains($warning, 'Duplicates CSV row')) {
        return 'A possible duplicate was matched to an existing company record.';
    }
    return $warning;
};
?>
<style>
    .import-report-premium{--ir-ink:#17332d;--ir-muted:#657a74;--ir-teal:#0b9488;--ir-teal-dark:#08766e;--ir-line:#e1ebe8;--ir-soft:#f4f8f7;max-width:1240px;color:var(--ir-ink)}
    .ir-overview{position:relative;overflow:hidden;padding:34px;border:1px solid #d7e8e4;border-radius:28px;background:linear-gradient(135deg,#fff 0%,#f2faf8 62%,#e4f5f1 100%);box-shadow:0 24px 58px -44px rgba(10,72,62,.72)}
    .ir-overview::after{position:absolute;right:-105px;top:-145px;width:330px;height:330px;border:62px solid rgba(11,148,136,.07);border-radius:50%;content:"";pointer-events:none}
    .ir-overview__top{position:relative;z-index:1;display:flex;align-items:flex-start;justify-content:space-between;gap:28px}
    .ir-status{display:flex;align-items:flex-start;gap:17px;min-width:0}
    .ir-status__icon{display:grid;flex:0 0 54px;width:54px;height:54px;place-items:center;border-radius:18px;background:#dff5ed;color:#16805f;box-shadow:inset 0 0 0 1px rgba(22,128,95,.08)}
    .ir-overview.is-warning .ir-status__icon{background:#fff1dc;color:#b86716}.ir-overview.is-error .ir-status__icon{background:#fee9e7;color:#b42318}
    .ir-eyebrow{margin:1px 0 7px;color:var(--ir-teal-dark);font-size:11px;font-weight:800;letter-spacing:.14em;text-transform:uppercase}
    .ir-status h2{margin:0;font-size:clamp(26px,3vw,36px);line-height:1.12;letter-spacing:-.035em}
    .ir-status__message{max-width:680px;margin:9px 0 0;color:var(--ir-muted);font-size:15px;line-height:1.6}
    .ir-batch-id{position:relative;z-index:1;flex:0 0 auto;padding:8px 12px;border:1px solid rgba(11,148,136,.16);border-radius:999px;background:rgba(255,255,255,.72);color:#54716a;font-size:12px;font-weight:750}
    .ir-file{position:relative;z-index:1;display:flex;align-items:center;gap:12px;margin-top:25px;padding:14px 16px;border:1px solid rgba(218,232,228,.9);border-radius:16px;background:rgba(255,255,255,.78)}
    .ir-file svg{flex:0 0 auto;color:var(--ir-teal)}.ir-file strong,.ir-file span{display:block}.ir-file strong{font-size:14px;overflow-wrap:anywhere}.ir-file span{margin-top:3px;color:var(--ir-muted);font-size:12px}
    .ir-actions{position:relative;z-index:1;display:flex;gap:10px;flex-wrap:wrap;margin-top:22px}
    .ir-btn{display:inline-flex;min-height:44px;align-items:center;justify-content:center;gap:8px;padding:10px 17px;border:1px solid transparent;border-radius:13px;font:inherit;font-size:13px;font-weight:800;text-decoration:none;cursor:pointer;transition:transform .18s ease,box-shadow .18s ease,background .18s ease}
    .ir-btn:hover{transform:translateY(-1px)}.ir-btn-primary{background:var(--ir-teal);color:#fff;box-shadow:0 13px 24px -16px rgba(11,148,136,.9)}.ir-btn-primary:hover{background:var(--ir-teal-dark)}.ir-btn-secondary{border-color:#d5e5e1;background:#fff;color:#31534b}.ir-btn-secondary:hover{background:#f8fbfa}
    .ir-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin:18px 0}
    .ir-stat{padding:20px 21px;border:1px solid var(--ir-line);border-radius:19px;background:#fff;box-shadow:0 14px 34px -32px rgba(13,62,54,.7)}
    .ir-stat strong{display:block;font-size:30px;line-height:1;letter-spacing:-.04em}.ir-stat span{display:block;margin-top:9px;color:var(--ir-muted);font-size:12px;font-weight:750}.ir-stat.is-alert strong{color:#b42318}
    .ir-details{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));margin-bottom:20px;border:1px solid var(--ir-line);border-radius:18px;background:#fff}
    .ir-detail{display:flex;align-items:center;gap:12px;padding:15px 18px}.ir-detail+.ir-detail{border-left:1px solid var(--ir-line)}.ir-detail__icon{display:grid;width:36px;height:36px;place-items:center;border-radius:11px;background:#edf7f4;color:var(--ir-teal)}.ir-detail strong,.ir-detail span{display:block}.ir-detail strong{font-size:15px}.ir-detail span{margin-top:2px;color:var(--ir-muted);font-size:11px;font-weight:700}
    .ir-results{overflow:hidden;border:1px solid var(--ir-line);border-radius:24px;background:#fff;box-shadow:0 24px 54px -48px rgba(13,62,54,.72)}
    .ir-results__header{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;padding:22px 24px;border-bottom:1px solid var(--ir-line)}.ir-results__header h3{margin:0;font-size:19px;letter-spacing:-.02em}.ir-results__header p{margin:5px 0 0;color:var(--ir-muted);font-size:13px}.ir-results__count{flex:0 0 auto;color:var(--ir-muted);font-size:12px;font-weight:750}
    .ir-table-wrap{overflow-x:auto}.ir-table{width:100%;border-collapse:collapse;table-layout:fixed}.ir-table th{padding:13px 18px;background:#f8faf9;color:#70837e;font-size:10px;font-weight:850;letter-spacing:.1em;text-align:left;text-transform:uppercase}.ir-table td{padding:19px 18px;border-top:1px solid #edf2f0;color:#28453e;font-size:13px;line-height:1.55;vertical-align:top;overflow-wrap:anywhere}.ir-table tbody tr:first-child td{border-top:0}.ir-company strong{display:block;color:var(--ir-ink);font-size:14px}.ir-company span{display:block;margin-top:4px;color:#85958f;font-size:11px}.ir-result{display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border-radius:999px;font-size:11px;font-weight:850;white-space:nowrap}.ir-result::before{width:6px;height:6px;border-radius:50%;background:currentColor;content:""}.ir-result.is-success{background:#e6f6ef;color:#207b59}.ir-result.is-warning{background:#fff1dc;color:#a75d14}.ir-result.is-error{background:#fee9e7;color:#b42318}
    .ir-directors{display:flex;gap:6px;flex-wrap:wrap}.ir-person{display:inline-flex;padding:4px 8px;border-radius:8px;background:#f0f6f4;color:#45665e;font-size:11px;font-weight:700}.ir-empty{color:#91a09c}.ir-note-list{margin:0;padding:0;list-style:none}.ir-note-list li{position:relative;padding-left:13px;color:#8a551d}.ir-note-list li::before{position:absolute;left:0;top:.65em;width:5px;height:5px;border-radius:50%;background:#e69a3a;content:""}.ir-note-list li+li{margin-top:5px}.ir-note-error{color:#a13c34}.ir-note-ok{color:#668079}
    .ir-empty-results{padding:38px 24px;color:var(--ir-muted);text-align:center}
    .ir-footer{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-top:18px;padding:15px 4px}.ir-footer p{margin:0;color:var(--ir-muted);font-size:12px;line-height:1.5}.ir-delete{border:0;background:transparent;color:#a44840;font:inherit;font-size:12px;font-weight:800;cursor:pointer}.ir-delete:hover{text-decoration:underline}
    .ir-sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
    @media(max-width:900px){.ir-stats{grid-template-columns:repeat(2,minmax(0,1fr))}.ir-details{grid-template-columns:1fr}.ir-detail+.ir-detail{border-top:1px solid var(--ir-line);border-left:0}.ir-overview{padding:27px}}
    @media(max-width:700px){.ir-overview__top{display:block}.ir-batch-id{display:inline-flex;margin:18px 0 0 71px}.ir-actions .ir-btn{flex:1 1 180px}.ir-results__header{align-items:flex-start}.ir-table-wrap{overflow:visible}.ir-table,.ir-table tbody,.ir-table tr,.ir-table td{display:block;width:100%}.ir-table colgroup,.ir-table thead{display:none}.ir-table tbody{padding:0 18px}.ir-table tr{padding:18px 0;border-top:1px solid var(--ir-line)}.ir-table tr:first-child{border-top:0}.ir-table td{display:grid;grid-template-columns:112px minmax(0,1fr);gap:12px;padding:7px 0;border:0}.ir-table td::before{color:#80918c;font-size:10px;font-weight:850;letter-spacing:.08em;text-transform:uppercase;content:attr(data-label)}.ir-footer{align-items:flex-start;flex-direction:column}}
    @media(max-width:480px){.ir-overview{padding:22px 19px;border-radius:22px}.ir-status{gap:13px}.ir-status__icon{width:46px;height:46px;flex-basis:46px;border-radius:15px}.ir-status h2{font-size:25px}.ir-batch-id{margin-left:59px}.ir-file{align-items:flex-start}.ir-actions{display:grid;grid-template-columns:1fr}.ir-actions .ir-btn{width:100%}.ir-stats{gap:9px}.ir-stat{padding:17px}.ir-stat strong{font-size:26px}.ir-results{border-radius:20px}.ir-results__header{display:block;padding:20px}.ir-results__count{display:block;margin-top:8px}.ir-table tbody{padding:0 16px}.ir-table td{grid-template-columns:1fr;gap:5px}.ir-table td::before{margin-top:3px}}
</style>

<div class="import-report-premium">
    <section class="ir-overview <?= htmlspecialchars($statusClass) ?>" aria-labelledby="import-status-title">
        <div class="ir-overview__top">
            <div class="ir-status">
                <span class="ir-status__icon" aria-hidden="true">
                    <?php if ($allFailed): ?>
                        <svg width="25" height="25" viewBox="0 0 24 24" fill="none"><path d="M12 8v5m0 3.5v.01M10.3 3.8 2.4 17.5A2 2 0 0 0 4.1 20h15.8a2 2 0 0 0 1.7-2.5L13.7 3.8a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <?php elseif ($hasFailures): ?>
                        <svg width="25" height="25" viewBox="0 0 24 24" fill="none"><path d="M12 7.5V13m0 3.5v.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    <?php else: ?>
                        <svg width="25" height="25" viewBox="0 0 24 24" fill="none"><path d="m7 12.5 3.2 3.2L17.5 8.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <?php endif; ?>
                </span>
                <div>
                    <p class="ir-eyebrow">Client data import</p>
                    <h2 id="import-status-title"><?= htmlspecialchars($statusTitle) ?></h2>
                    <p class="ir-status__message"><?= htmlspecialchars($statusMessage) ?></p>
                </div>
            </div>
            <?php if (!empty($result['import_id'])): ?><span class="ir-batch-id">Import #<?= (int) $result['import_id'] ?></span><?php endif; ?>
        </div>

        <div class="ir-file">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7m-5-5 5 5m-5-5v5h5M9 13h6m-6 4h6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <div><strong><?= htmlspecialchars((string) ($result['filename'] ?? 'Client CSV')) ?></strong><span>Completed <?= htmlspecialchars($completedDisplay) ?></span></div>
        </div>

        <div class="ir-actions">
            <a class="ir-btn ir-btn-primary" href="/staff/clients">
                View client records
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m9 18 6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
            <a data-no-ajax class="ir-btn ir-btn-secondary" href="/staff/clients/import/report.csv?<?= htmlspecialchars($downloadQuery) ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3v12m0 0 4-4m-4 4-4-4M5 20h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Download report
            </a>
        </div>
    </section>

    <div class="ir-stats" aria-label="Import summary">
        <?php foreach ($primaryStats as $stat): ?>
            <div class="ir-stat<?= !empty($stat['alert']) ? ' is-alert' : '' ?>">
                <strong><?= (int) $stat['value'] ?></strong>
                <span><?= htmlspecialchars($stat['label']) ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="ir-details" aria-label="Linked data summary">
        <div class="ir-detail"><span class="ir-detail__icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm8-1 2 2 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span><div><strong><?= $directorsLinked ?> directors linked</strong><span>Connected to their companies</span></div></div>
        <div class="ir-detail"><span class="ir-detail__icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M7 3v3m10-3v3M4 9h16M6 5h12a2 2 0 0 1 2 2v13H4V7a2 2 0 0 1 2-2Zm3 9 2 2 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span><div><strong><?= $deadlinesCreated ?> deadlines added</strong><span>Included in compliance tracking</span></div></div>
        <div class="ir-detail"><span class="ir-detail__icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 17v-6m0-4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span><div><strong><?= $warnings ?> <?= $warnings === 1 ? 'row has' : 'rows have' ?> notes</strong><span>Review any highlighted details below</span></div></div>
    </div>

    <section class="ir-results" aria-labelledby="company-results-title">
        <header class="ir-results__header">
            <div><h3 id="company-results-title">Company results</h3><p>A clear record of what was added or updated.</p></div>
            <span class="ir-results__count"><?= $total ?> <?= $total === 1 ? 'company' : 'companies' ?></span>
        </header>
        <?php if ($rows): ?>
            <div class="ir-table-wrap">
                <table class="ir-table">
                    <caption class="ir-sr-only">Results for each company in this import</caption>
                    <colgroup><col style="width:25%"><col style="width:17%"><col style="width:24%"><col style="width:12%"><col style="width:22%"></colgroup>
                    <thead><tr><th>Company</th><th>Outcome</th><th>Directors</th><th>Deadlines</th><th>Notes</th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $row): ?>
                        <?php [$outcomeLabel, $outcomeClass] = $resultMeta($row); $directors = $row['directors'] ?? []; ?>
                        <tr>
                            <td class="ir-company" data-label="Company"><strong><?= htmlspecialchars((string) ($row['data']['client_name'] ?? 'Unnamed company')) ?></strong><span>CSV row <?= (int) ($row['line'] ?? 0) ?></span></td>
                            <td data-label="Outcome"><span class="ir-result <?= htmlspecialchars($outcomeClass) ?>"><?= htmlspecialchars($outcomeLabel) ?></span></td>
                            <td data-label="Directors">
                                <?php if ($directors): ?><div class="ir-directors"><?php foreach ($directors as $director): ?><span class="ir-person"><?= htmlspecialchars((string) $director) ?></span><?php endforeach; ?></div><?php else: ?><span class="ir-empty">None listed</span><?php endif; ?>
                            </td>
                            <td data-label="Deadlines"><?php $rowDeadlines = (int) ($row['deadlines_created'] ?? 0); ?><strong><?= $rowDeadlines ?></strong> <?= $rowDeadlines === 1 ? 'added' : 'added' ?></td>
                            <td data-label="Notes">
                                <?php if (($row['result'] ?? '') === 'failed'): ?>
                                    <span class="ir-note-error">This company could not be saved. Download the report for support details.</span>
                                <?php elseif (!empty($row['warnings'])): ?>
                                    <ul class="ir-note-list"><?php foreach ($row['warnings'] as $warning): ?><li><?= htmlspecialchars($friendlyWarning((string) $warning)) ?></li><?php endforeach; ?></ul>
                                <?php else: ?>
                                    <span class="ir-note-ok">No issues</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="ir-empty-results">There are no company results to display for this import.</div>
        <?php endif; ?>
    </section>

    <?php if (!empty($result['import_id'])): ?>
        <footer class="ir-footer">
            <p>Imported records remain available in Clients. Removing this batch only moves companies created by this import to Trash.</p>
            <form action="/staff/clients/import/batch/delete" method="POST" data-ajax-form>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
                <input type="hidden" name="batch_id" value="<?= (int) ($result['import_id'] ?? 0) ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars((string) ($result['token'] ?? '')) ?>">
                <button type="submit" class="ir-delete" data-loading-text="Moving batch to Trash..." onclick="return confirm('Move only the companies created by this import to Trash? Existing matched clients will not be changed.')">Move imported batch to Trash</button>
            </form>
        </footer>
    <?php endif; ?>
</div>
