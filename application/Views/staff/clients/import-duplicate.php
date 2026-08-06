<?php $completed = ($existing['status'] ?? '') === 'completed'; ?>
<style>
    .duplicate-import{max-width:940px}.di-card{position:relative;overflow:hidden;padding:32px;border:1px solid #dbe9e5;border-radius:26px;background:linear-gradient(135deg,#fff 0%,#f5faf8 100%);box-shadow:0 22px 55px -42px rgba(12,74,68,.7)}.di-card::after{position:absolute;right:-85px;top:-100px;width:250px;height:250px;border:46px solid rgba(13,148,136,.06);border-radius:50%;content:""}.di-status{position:relative;z-index:1;display:inline-flex;padding:6px 10px;border-radius:999px;background:#fff1dc;color:#a75d14;font-size:10px;font-weight:850;letter-spacing:.08em;text-transform:uppercase}.di-card h2{position:relative;z-index:1;margin:15px 0 9px;color:#19332f;font-size:29px;letter-spacing:-.03em}.di-lead{position:relative;z-index:1;max-width:700px;margin:0;color:#60766f;font-size:14px;line-height:1.65}.di-grid{position:relative;z-index:1;display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin:25px 0}.di-metric{min-width:0;padding:14px;border:1px solid #e3ece9;border-radius:14px;background:rgba(255,255,255,.82)}.di-metric strong,.di-metric span{display:block}.di-metric strong{color:#19332f;font-size:16px;overflow-wrap:anywhere}.di-metric span{margin-top:4px;color:#71857f;font-size:10px;font-weight:750;text-transform:uppercase;letter-spacing:.05em}.di-actions{position:relative;z-index:1;display:flex;gap:9px;flex-wrap:wrap}.di-btn{display:inline-flex;align-items:center;justify-content:center;min-height:43px;padding:10px 15px;border:1px solid transparent;border-radius:12px;text-decoration:none;font-size:12px;font-weight:800}.di-primary{background:#0d9488;color:#fff}.di-primary:hover{background:#08766e;color:#fff}.di-secondary{border-color:#dbe7e3;background:#fff;color:#48645d}.di-secondary:hover{color:#08766e}@media(max-width:720px){.di-card{padding:24px 20px}.di-grid{grid-template-columns:1fr 1fr}.di-actions .di-btn{flex:1 1 180px}}@media(max-width:420px){.di-grid{grid-template-columns:1fr}.di-actions{display:grid}.di-actions .di-btn{width:100%}}
</style>

<div class="duplicate-import">
    <section class="di-card">
        <span class="di-status"><?= $completed ? 'Already imported' : 'Import in progress' ?></span>
        <h2><?= $completed ? 'Duplicate import prevented' : 'This CSV is already being prepared' ?></h2>
        <p class="di-lead"><?= $completed
            ? 'This CSV was imported previously and at least one of its company records is still active. No duplicate company, director, link, deadline, email, or notification was created.'
            : 'Another upload of this CSV is pending or being processed. No second import record was created.' ?></p>

        <div class="di-grid">
            <div class="di-metric"><strong><?= htmlspecialchars((string)($existing['filename'] ?? '')) ?></strong><span>Original filename</span></div>
            <div class="di-metric"><strong><?= htmlspecialchars((string)($existing['imported_by'] ?? 'Staff user')) ?></strong><span>Imported by</span></div>
            <div class="di-metric"><strong><?= !empty($existing['completed_at']) ? htmlspecialchars(date('d M Y, H:i', strtotime((string)$existing['completed_at']))) : 'In progress' ?></strong><span>Import time</span></div>
            <div class="di-metric"><strong><?= (int)($existing['total_rows'] ?? 0) ?></strong><span>Total rows</span></div>
            <div class="di-metric"><strong><?= (int)($existing['created'] ?? 0) ?></strong><span>Created</span></div>
            <div class="di-metric"><strong><?= (int)($existing['updated'] ?? 0) ?></strong><span>Updated</span></div>
            <div class="di-metric"><strong><?= (int)($existing['flagged'] ?? 0) ?></strong><span>Flagged</span></div>
        </div>

        <div class="di-actions">
            <?php if ($completed && !empty($existing['report_url'])): ?><a class="di-btn di-primary" href="<?= htmlspecialchars((string)$existing['report_url']) ?>">View original report</a><?php endif; ?>
            <a class="di-btn di-secondary" href="/staff/clients">Back to clients</a>
            <a class="di-btn di-secondary" href="/staff/clients/import">Choose another CSV</a>
        </div>
    </section>
</div>
