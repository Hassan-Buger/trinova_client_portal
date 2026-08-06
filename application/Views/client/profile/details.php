<div class="tn-screen" style="max-width:960px">
    <div style="margin-bottom:24px">
        <p style="margin:0;color:#61756e;font-size:14.5px">Contact details, registered address, and linked business entities.</p>
    </div>

    <!-- Contact & Address Card -->
    <div style="background:#fff;border-radius:24px;padding:28px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -24px rgba(16,54,45,.4);margin-bottom:24px">
        <h3 style="margin:0 0 20px;font-size:18px;font-weight:800;color:#213330">Primary Contact Information</h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px;margin-bottom:20px">
            <div>
                <div style="font-size:12.5px;font-weight:700;color:#7d8e88;text-transform:uppercase;letter-spacing:.05em">Full Name</div>
                <div style="font-size:16px;font-weight:700;color:#213330;margin-top:4px"><?= htmlspecialchars($client['name'] ?? '') ?></div>
            </div>
            <div>
                <div style="font-size:12.5px;font-weight:700;color:#7d8e88;text-transform:uppercase;letter-spacing:.05em">Email Address</div>
                <div style="font-size:16px;font-weight:700;color:#213330;margin-top:4px"><?= htmlspecialchars($client['email'] ?? '') ?></div>
            </div>
            <div>
                <div style="font-size:12.5px;font-weight:700;color:#7d8e88;text-transform:uppercase;letter-spacing:.05em">Phone Number</div>
                <div style="font-size:16px;font-weight:700;color:#213330;margin-top:4px"><?= htmlspecialchars($client['phone'] ?? 'Not provided') ?></div>
            </div>
        </div>
        <div>
            <div style="font-size:12.5px;font-weight:700;color:#7d8e88;text-transform:uppercase;letter-spacing:.05em">Postal Address</div>
            <div style="font-size:15px;font-weight:600;color:#3a4d47;margin-top:4px"><?= htmlspecialchars($client['address'] ?? 'No address registered') ?></div>
        </div>
    </div>

    <!-- Linked Entities -->
    <div style="background:#fff;border-radius:24px;padding:28px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -24px rgba(16,54,45,.4);margin-bottom:24px">
        <h3 style="margin:0 0 16px;font-size:18px;font-weight:800;color:#213330">Linked Businesses & Entities</h3>
        <?php if (empty($entities)): ?>
            <div style="color:#8a9a94;font-size:14px">No linked businesses registered.</div>
        <?php else: ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px">
                <?php foreach ($entities as $e): ?>
                    <div style="border:1px solid rgba(20,60,50,.08);border-radius:18px;padding:18px;background:#fbfdfc">
                        <div style="display:flex;justify-content:space-between;gap:10px;margin-bottom:10px"><div style="font-weight:800;font-size:16px;color:#213330"><?= htmlspecialchars($e['company_name']) ?></div><span style="font-size:11px;font-weight:700;background:#eef4f1;padding:4px 9px;border-radius:999px"><?= htmlspecialchars($e['entity_type']) ?></span></div>
                        <?php if($e['company_number']): ?><div style="font-size:13px;color:#61756e">Company number: <strong><?= htmlspecialchars($e['company_number']) ?></strong></div><?php endif; ?>
                        <?php if($e['tax_reference']): ?><div style="font-size:13px;color:#61756e;margin-top:2px">Tax reference: <strong><?= htmlspecialchars($e['tax_reference']) ?></strong></div><?php endif; ?>
                        <?php foreach($e['attributes'] as $attribute): ?><div style="font-size:13px;color:#61756e;margin-top:2px"><?= htmlspecialchars($attribute['label'] ?? '') ?>: <strong><?= htmlspecialchars($attribute['value'] ?? '') ?></strong></div><?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Request Change Card -->
    <div style="background:#fff;border-radius:24px;padding:28px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -24px rgba(16,54,45,.4)">
        <h3 style="margin:0 0 8px;font-size:18px;font-weight:800;color:#213330">Request Detail Update</h3>
        <p style="margin:0 0 16px;color:#61756e;font-size:14px">Need to update your address, phone number, or add a new business? Let our team know below.</p>

        <form action="/client/profile/request-update" method="POST" data-ajax-form>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
            <textarea name="update_notes" rows="3" placeholder="e.g. Please update my registered phone number to 07700 900555" required style="width:100%;padding:14px 16px;border:1.5px solid #e0e9e5;border-radius:15px;font-size:14px;background:#fbfdfc;margin-bottom:16px;resize:vertical"></textarea>
            <button type="submit" style="background:#0d9488;color:#fff;border:none;padding:12px 24px;border-radius:14px;font-weight:700;font-size:14px;cursor:pointer">Send Update Request</button>
        </form>
    </div>
</div>
