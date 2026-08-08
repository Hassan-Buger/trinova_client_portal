<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle ?? 'TriNova Client Portal') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif; color: #213330; background: #eef4f1; -webkit-font-smoothing: antialiased; }
        a { color: #0d9488; text-decoration: none; }
        a:hover { color: #0f766e; }
        input, button, textarea { font-family: inherit; }
        input:focus, textarea:focus { outline: none; border-color: #0d9488 !important; box-shadow: 0 0 0 4px rgba(13,148,136,.12); }
        input::placeholder, textarea::placeholder { color: #9aaba5; }
        ::-webkit-scrollbar { width: 10px; height: 10px; }
        ::-webkit-scrollbar-thumb { background: rgba(20,60,50,.14); border-radius: 20px; border: 3px solid transparent; background-clip: padding-box; }
        @keyframes tnfade { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
        @keyframes tnpop { from { opacity: 0; transform: scale(.96); } to { opacity: 1; transform: none; } }
        .tn-screen { animation: tnfade .34s cubic-bezier(.22,.61,.36,1); }
        .tn-page-progress { position:fixed;top:0;left:0;height:3px;width:0;background:#0d9488;z-index:9999;opacity:0;transition:width .2s ease,opacity .2s ease; }
        .tn-page-progress.is-loading { width:72%;opacity:1; }
        .tn-page-progress.is-complete { width:100%;opacity:0; }
        .tn-toast-stack { position:fixed;right:24px;bottom:24px;z-index:9998;display:flex;flex-direction:column;gap:10px;pointer-events:none; }
        .tn-toast { min-width:280px;max-width:420px;padding:14px 18px;border-radius:15px;background:#e2f3ea;color:#287a52;font-size:14px;font-weight:700;box-shadow:0 16px 40px -20px rgba(16,54,45,.55);animation:tnpop .22s ease; }
        .tn-toast.is-error { background:#fdecdc;color:#b45e18; }
        .tn-message-day { align-self:center;color:#7d8e88;background:#eef4f1;border-radius:999px;padding:5px 11px;font-size:11px;font-weight:800;letter-spacing:.02em; }
        .tn-message-bubble { max-width:78%;border-radius:18px;padding:15px 18px;box-shadow:0 2px 8px rgba(16,54,45,.04); }
        .tn-message-bubble.is-mine { align-self:flex-end;border-bottom-right-radius:4px;background:#0d9488;color:#fff; }
        [data-message-thread][data-current-role="staff"] .tn-message-bubble.is-mine { background:#41556f; }
        .tn-message-bubble.is-theirs { align-self:flex-start;border-bottom-left-radius:4px;background:#fff;color:#213330;border:1px solid rgba(20,60,50,.08); }
        .tn-message-meta { font-size:12px;font-weight:700;margin-bottom:5px;opacity:.78; }
        .tn-message-body { font-size:14px;line-height:1.5;white-space:pre-wrap;overflow-wrap:anywhere; }
        .tn-main[aria-busy="true"] #portal-content { opacity:.62;transition:opacity .15s ease; }
        .tn-welcome-text { margin-top:4px;color:#7d8e88;font-size:12.5px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
        .tn-notification-wrap { position:relative; }
        .tn-notification-button { width:44px;height:44px;border-radius:14px;border:1px solid rgba(20,60,50,.08);background:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#5f726c;position:relative; }
        .tn-notification-badge { position:absolute;top:5px;right:5px;min-width:18px;height:18px;padding:0 5px;background:#ef8f3c;color:#fff;border-radius:999px;border:2px solid #fff;font-size:10px;font-weight:800;line-height:14px;text-align:center; }
        .tn-notification-panel { position:absolute;right:0;top:52px;width:min(360px,calc(100vw - 32px));background:#fff;border:1px solid rgba(20,60,50,.09);border-radius:17px;box-shadow:0 22px 55px -24px rgba(16,54,45,.55);overflow:hidden;z-index:30; }
        .tn-notification-heading { padding:15px 17px;border-bottom:1px solid #edf2f0;font-size:13px;font-weight:800;color:#41556f; }
        .tn-notification-list { max-height:360px;overflow:auto; }
        .tn-notification-item { display:block;padding:13px 17px;border-bottom:1px solid #f0f4f2;color:#213330; }
        .tn-notification-item.is-unread { background:#eef8f5;border-left:3px solid #0d9488;padding-left:14px; }
        .tn-notification-item:hover { background:#f7faf9;color:#0f766e; }
        .tn-notification-title { display:block;font-size:12px;font-weight:800;color:#0f766e;margin-bottom:3px; }
        .tn-notification-message { display:block;font-size:13px;font-weight:700;line-height:1.4; }
        .tn-notification-time { display:block;margin-top:4px;color:#8a9a94;font-size:11px;font-weight:600; }
        .tn-notification-empty { padding:24px 17px;text-align:center;color:#8a9a94;font-size:13px; }
        .tn-side nav { min-height:0;overflow-y:auto;overflow-x:hidden;scrollbar-width:thin; }
        [data-entity-fields] input,
        [data-deadline-row] input,
        form[action="/staff/deadlines/create"] input:not([type="hidden"]) { width:100%;min-width:0;padding:11px 12px;border:1px solid #dfe9e5;border-radius:11px;background:#fbfdfc;color:#213330;font-size:13px; }
        [data-entity-fields] input:focus,
        [data-deadline-row] input:focus { background:#fff; }
        .tn-brand-logo { display:block;width:178px;max-width:100%;height:52px;object-fit:contain;object-position:left center; }
        .tn-auth-logo { display:block;width:230px;max-width:78vw;height:auto;margin:0 auto 26px; }
        /* Premium authentication surface */
        :root {
            --auth-bg:#f4f7f5;--auth-surface:rgba(255,255,255,.96);--auth-text:#172d29;
            --auth-muted:#63756f;--auth-subtle:#84938e;--auth-border:#dce6e2;
            --auth-brand:#0d9488;--auth-brand-hover:#0b7f75;--auth-danger:#b94a48;
            --auth-radius-card:28px;--auth-radius-input:15px;
            --auth-focus:0 0 0 4px rgba(13,148,136,.13);
            --auth-shadow:0 30px 70px -42px rgba(18,55,48,.42),0 10px 28px -22px rgba(18,55,48,.3),inset 0 1px 0 rgba(255,255,255,.9);
        }
        .auth-shell { position:relative;isolation:isolate;min-height:100vh;min-height:100dvh;display:grid;place-items:center;padding:clamp(28px,5vh,64px) 20px;background:var(--auth-bg);overflow:hidden; }
        .auth-atmosphere { position:absolute;inset:0;z-index:-1;pointer-events:none;background:radial-gradient(52rem 34rem at -8% -8%,rgba(68,181,166,.13),transparent 68%),radial-gradient(42rem 30rem at 108% 104%,rgba(232,145,100,.09),transparent 70%),radial-gradient(28rem 22rem at 92% 5%,rgba(155,149,192,.045),transparent 72%); }
        .auth-atmosphere::after { content:"";position:absolute;inset:0;opacity:.2;background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 180 180' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.9' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.055'/%3E%3C/svg%3E"); }
        .auth-container { width:min(100%,540px);animation:auth-enter .42s cubic-bezier(.22,.7,.25,1) both; }
        .auth-brand { display:flex;justify-content:center;margin-bottom:clamp(24px,3.5vh,32px); }
        .auth-brand__logo { display:block;width:clamp(205px,20vw,246px);max-width:72vw;height:auto;filter:drop-shadow(0 7px 15px rgba(20,70,61,.09)); }
        .auth-card { padding:clamp(30px,4vw,44px);background:var(--auth-surface);border:1px solid rgba(119,145,137,.2);border-radius:var(--auth-radius-card);box-shadow:var(--auth-shadow); }
        .auth-card__header { margin-bottom:28px; }
        .auth-card__header h1 { margin:0 0 10px;color:var(--auth-text);font-size:clamp(28px,3vw,32px);line-height:1.18;font-weight:750;letter-spacing:-.035em; }
        .auth-card__header p { max-width:440px;margin:0;color:var(--auth-muted);font-size:15.5px;line-height:1.62; }
        .auth-alert { display:flex;align-items:flex-start;gap:11px;margin:0 0 22px;padding:13px 15px;border:1px solid;border-radius:13px;font-size:13.5px;font-weight:600;line-height:1.45;animation:auth-alert-in .22s ease both; }
        .auth-alert svg { flex:0 0 18px;width:18px;height:18px;margin-top:1px; }
        .auth-alert--error { color:#9f3f3d;background:#fff6f5;border-color:#f1d2cf; }
        .auth-alert--success { color:#257154;background:#f0faf5;border-color:#cee9dc; }
        .auth-form { display:grid;gap:20px; }
        .auth-field label { display:block;color:#293e39;font-size:14px;font-weight:700;line-height:1.3; }
        .auth-label-row { display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:9px; }
        .auth-field:not(:has(.auth-label-row)) > label { margin-bottom:9px; }
        .auth-forgot { position:relative;color:var(--auth-brand);font-size:13.5px;font-weight:700;text-decoration:none; }
        .auth-forgot::after { content:"";position:absolute;right:0;bottom:-3px;left:0;height:1px;background:currentColor;transform:scaleX(0);transform-origin:right;transition:transform .18s ease; }
        .auth-forgot:hover { color:var(--auth-brand-hover); }
        .auth-forgot:hover::after { transform:scaleX(1);transform-origin:left; }
        .auth-input-wrap { position:relative; }
        .auth-input { display:block;width:100%;height:57px;padding:0 48px;color:var(--auth-text);background:#f8faf9;border:1px solid var(--auth-border);border-radius:var(--auth-radius-input);font-size:15px;font-weight:500;line-height:1;caret-color:var(--auth-brand);transition:border-color .18s ease,background-color .18s ease,box-shadow .18s ease,transform .18s ease; }
        .auth-input:not(.auth-input--password) { padding-right:16px; }
        .auth-input:hover:not(:disabled) { border-color:#c5d5d0;background:#fbfdfc; }
        .auth-input:focus { outline:0;border-color:var(--auth-brand) !important;background:#fff;box-shadow:var(--auth-focus); }
        .auth-input[aria-invalid="true"] { border-color:#d98a86;background:#fffafa; }
        .auth-input:disabled { color:#94a19d;background:#eef2f0;cursor:not-allowed; }
        .auth-input::placeholder { color:#98a6a1;opacity:1; }
        .auth-input-icon { position:absolute;top:50%;left:16px;width:19px;height:19px;color:#788b84;transform:translateY(-50%);pointer-events:none;transition:color .18s ease; }
        .auth-input-wrap:focus-within .auth-input-icon { color:var(--auth-brand); }
        .auth-password-toggle { position:absolute;top:50%;right:8px;width:40px;height:40px;display:grid;place-items:center;padding:0;color:#71847e;background:transparent;border:0;border-radius:10px;cursor:pointer;transform:translateY(-50%);transition:color .18s ease,background-color .18s ease; }
        .auth-password-toggle:hover { color:var(--auth-brand-hover);background:#eaf4f1; }
        .auth-password-toggle:focus-visible,.auth-forgot:focus-visible,.auth-support a:focus-visible { outline:2px solid var(--auth-brand);outline-offset:3px; }
        .auth-eye { width:19px;height:19px; }
        .auth-eye--hide { display:none; }
        .auth-password-toggle[aria-pressed="true"] .auth-eye--show { display:none; }
        .auth-password-toggle[aria-pressed="true"] .auth-eye--hide { display:block; }
        .auth-submit { position:relative;width:100%;height:58px;display:flex;align-items:center;justify-content:center;gap:9px;margin-top:4px;padding:0 22px;color:#fff;background:linear-gradient(180deg,#109b8f 0%,#0d8e83 100%);border:1px solid rgba(5,102,94,.25);border-radius:15px;box-shadow:0 13px 24px -14px rgba(13,148,136,.85),inset 0 1px 0 rgba(255,255,255,.18);font-size:15.5px;font-weight:750;letter-spacing:-.01em;cursor:pointer;transition:background .18s ease,box-shadow .18s ease,transform .18s ease; }
        .auth-submit:hover:not(:disabled) { background:linear-gradient(180deg,#0e8f84,#0b7f75);box-shadow:0 16px 28px -14px rgba(13,126,116,.8),inset 0 1px 0 rgba(255,255,255,.16);transform:translateY(-1px); }
        .auth-submit:active:not(:disabled) { box-shadow:0 7px 16px -12px rgba(13,126,116,.75);transform:translateY(0); }
        .auth-submit:focus-visible { outline:0;box-shadow:0 0 0 4px rgba(13,148,136,.2),0 13px 24px -14px rgba(13,148,136,.85); }
        .auth-submit:disabled { opacity:.78;cursor:wait; }
        .auth-submit__arrow { width:18px;height:18px;transition:transform .18s ease; }
        .auth-submit:hover .auth-submit__arrow { transform:translateX(2px); }
        .auth-submit__spinner { display:none;width:18px;height:18px;border:2px solid rgba(255,255,255,.42);border-top-color:#fff;border-radius:50%;animation:auth-spin .7s linear infinite; }
        .auth-submit.is-loading .auth-submit__arrow { display:none; }
        .auth-submit.is-loading .auth-submit__spinner { display:block; }
        .auth-trust { display:flex;align-items:center;justify-content:center;gap:8px;margin-top:23px;color:#687c75;font-size:12.5px;font-weight:650;text-align:center; }
        .auth-trust svg { flex:0 0 17px;width:17px;height:17px;color:#56887a; }
        .auth-support { margin:24px auto 0;color:#778983;font-size:12.5px;font-weight:550;line-height:1.65;text-align:center; }
        .auth-support a { color:#365e55;font-weight:750;white-space:nowrap; }
        .auth-support a:hover { color:var(--auth-brand-hover); }
        .auth-input:-webkit-autofill,.auth-input:-webkit-autofill:hover,.auth-input:-webkit-autofill:focus { -webkit-text-fill-color:var(--auth-text);-webkit-box-shadow:0 0 0 1000px #f8faf9 inset;transition:background-color 9999s ease-out;caret-color:var(--auth-text); }
        @keyframes auth-enter { from { opacity:0;transform:translateY(7px); } to { opacity:1;transform:none; } }
        @keyframes auth-alert-in { from { opacity:0;transform:translateY(-3px); } to { opacity:1;transform:none; } }
        @keyframes auth-spin { to { transform:rotate(360deg); } }
        @media(max-width:600px) {
            .auth-shell { align-items:center;padding:24px 16px; }
            .auth-brand { margin-bottom:22px; }
            .auth-brand__logo { width:205px; }
            .auth-card { padding:28px 24px;border-radius:22px; }
            .auth-card__header { margin-bottom:24px; }
            .auth-card__header p { font-size:14.5px; }
            .auth-input,.auth-submit { height:56px; }
            .auth-support { max-width:310px;margin-top:20px; }
        }
        @media(max-width:380px) {
            .auth-shell { padding-right:14px;padding-left:14px; }
            .auth-card { padding:25px 20px; }
            .auth-label-row { gap:12px; }
            .auth-forgot { font-size:13px; }
        }
        @media(prefers-reduced-motion:reduce) {
            .auth-container,.auth-alert { animation:none; }
            .auth-input,.auth-forgot::after,.auth-password-toggle,.auth-submit,.auth-submit__arrow { transition:none; }
        }
        .tn-side > a:last-child { flex-shrink:0;background:#f6f9f8; }
        .tn-side > a:last-child:hover { background:#fdecdc;color:#b45e18 !important; }
        @media(max-width:1080px){ .tn-navlabel, .tn-brandword, .tn-logout-label { display: none !important; } .tn-side { width: 80px !important; align-items: center; } .tn-navitem { justify-content: center; } .tn-sidebar-brand { width:56px;padding-left:0 !important;padding-right:0 !important;justify-content:flex-start;overflow:hidden; } .tn-brand-logo { width:150px;max-width:none;height:44px;object-position:left center; } }
        @media(max-width:760px){
            body { overflow-x:hidden; }
            .tn-side { position:fixed !important;left:0;right:0;bottom:0;top:auto !important;width:100% !important;height:76px !important;padding:7px 8px env(safe-area-inset-bottom) !important;border-right:0 !important;border-top:1px solid rgba(20,60,50,.1);z-index:20;align-items:stretch !important; }
            .tn-side > div:first-child, .tn-side > a:last-child { display:none !important; }
            .tn-side nav { display:flex !important;flex-direction:row !important;gap:3px !important;overflow-x:auto;overflow-y:hidden;scrollbar-width:none; }
            .tn-side nav::-webkit-scrollbar { display:none; }
            .tn-side .tn-navitem { min-width:68px;padding:7px 6px !important;gap:3px !important;flex:0 0 auto;flex-direction:column;justify-content:center;font-size:10px !important;text-align:center; }
            .tn-side .tn-navitem svg { width:19px;height:19px; }
            .tn-side .tn-navlabel { display:block !important;max-width:72px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
            .tn-main { width:100%;padding-bottom:78px; }
            .tn-topbar { padding:12px 14px !important;gap:10px !important; }
            .tn-pagetitle { font-size:18px !important; }
            .tn-welcome-text { max-width:190px;font-size:11px; }
            #portal-content { padding:8px 14px 30px !important; }
            .tn-toast-stack { right:12px;left:12px;bottom:90px; }
            .tn-toast { min-width:0;max-width:none;width:100%; }
            .tn-notification-panel { position:fixed;right:12px;top:70px;width:calc(100vw - 24px); }
            .tn-client-toolbar { align-items:stretch !important;flex-direction:column; }
            .tn-client-toolbar-actions { width:100%;flex-wrap:wrap; }
            .tn-client-toolbar-actions form { width:100%; }
            .tn-client-toolbar-actions input[type="search"] { min-width:0 !important;flex:1;width:100%; }
            .tn-client-toolbar-actions select { flex:0 0 108px; }
            .tn-create-client-button { width:100%; }
            [data-entity-fields], [data-deadline-row], form[action="/staff/deadlines/create"] { grid-template-columns:1fr !important; }
            .tn-client-list { padding:0 !important;background:transparent !important;box-shadow:none !important; }
            #clientsTable, #clientsTable tbody { display:block;width:100%; }
            #clientsTable thead { display:none; }
            #clientsTable .client-row { display:block;background:#fff;border:0 !important;border-radius:20px;margin-bottom:12px;padding:11px 8px;box-shadow:0 10px 28px -24px rgba(16,54,45,.65); }
            #clientsTable .client-row td { display:grid;grid-template-columns:88px minmax(0,1fr);align-items:center;gap:10px;padding:8px 10px !important;text-align:left !important;overflow-wrap:anywhere; }
            #clientsTable .client-row td::before { content:attr(data-label);color:#8a9a94;font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:.05em; }
            #clientsTable .client-row td:last-child > div { display:block !important; }
            #clientsTable .client-row td:last-child a { display:block;text-align:center;padding:10px 13px !important; }
            #clientsTable .tn-client-empty { display:block;background:#fff;border-radius:20px; }
            #clientsTable .tn-client-empty td { display:block !important;width:100%; }
        }
    </style>
</head>
<body data-portal-authenticated="<?= \Application\Core\Session::get('user_id') ? '1' : '0' ?>" data-csrf-token="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">

<div id="tnPageProgress" class="tn-page-progress" aria-hidden="true"></div>
<div id="tnToastStack" class="tn-toast-stack" aria-live="polite" aria-atomic="true"></div>

<?php
$userId = \Application\Core\Session::get('user_id');
$role = \Application\Core\Session::get('role');
?>

<?php if (!$userId): ?>
    <?= $content ?>
<?php else: ?>
    <div style="display:flex;min-height:100vh;background:#eef4f1">
        <?php if ($role === 'staff'): ?>
            <?php require __DIR__ . '/sidebar-staff.php'; ?>
        <?php else: ?>
            <?php require __DIR__ . '/sidebar-client.php'; ?>
        <?php endif; ?>

        <div class="tn-main" style="flex:1;min-width:0;display:flex;flex-direction:column">
            <?php require __DIR__ . '/header.php'; ?>
            
            <main id="portal-content" tabindex="-1" style="padding:8px 32px 48px;flex:1">
                <?php if ($flashSuccess = \Application\Core\Session::getFlash('success')): ?>
                    <div style="max-width:1120px;margin-bottom:16px;padding:14px 18px;background:#e2f3ea;color:#3f9d6d;border-radius:16px;font-weight:600;font-size:14px">
                        <?= htmlspecialchars($flashSuccess) ?>
                    </div>
                <?php endif; ?>
                <?php if ($flashError = \Application\Core\Session::getFlash('error')): ?>
                    <div style="max-width:1120px;margin-bottom:16px;padding:14px 18px;background:#fdecdc;color:#e07d24;border-radius:16px;font-weight:600;font-size:14px">
                        <?= htmlspecialchars($flashError) ?>
                    </div>
                <?php endif; ?>

                <?= $content ?>
            </main>
        </div>
    </div>
<?php endif; ?>

<script src="/assets/js/app.js?v=<?= (int)@filemtime(dirname(__DIR__, 3) . '/public/assets/js/app.js') ?>" defer></script>
</body>
</html>
