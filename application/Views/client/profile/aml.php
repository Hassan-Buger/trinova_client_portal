<div class="tn-screen" style="max-width:860px">
    <div style="margin-bottom:24px">
        <h2 style="margin:0 0 6px;font-size:26px;font-weight:800;letter-spacing:-.02em">AML Compliance & Verification Status</h2>
        <p style="margin:0;color:#61756e;font-size:14.5px">Anti-Money Laundering (AML) identity verification status required under UK regulations.</p>
    </div>

    <?php
    $status = $client['aml_status'] ?? 'Action Required';
    $isComplete = ($status === 'Complete');
    ?>

    <div style="background:#fff;border-radius:24px;padding:32px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -24px rgba(16,54,45,.4);margin-bottom:24px">
        <div style="display:flex;align-items:center;gap:18px;margin-bottom:24px">
            <div style="width:56px;height:56px;border-radius:18px;<?= $isComplete ? 'background:#e2f3ea;color:#3f9d6d;' : 'background:#fdecdc;color:#e07d24;' ?>display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    <polyline points="9 12 11 14 15 10"></polyline>
                </svg>
            </div>
            <div>
                <div style="font-size:13px;font-weight:700;color:#7d8e88;text-transform:uppercase;letter-spacing:.05em">Current Identity Status</div>
                <div style="font-size:24px;font-weight:800;color:#213330;margin-top:2px">
                    <?= htmlspecialchars($status) ?>
                </div>
            </div>
        </div>

        <p style="margin:0 0 20px;font-size:14.5px;color:#61756e;line-height:1.5">
            <?php if ($isComplete): ?>
                Your identity and anti-money laundering verification is fully verified and up to date. No further action is required from you.
            <?php else: ?>
                Your account requires updated photo ID (Passport / UK Driving Licence) or proof of address to maintain full compliance.
            <?php endif; ?>
        </p>

        <div style="border-top:1px solid #eef4f1;padding-top:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px">
            <div style="font-size:13px;color:#7d8e88">
                External Identity Provider Link: <strong style="color:#0d9488">Credas / AML Verify UK</strong>
            </div>
            <a href="https://credas.co.uk" target="_blank" rel="noopener" style="background:#0d9488;color:#fff;padding:12px 22px;border-radius:14px;font-weight:700;font-size:14px;box-shadow:0 8px 18px -8px rgba(13,148,136,.7)">
                Complete Verification Online &rarr;
            </a>
        </div>
    </div>
</div>
