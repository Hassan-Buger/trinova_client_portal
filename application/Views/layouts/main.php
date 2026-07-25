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
        .tn-notification-item:hover { background:#f7faf9;color:#0f766e; }
        .tn-notification-message { display:block;font-size:13px;font-weight:700;line-height:1.4; }
        .tn-notification-time { display:block;margin-top:4px;color:#8a9a94;font-size:11px;font-weight:600; }
        .tn-notification-empty { padding:24px 17px;text-align:center;color:#8a9a94;font-size:13px; }
        @media(max-width:1080px){ .tn-navlabel, .tn-brandword, .tn-logout-label { display: none !important; } .tn-side { width: 80px !important; align-items: center; } .tn-navitem { justify-content: center; } }
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
