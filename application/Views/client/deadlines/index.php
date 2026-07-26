<div class="tn-screen" style="max-width:1120px">
    <div style="margin-bottom:24px">
        <p style="margin:0;color:#61756e;font-size:14.5px">Statutory deadlines for VAT, Payroll, Accounts, Corp Tax, Self Assessment, and Confirmation Statements.</p>
    </div>

    <div style="background:#fff;border-radius:24px;padding:26px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -24px rgba(16,54,45,.4)">
        <?php if (empty($deadlines)): ?>
            <div style="padding:48px 24px;text-align:center;color:#8a9a94">No upcoming compliance deadlines logged.</div>
        <?php else: ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px">
                <?php foreach ($deadlines as $d): ?>
                    <?php
                    $isOverdue = (strtotime($d['due_date']) < time() && $d['status'] !== 'Completed');
                    $daysRemaining = (int)ceil((strtotime($d['due_date']) - time()) / 86400);
                    $badgeStyle = match($d['status']) {
                        'Completed' => 'background:#e2f3ea;color:#3f9d6d;',
                        'Overdue'   => 'background:#fdecdc;color:#e07d24;',
                        default     => $isOverdue ? 'background:#fdecdc;color:#e07d24;' : 'background:#dff1ee;color:#0d9488;'
                    };
                    ?>
                    <div style="border:1px solid rgba(20,60,50,.08);border-radius:20px;padding:20px;background:#fbfdfc;display:flex;flex-direction:column;justify-content:space-between">
                        <div>
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
                                <span style="font-weight:800;font-size:16px;color:#213330"><?= htmlspecialchars($d['type']) ?></span>
                                <span style="padding:4px 10px;border-radius:999px;font-size:11.5px;font-weight:700;<?= $badgeStyle ?>">
                                    <?= htmlspecialchars($d['status'] === 'Pending' && $isOverdue ? 'Overdue' : $d['status']) ?>
                                </span>
                            </div>
                            <div style="font-size:26px;font-weight:800;letter-spacing:-.02em;margin-bottom:4px;color:#213330">
                                <?= date('d M Y', strtotime($d['due_date'])) ?>
                            </div>
                            <div style="font-size:13px;color:#7d8e88">
                                <?php if ($d['status'] === 'Completed'): ?>
                                    Completed & Filed
                                <?php elseif ($isOverdue): ?>
                                    <strong style="color:#e07d24"><?= abs($daysRemaining) ?> days overdue</strong>
                                <?php else: ?>
                                    <?= $daysRemaining ?> days remaining
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
