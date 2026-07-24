<div class="tn-screen" style="max-width:800px">
    <div style="background:#fff;border-radius:24px;padding:36px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -24px rgba(16,54,45,.4)">
        <div style="display:inline-flex;align-items:center;gap:8px;background:#dff1ee;color:#0f766e;font-size:12px;font-weight:700;padding:5px 14px;border-radius:999px;margin-bottom:16px;text-transform:uppercase;letter-spacing:.06em">
            Foundation Shell Active
        </div>
        <h2 style="margin:0 0 10px;font-size:24px;font-weight:800;letter-spacing:-.02em"><?= htmlspecialchars($featureName ?? 'Feature') ?></h2>
        <p style="margin:0 0 24px;color:#61756e;font-size:15px;line-height:1.5">
            <?= htmlspecialchars($description ?? 'This feature module is scheduled for full implementation in the next phase.') ?>
        </p>
        <div style="padding:16px 20px;background:#f8faf9;border:1px dashed #c8dad3;border-radius:16px;color:#5f726c;font-size:13.5px;font-weight:500">
            <strong>Foundation Status:</strong> Route, RBAC session scoping, navigation shell, and database models are fully wired and ready for feature implementation.
        </div>
    </div>
</div>
