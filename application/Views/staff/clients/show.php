<?php
$csrfToken = \Application\Core\Session::csrfToken();
$clientName = (string) ($client['name'] ?? 'Client');
$nameParts = preg_split('/\s+/', trim($clientName)) ?: [];
$initials = '';
foreach (array_slice($nameParts, 0, 2) as $part) {
    $initials .= strtoupper(substr($part, 0, 1));
}
$initials = $initials ?: 'CL';

$visibleAttributes = static function (array $entity): array {
    $visible = [];
    foreach (($entity['attributes'] ?? []) as $key => $attribute) {
        if ((string) $key === 'csv_source_data') {
            continue;
        }

        if (is_array($attribute)) {
            $label = trim((string) ($attribute['label'] ?? ''));
            $value = $attribute['value'] ?? '';
        } else {
            $label = ucwords(str_replace('_', ' ', (string) $key));
            $value = $attribute;
        }

        if (!is_scalar($value)) {
            continue;
        }
        $value = trim((string) $value);
        if ($value === '' || strcasecmp($label, 'Original CSV data') === 0) {
            continue;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            $timestamp = strtotime($value);
            if ($timestamp !== false) {
                $value = date('d M Y', $timestamp);
            }
        }
        $visible[] = ['label' => $label ?: 'Reference', 'value' => $value];
    }
    return $visible;
};

$summaryItems = [
    ['value' => count($entities), 'label' => count($entities) === 1 ? 'Linked entity' : 'Linked entities'],
    ['value' => count($outstanding), 'label' => 'Outstanding items', 'alert' => !empty($outstanding)],
    ['value' => count($documents), 'label' => 'Documents'],
    ['value' => count($meetings), 'label' => 'Meetings'],
];
?>

