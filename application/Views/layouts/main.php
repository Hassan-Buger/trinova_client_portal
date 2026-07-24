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
        @media(max-width:1080px){ .tn-navlabel, .tn-brandword, .tn-logout-label { display: none !important; } .tn-side { width: 80px !important; align-items: center; } .tn-navitem { justify-content: center; } }
        @media(max-width:760px){ .tn-side { width: 66px !important; padding: 16px 8px !important; } .tn-topbar { padding: 14px 16px !important; } .tn-pagetitle { font-size: 19px !important; } }
    </style>
</head>
<body>

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
            
            <main style="padding:8px 32px 48px;flex:1">
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

</body>
</html>
