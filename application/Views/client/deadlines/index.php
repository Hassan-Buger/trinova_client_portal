<div class="tn-screen" style="max-width:1120px">
    <p style="margin:0 0 24px;color:#61756e;font-size:14.5px">Your important dates are grouped by the business or tax entity they belong to.</p>
    <?php if (empty($deadlineGroups)): ?>
        <div style="background:#fff;border-radius:24px;padding:48px;text-align:center;color:#8a9a94">No upcoming important dates recorded.</div>
    <?php endif; ?>
    <div style="display:flex;flex-direction:column;gap:18px">
        <?php foreach ($deadlineGroups as $group): ?>
            <section style="background:#fff;border-radius:24px;padding:24px;box-shadow:0 14px 34px -24px rgba(16,54,45,.4)">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:16px"><h2 style="margin:0;font-size:18px"><?= htmlspecialchars($group['entity_name']) ?></h2><span style="font-size:11.5px;font-weight:700;color:#41556f;background:#eef4f1;padding:5px 10px;border-radius:999px"><?= htmlspecialchars($group['entity_type']) ?></span></div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(235px,1fr));gap:13px">
                    <?php if(empty($group['deadlines'])): ?><div style="color:#8a9a94;font-size:13px">No important dates recorded for this entity.</div><?php endif; ?>
                    <?php foreach ($group['deadlines'] as $d): $overdue=strtotime($d['due_date'])<strtotime('today')&&$d['status']!=='Completed'; ?>
                        <article style="border:1px solid #e8efec;border-radius:18px;padding:18px;background:#fbfdfc"><div style="display:flex;justify-content:space-between;gap:8px"><strong><?= htmlspecialchars($d['type']) ?></strong><span style="font-size:11px;font-weight:700;color:<?= $overdue?'#e07d24':'#0d9488' ?>"><?= htmlspecialchars($overdue?'Overdue':$d['status']) ?></span></div><div style="font-size:23px;font-weight:800;margin-top:12px"><?= date('d M Y',strtotime($d['due_date'])) ?></div></article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
</div>