<style>
    .client-profile{--cp-ink:#17332d;--cp-muted:#687c76;--cp-teal:#0d9488;--cp-teal-dark:#08766e;--cp-navy:#41556f;--cp-line:#e0ebe7;--cp-soft:#f5f9f7;max-width:1240px;color:var(--cp-ink)}
    .cp-toast{display:none;margin-bottom:16px;padding:14px 18px;border-radius:15px;background:#e4f5ed;color:#287a52;font-size:13px;font-weight:750;box-shadow:0 12px 28px -22px rgba(16,54,45,.5)}
    .cp-hero{position:relative;overflow:hidden;padding:28px 30px;border:1px solid #d8e8e4;border-radius:27px;background:linear-gradient(135deg,#fff 0%,#f4faf8 64%,#e8f5f2 100%);box-shadow:0 24px 58px -46px rgba(10,72,62,.72)}
    .cp-hero::after{position:absolute;right:-90px;top:-155px;width:310px;height:310px;border:58px solid rgba(13,148,136,.06);border-radius:50%;content:"";pointer-events:none}
    .cp-hero__top{position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between;gap:24px}
    .cp-identity{display:flex;align-items:center;gap:17px;min-width:0}.cp-avatar{display:grid;flex:0 0 58px;width:58px;height:58px;place-items:center;border-radius:18px;background:var(--cp-navy);color:#fff;font-size:19px;font-weight:800;letter-spacing:.02em;box-shadow:0 14px 28px -18px rgba(65,85,111,.85)}
    .cp-name-row{display:flex;align-items:center;gap:10px;flex-wrap:wrap}.cp-name-row h2{margin:0;font-size:clamp(23px,2.5vw,31px);line-height:1.15;letter-spacing:-.035em}.cp-active{display:inline-flex;align-items:center;gap:6px;padding:5px 10px;border-radius:999px;background:#e3f5ed;color:#247c59;font-size:10px;font-weight:850;text-transform:uppercase;letter-spacing:.04em}.cp-active::before{width:6px;height:6px;border-radius:50%;background:#2e9b6e;content:""}.cp-contact{display:flex;gap:8px;flex-wrap:wrap;margin-top:7px;color:var(--cp-muted);font-size:13px}.cp-contact a{color:inherit}.cp-contact a:hover{color:var(--cp-teal-dark)}.cp-contact__divider{color:#b2bfbb}
    .cp-actions{display:flex;align-items:center;gap:9px;flex:0 0 auto}.cp-button{display:inline-flex;min-height:43px;align-items:center;justify-content:center;padding:10px 16px;border:1px solid transparent;border-radius:13px;font:inherit;font-size:12px;font-weight:800;cursor:pointer;text-decoration:none}.cp-button--primary{background:var(--cp-teal);color:#fff;box-shadow:0 13px 24px -16px rgba(13,148,136,.9)}.cp-button--primary:hover{background:var(--cp-teal-dark);color:#fff}.cp-button--soft{border-color:#d9e6e2;background:rgba(255,255,255,.82);color:#48635c}.cp-button--soft:hover{background:#fff;color:var(--cp-teal-dark)}
    .cp-menu{position:relative}.cp-menu summary{list-style:none}.cp-menu summary::-webkit-details-marker{display:none}.cp-menu__panel{position:absolute;right:0;top:50px;z-index:25;width:210px;padding:8px;border:1px solid var(--cp-line);border-radius:14px;background:#fff;box-shadow:0 20px 45px -25px rgba(16,54,45,.55)}.cp-menu__panel a,.cp-menu__panel button{display:block;width:100%;padding:10px 11px;border:0;border-radius:9px;background:transparent;font:inherit;font-size:12px;font-weight:750;text-align:left;cursor:pointer}.cp-menu__panel a:hover{background:#edf7f4}.cp-menu__danger{color:#b42318}.cp-menu__danger:hover{background:#fff1f0!important}
    .cp-summary{position:relative;z-index:1;display:grid;grid-template-columns:repeat(4,minmax(0,1fr));margin-top:24px;border:1px solid rgba(216,232,228,.95);border-radius:17px;background:rgba(255,255,255,.78)}.cp-summary__item{padding:15px 18px}.cp-summary__item+.cp-summary__item{border-left:1px solid var(--cp-line)}.cp-summary__item strong,.cp-summary__item span{display:block}.cp-summary__item strong{font-size:20px;letter-spacing:-.03em}.cp-summary__item span{margin-top:3px;color:var(--cp-muted);font-size:10px;font-weight:750;text-transform:uppercase;letter-spacing:.06em}.cp-summary__item.is-alert strong{color:#b86716}
    .cp-layout{display:flex;flex-direction:column;gap:20px;margin-top:20px}.cp-main{display:flex;min-width:0;flex-direction:column;gap:18px;width:100%}.cp-side{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:18px;width:100%;position:static}
    .cp-panel{overflow:hidden;border:1px solid var(--cp-line);border-radius:22px;background:#fff;box-shadow:0 18px 42px -39px rgba(13,62,54,.72)}.cp-panel__header{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:20px 22px;border-bottom:1px solid var(--cp-line)}.cp-panel__header h3{margin:0;font-size:16px;letter-spacing:-.015em}.cp-panel__header p{margin:5px 0 0;color:var(--cp-muted);font-size:11px;line-height:1.45}.cp-count{flex:0 0 auto;padding:5px 9px;border-radius:999px;background:#edf6f3;color:#547169;font-size:10px;font-weight:800}.cp-panel__body{padding:20px 22px}.cp-empty{margin:0;color:#84958f;font-size:12px;line-height:1.55}.cp-empty--success{color:#287a52;font-weight:700}
    .cp-entity+.cp-entity{margin-top:16px;padding-top:17px;border-top:1px solid var(--cp-line)}.cp-entity__head{display:flex;align-items:flex-start;justify-content:space-between;gap:14px}.cp-entity__title{min-width:0}.cp-entity__title strong{display:block;font-size:16px;line-height:1.35}.cp-entity__meta{display:flex;gap:6px;flex-wrap:wrap;margin-top:7px}.cp-tag{display:inline-flex;padding:4px 8px;border-radius:999px;background:#eff5f3;color:#57716a;font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.04em}.cp-entity__delete{flex:0 0 auto;padding:5px 7px;border:0;background:transparent;color:#a94c45;font:inherit;font-size:10px;font-weight:800;cursor:pointer}.cp-entity__delete:hover{text-decoration:underline}
    .cp-facts{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:9px 14px;margin-top:15px}.cp-fact{min-width:0;padding:10px 11px;border-radius:11px;background:var(--cp-soft)}.cp-fact span,.cp-fact strong{display:block}.cp-fact span{color:#7a8d87;font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.05em}.cp-fact strong{margin-top:4px;color:#29473f;font-size:11px;line-height:1.4;overflow-wrap:anywhere}
    .cp-subsection{margin-top:17px;padding-top:16px;border-top:1px solid #edf2f0}.cp-subsection__head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:9px}.cp-subsection__head strong{font-size:11px;text-transform:uppercase;letter-spacing:.055em}.cp-subsection__head span{color:#879791;font-size:10px}.cp-contact-row,.cp-access-row{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:9px 0}.cp-contact-row+.cp-contact-row,.cp-access-row+.cp-access-row{border-top:1px solid #f0f4f2}.cp-person strong,.cp-person span{display:block}.cp-person strong{font-size:12px}.cp-person span{margin-top:2px;color:var(--cp-muted);font-size:10px;overflow-wrap:anywhere}.cp-status{flex:0 0 auto;padding:4px 7px;border-radius:999px;font-size:9px;font-weight:800}.cp-status--warning{background:#fff1dc;color:#a75d14}.cp-status--success{background:#e4f5ed;color:#247c59}.cp-remove{border:0;background:transparent;color:#ad4d45;font:inherit;font-size:10px;font-weight:800;cursor:pointer}
    .cp-inline-form{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:8px;margin-top:10px}.cp-field{width:100%;min-width:0;padding:10px 11px;border:1px solid #dce8e4;border-radius:11px;background:#fbfdfc;color:var(--cp-ink);font:inherit;font-size:11px}.cp-mini-button{border:0;border-radius:10px;background:var(--cp-teal);color:#fff;padding:0 12px;font:inherit;font-size:11px;font-weight:800;cursor:pointer}
    .cp-deadline-group+.cp-deadline-group{margin-top:17px;padding-top:17px;border-top:1px solid var(--cp-line)}.cp-deadline-group__head{display:flex;align-items:center;justify-content:space-between;gap:12px}.cp-deadline-group__head strong{font-size:14px}.cp-deadline-group__head span{color:var(--cp-muted);font-size:10px}.cp-deadline-list{margin-top:10px}.cp-deadline{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:8px 0;font-size:12px}.cp-deadline+.cp-deadline{border-top:1px solid #f0f4f2}.cp-deadline strong{white-space:nowrap}.cp-deadline-form{display:grid;grid-template-columns:minmax(0,1fr) 135px auto;gap:8px;margin-top:12px}.cp-deadline-form .cp-mini-button{min-height:38px}
    .cp-table-wrap{overflow-x:auto}.cp-table{width:100%;border-collapse:collapse;table-layout:fixed}.cp-table th{padding:11px 14px;background:#f8faf9;color:#7a8d87;font-size:9px;font-weight:850;letter-spacing:.08em;text-align:left;text-transform:uppercase}.cp-table td{padding:13px 14px;border-top:1px solid #edf2f0;font-size:11px;line-height:1.45;vertical-align:middle;overflow-wrap:anywhere}.cp-table tbody tr:first-child td{border-top:0}.cp-file-name{font-weight:750}.cp-doc-type{display:inline-flex;padding:4px 7px;border-radius:999px;background:#edf7f4;color:var(--cp-teal-dark);font-size:9px;font-weight:800}.cp-doc-type.is-shared{background:#edf1f7;color:var(--cp-navy)}.cp-download{font-weight:800}
    .cp-meeting{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:11px 0}.cp-meeting+.cp-meeting{border-top:1px solid #edf2f0}.cp-meeting strong,.cp-meeting span{display:block}.cp-meeting strong{font-size:12px}.cp-meeting span{margin-top:3px;color:var(--cp-muted);font-size:10px}.cp-meeting__status{flex:0 0 auto;padding:4px 8px;border-radius:999px;background:#e4f5ed;color:#247c59;font-size:9px;font-weight:800}
    .cp-attention{border-color:#f1dfc7;background:#fffaf3}.cp-attention .cp-panel__header{border-color:#f1e5d6}.cp-outstanding{display:grid;grid-template-columns:8px minmax(0,1fr) auto;gap:10px;align-items:center;padding:9px 0}.cp-outstanding+.cp-outstanding{border-top:1px solid #f2e7d9}.cp-outstanding__dot{width:7px;height:7px;border-radius:50%;background:#e99139}.cp-outstanding strong{font-size:11px}.cp-outstanding time{color:#a45d19;font-size:9px;font-weight:800;white-space:nowrap}
    .cp-notes{border-color:#f0dfc9;background:#fffaf3}.cp-staff-label{display:inline-flex;padding:4px 8px;border-radius:999px;background:#fbe8d4;color:#a75d14;font-size:9px;font-weight:850;letter-spacing:.08em;text-transform:uppercase}.cp-note-copy{margin:11px 0 0;color:#634c32;font-size:11px;line-height:1.6;white-space:pre-wrap;overflow-wrap:anywhere}
    .cp-log{padding:10px 0}.cp-log+.cp-log{border-top:1px solid #edf2f0}.cp-log__top{display:flex;align-items:center;justify-content:space-between;gap:10px}.cp-log__top strong{color:var(--cp-teal-dark);font-size:10px}.cp-log__top time{color:#879791;font-size:9px}.cp-log p{margin:5px 0 0;color:var(--cp-muted);font-size:9px;line-height:1.45;overflow-wrap:anywhere}
    .cp-modal{display:none;position:fixed;inset:0;z-index:199;align-items:center;justify-content:center;padding:20px;background:rgba(18,40,35,.48);backdrop-filter:blur(7px)}.cp-modal__card{width:100%;max-width:680px;max-height:92vh;overflow:auto;padding:28px;border-radius:23px;background:#fff;box-shadow:0 30px 75px -34px rgba(0,0,0,.5);animation:tnpop .22s ease}.cp-modal__card--small{max-width:460px}.cp-modal__head{display:flex;align-items:center;justify-content:space-between;gap:20px;margin-bottom:20px}.cp-modal__head h3{margin:0;font-size:18px;letter-spacing:-.02em}.cp-close{width:34px;height:34px;border:0;border-radius:10px;background:#f1f5f3;color:#6e817b;font-size:21px;line-height:1;cursor:pointer}.cp-form-group{margin-bottom:14px}.cp-form-group label,.cp-form-label{display:block;margin-bottom:6px;color:#3d5750;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.045em}.cp-input{width:100%;padding:12px 13px;border:1px solid #dce8e4;border-radius:12px;background:#fbfdfc;color:var(--cp-ink);font:inherit;font-size:12px}.cp-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}.cp-form-dates{margin-top:18px;padding-top:17px;border-top:1px solid var(--cp-line)}.cp-form-date-row{display:grid;grid-template-columns:minmax(0,1fr) 155px;gap:9px;margin-top:8px}.cp-modal__actions{display:flex;justify-content:flex-end;gap:10px;margin-top:22px}.cp-danger-copy{margin:0;color:var(--cp-muted);font-size:12px;line-height:1.6}.cp-danger-copy strong{color:var(--cp-ink)}.cp-danger-input{border-color:#f3b4b0;background:#fffafa}.cp-button--danger{background:#c83d34;color:#fff}.cp-button--danger:hover{background:#ae3029;color:#fff}
    .cp-sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
    @media(max-width:1020px){.cp-layout{grid-template-columns:1fr}.cp-side{display:grid;grid-template-columns:repeat(2,minmax(0,1fr))}.cp-side .cp-panel:last-child{grid-column:1/-1}}
    @media(max-width:760px){.cp-hero{padding:22px 19px}.cp-hero__top{align-items:flex-start;flex-direction:column}.cp-actions{width:100%}.cp-actions>.cp-button{flex:1}.cp-menu{flex:1}.cp-menu>summary{width:100%}.cp-summary{grid-template-columns:repeat(2,minmax(0,1fr))}.cp-summary__item:nth-child(3){border-left:0;border-top:1px solid var(--cp-line)}.cp-summary__item:nth-child(4){border-top:1px solid var(--cp-line)}.cp-side{display:flex;flex-direction:column}.cp-facts{grid-template-columns:1fr 1fr}.cp-deadline-form{grid-template-columns:1fr 1fr}.cp-deadline-form .cp-mini-button{grid-column:1/-1;min-height:40px}.cp-form-grid{grid-template-columns:1fr}.cp-form-date-row{grid-template-columns:1fr}.cp-panel__header,.cp-panel__body{padding-left:18px;padding-right:18px}}
    @media(max-width:520px){.cp-identity{align-items:flex-start}.cp-avatar{width:49px;height:49px;flex-basis:49px;border-radius:15px;font-size:16px}.cp-contact{display:block}.cp-contact__divider{display:none}.cp-contact a,.cp-contact span:not(.cp-contact__divider){display:block;margin-top:3px}.cp-actions{display:grid;grid-template-columns:1fr 1fr}.cp-name-row h2{font-size:23px}.cp-facts{grid-template-columns:1fr}.cp-entity__head{align-items:flex-start}.cp-inline-form{grid-template-columns:1fr}.cp-inline-form .cp-mini-button{min-height:38px}.cp-table,.cp-table tbody,.cp-table tr,.cp-table td{display:block;width:100%}.cp-table colgroup,.cp-table thead{display:none}.cp-table tbody{padding:0 17px}.cp-table tr{padding:11px 0;border-top:1px solid var(--cp-line)}.cp-table tr:first-child{border-top:0}.cp-table td{display:grid;grid-template-columns:86px minmax(0,1fr);gap:9px;padding:5px 0;border:0}.cp-table td::before{color:#82938e;font-size:8px;font-weight:850;letter-spacing:.08em;text-transform:uppercase;content:attr(data-label)}.cp-modal__card{padding:22px 18px}.cp-modal__actions{display:grid;grid-template-columns:1fr 1fr}.cp-modal__actions .cp-button{width:100%}}
</style>

<div class="tn-screen client-profile">
    <div id="toastSuccess" class="cp-toast"></div>

    <div style="margin-bottom:16px;">
        <a href="/staff/clients" data-ajax-link style="display:inline-flex;align-items:center;gap:7px;font-weight:700;font-size:13.5px;color:#0d9488;background:#fff;padding:9px 18px;border-radius:14px;border:1px solid #e0e9e5;box-shadow:0 1px 2px rgba(16,54,45,.04);text-decoration:none;transition:all .15s">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            Back to Clients
        </a>
    </div>

    <section class="cp-hero" aria-labelledby="client-profile-name">
        <div class="cp-hero__top">
            <div class="cp-identity">
                <div class="cp-avatar" aria-hidden="true"><?= htmlspecialchars($initials) ?></div>
                <div>
                    <div class="cp-name-row"><h2 id="client-profile-name"><?= htmlspecialchars($clientName) ?></h2><span class="cp-active">Active client</span></div>
                    <div class="cp-contact">
                        <a href="mailto:<?= htmlspecialchars((string) ($client['email'] ?? '')) ?>"><?= htmlspecialchars((string) ($client['email'] ?? 'No email')) ?></a>
                        <span class="cp-contact__divider">&middot;</span>
                        <span><?= htmlspecialchars((string) (($client['phone'] ?? '') ?: 'No phone recorded')) ?></span>
                    </div>
                </div>
            </div>
            <div class="cp-actions">
                <button type="button" class="cp-button cp-button--primary" onclick="document.getElementById('addEntityModal').style.display='flex'">Link business entity</button>
                <details class="cp-menu">
                    <summary class="cp-button cp-button--soft">Account actions</summary>
                    <div class="cp-menu__panel">
                        <a href="/staff/users?q=<?= urlencode((string) ($client['email'] ?? '')) ?>">Manage portal user</a>
                        <button type="button" class="cp-menu__danger" onclick="document.getElementById('deleteClientModal').style.display='flex'">Permanently delete client</button>
                    </div>
                </details>
            </div>
        </div>

        <div class="cp-summary" aria-label="Client summary">
            <?php foreach ($summaryItems as $item): ?><div class="cp-summary__item<?= !empty($item['alert']) ? ' is-alert' : '' ?>"><strong><?= (int) $item['value'] ?></strong><span><?= htmlspecialchars($item['label']) ?></span></div><?php endforeach; ?>
        </div>
    </section>

    <div class="cp-layout">
        <main class="cp-main">
            <section class="cp-panel" aria-labelledby="entities-title">
                <header class="cp-panel__header"><div><h3 id="entities-title">Business entities</h3><p>Company records, references, directors, and portal access.</p></div><span class="cp-count"><?= count($entities) ?> linked</span></header>
                <div class="cp-panel__body">
                    <?php if (empty($entities)): ?>
                        <p class="cp-empty">No business entities are linked to this client yet.</p>
                    <?php else: ?>
                        <?php foreach ($entities as $b): ?>
                            <?php $entityId = (int) $b['id']; $attributes = $visibleAttributes($b); $contacts = $contactsByEntity[$entityId] ?? []; $directors = $directorsByEntity[$entityId] ?? []; ?>
                            <article class="cp-entity">
                                <div class="cp-entity__head">
                                    <div class="cp-entity__title">
                                        <strong><?= htmlspecialchars((string) $b['company_name']) ?></strong>
                                        <div class="cp-entity__meta"><span class="cp-tag"><?= htmlspecialchars((string) $b['entity_type']) ?></span><span class="cp-tag"><?= htmlspecialchars(ucfirst((string) $b['entity_scope'])) ?> record</span></div>
                                    </div>
                                    <button type="button" class="cp-entity__delete" onclick="tnDeleteEntity(<?= $entityId ?>)">Move to Trash</button>
                                </div>

                                <?php if (!empty($b['company_number']) || !empty($b['tax_reference']) || $attributes): ?>
                                    <div class="cp-facts">
                                        <?php if (!empty($b['company_number'])): ?><div class="cp-fact"><span>Company number</span><strong><?= htmlspecialchars((string) $b['company_number']) ?></strong></div><?php endif; ?>
                                        <?php if (!empty($b['tax_reference'])): ?><div class="cp-fact"><span>Tax reference</span><strong><?= htmlspecialchars((string) $b['tax_reference']) ?></strong></div><?php endif; ?>
                                        <?php foreach ($attributes as $attribute): ?><div class="cp-fact"><span><?= htmlspecialchars($attribute['label']) ?></span><strong><?= htmlspecialchars($attribute['value']) ?></strong></div><?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (($b['entity_scope'] ?? '') === 'company'): ?>
                                    <div class="cp-subsection">
                                        <div class="cp-subsection__head"><strong>Directors and contacts</strong><span><?= count($contacts) ?> records</span></div>
                                        <?php if (empty($contacts)): ?><p class="cp-empty">No director or contact records are linked.</p><?php endif; ?>
                                        <?php foreach ($contacts as $contact): ?>
                                            <div class="cp-contact-row"><div class="cp-person"><strong><?= htmlspecialchars((string) $contact['name']) ?></strong><?php if (!empty($contact['email'])): ?><span><?= htmlspecialchars((string) $contact['email']) ?></span><?php endif; ?></div><span class="cp-status <?= !empty($contact['needs_contact_details']) ? 'cp-status--warning' : 'cp-status--success' ?>"><?= !empty($contact['needs_contact_details']) ? 'Details required' : 'Complete' ?></span></div>
                                        <?php endforeach; ?>
                                    </div>

                                    <div class="cp-subsection">
                                        <div class="cp-subsection__head"><strong>Portal access</strong><span><?= count($directors) ?> users</span></div>
                                        <?php if (empty($directors)): ?><p class="cp-empty">No portal users have access to this company.</p><?php endif; ?>
                                        <?php foreach ($directors as $director): ?>
                                            <form action="/staff/clients/unlink-director" method="POST" data-ajax-form class="cp-access-row">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="entity_id" value="<?= $entityId ?>"><input type="hidden" name="user_id" value="<?= (int) $director['id'] ?>"><input type="hidden" name="return_client_id" value="<?= (int) $client['id'] ?>">
                                                <div class="cp-person"><strong><?= htmlspecialchars((string) $director['name']) ?></strong><span><?= htmlspecialchars((string) $director['email']) ?></span></div><button class="cp-remove">Remove</button>
                                            </form>
                                        <?php endforeach; ?>
                                        <form action="/staff/clients/link-director" method="POST" data-ajax-form class="cp-inline-form">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="entity_id" value="<?= $entityId ?>"><input type="hidden" name="return_client_id" value="<?= (int) $client['id'] ?>">
                                            <select name="user_id" class="cp-field" required><option value="">Add portal user...</option><?php foreach ($eligibleDirectors as $candidate): ?><option value="<?= (int) $candidate['id'] ?>"><?= htmlspecialchars((string) $candidate['name']) ?> — <?= htmlspecialchars((string) $candidate['email']) ?></option><?php endforeach; ?></select><button class="cp-mini-button">Add</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <section class="cp-panel" aria-labelledby="dates-title">
                <header class="cp-panel__header"><div><h3 id="dates-title">Important dates</h3><p>Compliance deadlines grouped by business entity.</p></div><span class="cp-count"><?= count($deadlineGroups) ?> entities</span></header>
                <div class="cp-panel__body">
                    <?php if (empty($deadlineGroups)): ?><p class="cp-empty">Link an entity before adding important dates.</p><?php endif; ?>
                    <?php foreach ($deadlineGroups as $group): ?>
                        <section class="cp-deadline-group">
                            <div class="cp-deadline-group__head"><strong><?= htmlspecialchars((string) $group['entity_name']) ?></strong><span><?= htmlspecialchars((string) $group['entity_type']) ?></span></div>
                            <?php if (empty($group['deadlines'])): ?><p class="cp-empty" style="margin-top:9px">No dates recorded for this entity.</p><?php endif; ?>
                            <?php if (!empty($group['deadlines'])): ?><div class="cp-deadline-list"><?php foreach ($group['deadlines'] as $deadline): ?><div class="cp-deadline"><span><?= htmlspecialchars((string) $deadline['type']) ?></span><strong><?= date('d M Y', strtotime((string) $deadline['due_date'])) ?></strong></div><?php endforeach; ?></div><?php endif; ?>
                            <form action="/staff/deadlines/create" method="POST" data-ajax-form class="cp-deadline-form">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="client_id" value="<?= (int) $client['id'] ?>"><input type="hidden" name="entity_id" value="<?= (int) $group['entity_id'] ?>"><input type="hidden" name="return_to" value="/staff/clients/<?= (int) $client['id'] ?>">
                                <input class="cp-field" name="type" placeholder="Date type" required><input class="cp-field" type="date" name="due_date" min="<?= date('Y-m-d') ?>" required><button class="cp-mini-button">Add date</button>
                            </form>
                        </section>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="cp-panel" aria-labelledby="documents-title">
                <header class="cp-panel__header"><div><h3 id="documents-title">Documents</h3><p>Files uploaded by the client or shared by TriNova.</p></div><span class="cp-count"><?= count($documents) ?> files</span></header>
                <?php if (empty($documents)): ?><div class="cp-panel__body"><p class="cp-empty">No documents have been uploaded or shared with this client yet.</p></div><?php else: ?>
                    <div class="cp-table-wrap"><table class="cp-table"><caption class="cp-sr-only">Client documents</caption><colgroup><col style="width:38%"><col style="width:21%"><col style="width:25%"><col style="width:16%"></colgroup><thead><tr><th>Filename</th><th>Source</th><th>Date</th><th>Action</th></tr></thead><tbody>
                    <?php foreach ($documents as $doc): ?><tr><td data-label="Filename" class="cp-file-name"><?= htmlspecialchars((string) $doc['filename']) ?></td><td data-label="Source"><span class="cp-doc-type<?= $doc['direction'] === 'client_upload' ? '' : ' is-shared' ?>"><?= $doc['direction'] === 'client_upload' ? 'Client upload' : 'TriNova file' ?></span></td><td data-label="Date"><?= date('d M Y, H:i', strtotime((string) $doc['created_at'])) ?></td><td data-label="Action"><a class="cp-download" href="/documents/download/<?= (int) $doc['id'] ?>">Download</a></td></tr><?php endforeach; ?>
                    </tbody></table></div>
                <?php endif; ?>
            </section>

            <section class="cp-panel" aria-labelledby="meetings-title">
                <header class="cp-panel__header"><div><h3 id="meetings-title">Appointments and meetings</h3><p>Scheduled conversations with the client.</p></div><span class="cp-count"><?= count($meetings) ?> scheduled</span></header>
                <div class="cp-panel__body">
                    <?php if (empty($meetings)): ?><p class="cp-empty">No appointments are scheduled for this client.</p><?php endif; ?>
                    <?php foreach ($meetings as $meeting): ?><div class="cp-meeting"><div><strong><?= htmlspecialchars((string) $meeting['title']) ?></strong><span><?= date('d M Y, H:i', strtotime((string) $meeting['meeting_time'])) ?> &middot; With <?= htmlspecialchars((string) ($meeting['staff_name'] ?? 'Staff')) ?></span></div><span class="cp-meeting__status"><?= htmlspecialchars((string) ($meeting['status'] ?? 'Scheduled')) ?></span></div><?php endforeach; ?>
                </div>
            </section>
        </main>

        <aside class="cp-side">
            <section class="cp-panel cp-attention" aria-labelledby="outstanding-title">
                <header class="cp-panel__header"><div><h3 id="outstanding-title">Outstanding items</h3><p>Requests that still need client action.</p></div><span class="cp-count"><?= count($outstanding) ?></span></header>
                <div class="cp-panel__body">
                    <?php if (empty($outstanding)): ?><p class="cp-empty cp-empty--success">Everything is up to date.</p><?php endif; ?>
                    <?php foreach ($outstanding as $item): ?><div class="cp-outstanding"><span class="cp-outstanding__dot" aria-hidden="true"></span><strong><?= htmlspecialchars((string) $item['title']) ?></strong><time datetime="<?= htmlspecialchars((string) $item['due_date']) ?>">Due <?= date('d M', strtotime((string) $item['due_date'])) ?></time></div><?php endforeach; ?>
                </div>
            </section>

            <section class="cp-panel cp-notes" aria-labelledby="notes-title">
                <div class="cp-panel__body"><span class="cp-staff-label">Staff only</span><h3 id="notes-title" style="margin:11px 0 0;font-size:15px">Internal notes</h3><p class="cp-note-copy"><?= htmlspecialchars((string) (($client['notes'] ?? '') ?: 'No internal notes registered for this client.')) ?></p></div>
            </section>

            <section class="cp-panel" aria-labelledby="activity-title">
                <header class="cp-panel__header"><div><h3 id="activity-title">Recent activity</h3><p>Portal login and account events.</p></div><span class="cp-count"><?= count($auditLogs) ?></span></header>
                <div class="cp-panel__body">
                    <?php if (empty($auditLogs)): ?><p class="cp-empty">No activity has been recorded for this client yet.</p><?php endif; ?>
                    <?php foreach ($auditLogs as $log): ?><div class="cp-log"><div class="cp-log__top"><strong><?= htmlspecialchars((string) $log['action_type']) ?></strong><time datetime="<?= htmlspecialchars((string) $log['created_at']) ?>"><?= date('d M, H:i', strtotime((string) $log['created_at'])) ?></time></div><p><?= htmlspecialchars((string) ($log['target_type'] ?? 'Account')) ?><?= !empty($log['target_id']) ? ' #' . (int) $log['target_id'] : '' ?> &middot; <?= htmlspecialchars((string) $log['ip_address']) ?></p></div><?php endforeach; ?>
                </div>
            </section>
        </aside>
    </div>
</div>

<div id="deleteClientModal" class="cp-modal" role="dialog" aria-modal="true" aria-labelledby="delete-client-title">
    <div class="cp-modal__card cp-modal__card--small">
        <div class="cp-modal__head"><h3 id="delete-client-title">Delete client account</h3><button type="button" class="cp-close" aria-label="Close" onclick="document.getElementById('deleteClientModal').style.display='none'">&times;</button></div>
        <form action="/staff/clients/delete" method="POST" data-ajax-form>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="client_id" value="<?= (int) $client['id'] ?>"><input type="hidden" name="is_ajax" value="1">
            <p class="cp-danger-copy">Are you sure you want to delete <strong><?= htmlspecialchars($clientName) ?></strong>? This permanently removes their profile, files, messages, and login access.</p>
            <div class="cp-form-group" style="margin-top:18px"><label for="confirmClientDelete">Type DELETE to confirm</label><input id="confirmClientDelete" class="cp-input cp-danger-input" type="text" name="confirm_delete" pattern="DELETE" required autocomplete="off"></div>
            <div class="cp-modal__actions"><button type="button" class="cp-button cp-button--soft" onclick="document.getElementById('deleteClientModal').style.display='none'">Cancel</button><button type="submit" class="cp-button cp-button--danger" data-loading-text="Deleting...">Delete permanently</button></div>
        </form>
    </div>
</div>

<div id="addEntityModal" class="cp-modal" role="dialog" aria-modal="true" aria-labelledby="add-entity-title">
    <div class="cp-modal__card">
        <div class="cp-modal__head"><h3 id="add-entity-title">Link business entity</h3><button type="button" class="cp-close" aria-label="Close" onclick="document.getElementById('addEntityModal').style.display='none'">&times;</button></div>
        <form action="/staff/clients/add-entity" method="POST" data-ajax-form>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="client_id" value="<?= (int) $client['id'] ?>">
            <div class="cp-form-group"><label for="entityName">Entity name</label><input id="entityName" class="cp-input" type="text" name="entity_name" placeholder="e.g. Example Services Ltd" required></div>
            <div class="cp-form-grid">
                <div class="cp-form-group"><label for="entityType">Entity type</label><input id="entityType" class="cp-input" name="entity_type" list="profileEntityTypes" placeholder="Select or enter type" required><datalist id="profileEntityTypes"><option value="Limited Company"><option value="Personal Tax Return"><option value="Sole Trader"><option value="Partnership"><option value="Other"></datalist></div>
                <div class="cp-form-group"><label for="entityScope">Record access</label><select id="entityScope" class="cp-input" name="entity_scope"><option value="company">Shared company record</option><option value="personal">Private personal record</option></select></div>
            </div>
            <div data-entity-fields class="cp-form-grid">
                <input class="cp-input" name="company_number" placeholder="Company number"><input class="cp-input" name="vat_number" placeholder="VAT registration number"><input class="cp-input" name="ct_utr" placeholder="Corporation Tax UTR"><input class="cp-input" name="personal_utr" placeholder="Personal UTR"><input class="cp-input" type="date" name="accounting_year_end" title="Accounting year end"><input class="cp-input" name="tax_year" placeholder="Tax year"><input class="cp-input" name="custom_attribute_label" placeholder="Other reference label"><input class="cp-input" name="custom_attribute_value" placeholder="Other reference value">
            </div>
            <div class="cp-form-dates"><span class="cp-form-label">Important dates</span><?php foreach (["Accounts Due", "Corporation Tax Due", "Next VAT Return Due"] as $suggestion): ?><div data-deadline-row class="cp-form-date-row"><input class="cp-input" name="deadline_type[]" value="<?= htmlspecialchars($suggestion) ?>"><input class="cp-input" type="date" name="deadline_due_date[]" min="<?= date('Y-m-d') ?>"></div><?php endforeach; ?></div>
            <div class="cp-modal__actions"><button type="button" class="cp-button cp-button--soft" onclick="document.getElementById('addEntityModal').style.display='none'">Cancel</button><button type="submit" class="cp-button cp-button--primary">Link entity</button></div>
        </form>
    </div>
</div>

<div id="deleteEntityModal" class="cp-modal" role="dialog" aria-modal="true" aria-labelledby="delete-entity-title">
    <div class="cp-modal__card cp-modal__card--small">
        <div class="cp-modal__head"><h3 id="delete-entity-title">Move business entity to Trash?</h3><button type="button" class="cp-close" aria-label="Close" onclick="document.getElementById('deleteEntityModal').style.display='none'">&times;</button></div>
        <p class="cp-danger-copy">The entity and its linked records will be soft-deleted and can be restored from Trash.</p>
        <form action="/staff/clients/delete-entity" method="POST" data-ajax-form>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="entity_id" id="deleteEntityId"><input type="hidden" name="client_id" value="<?= (int) $client['id'] ?>">
            <div class="cp-modal__actions"><button type="button" class="cp-button cp-button--soft" onclick="document.getElementById('deleteEntityModal').style.display='none'">Cancel</button><button type="submit" class="cp-button cp-button--danger">Move to Trash</button></div>
        </form>
    </div>
</div>

<script>
function tnDeleteEntity(id) {
    document.getElementById('deleteEntityId').value = id;
    document.getElementById('deleteEntityModal').style.display = 'flex';
}
</script>
