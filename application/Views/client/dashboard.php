<div class="tn-screen" style="max-width:1120px">
    <p style="margin:0 0 4px;color:#61756e;font-size:15px;font-weight:500"><?= date('l, j F Y') ?></p>
    <h2 style="margin:0 0 22px;font-size:30px;font-weight:800;letter-spacing:-.025em">Good morning, <?= htmlspecialchars($userName) ?></h2>
    
    <!-- Dashboard Stats Grid -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:16px;margin-bottom:24px">
        <div style="background:#fff;border-radius:22px;padding:20px 22px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -22px rgba(16,54,45,.4)">
            <div style="width:42px;height:42px;border-radius:13px;display:flex;align-items:center;justify-content:center;background:#fdecdc;color:#e07d24">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 15V3 m7 8 5-5 5 5 M20 16.5V19a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2.5"/>
                </svg>
            </div>
            <div style="font-size:30px;font-weight:800;letter-spacing:-.02em;margin:14px 0 2px"><?= count($requests) ?></div>
            <div style="color:#61756e;font-size:13.5px;font-weight:600">Outstanding requests</div>
        </div>

        <div style="background:#fff;border-radius:22px;padding:20px 22px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -22px rgba(16,54,45,.4)">
            <div style="width:42px;height:42px;border-radius:13px;display:flex;align-items:center;justify-content:center;background:#dff1ee;color:#0d9488">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M8 2v4 M16 2v4 M4 9h16 M5 4h14a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/>
                </svg>
            </div>
            <div style="font-size:30px;font-weight:800;letter-spacing:-.02em;margin:14px 0 2px"><?= count($deadlines) ?></div>
            <div style="color:#61756e;font-size:13.5px;font-weight:600">Upcoming deadlines</div>
        </div>

        <div style="background:#fff;border-radius:22px;padding:20px 22px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -22px rgba(16,54,45,.4)">
            <div style="width:42px;height:42px;border-radius:13px;display:flex;align-items:center;justify-content:center;background:#e6ecf5;color:#41556f">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H8l-4 4V5a2 2 0 0 1 2-2h13a2 2 0 0 1 2 2z"/>
                </svg>
            </div>
            <div style="font-size:30px;font-weight:800;letter-spacing:-.02em;margin:14px 0 2px"><?= $unreadMessages ?></div>
            <div style="color:#61756e;font-size:13.5px;font-weight:600">Unread messages</div>
        </div>

        <div style="background:#fff;border-radius:22px;padding:20px 22px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -22px rgba(16,54,45,.4)">
            <div style="width:42px;height:42px;border-radius:13px;display:flex;align-items:center;justify-content:center;<?= $amlStatus === 'Complete' ? 'background:#e2f3ea;color:#3f9d6d' : 'background:#fdecdc;color:#e07d24' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z m9 11.5 2 2 4-4"/>
                </svg>
            </div>
            <div style="font-size:30px;font-weight:800;letter-spacing:-.02em;margin:14px 0 2px"><?= $amlStatus ?></div>
            <div style="color:#61756e;font-size:13.5px;font-weight:600">AML status</div>
        </div>
    </div>

    <!-- Main Workspace Split -->
    <div style="display:flex;gap:20px;flex-wrap:wrap">
        <!-- Outstanding Requests Panel -->
        <div style="flex:2;min-width:340px;background:#fff;border-radius:24px;padding:26px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -24px rgba(16,54,45,.4)">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px">
                <h3 style="margin:0;font-size:17px;font-weight:700">Outstanding requests</h3>
                <span style="font-size:12.5px;font-weight:700;color:#e07d24;background:#fdecdc;padding:5px 11px;border-radius:999px"><?= count($requests) ?> to action</span>
            </div>
            
            <?php if (empty($requests)): ?>
                <div style="padding:24px;text-align:center;color:#8a9a94;font-size:14px;font-weight:500">
                    No outstanding document requests right now.
                </div>
            <?php else: ?>
                <?php foreach ($requests as $r): ?>
                    <div style="display:flex;align-items:center;gap:16px;padding:16px;border:1px solid rgba(20,60,50,.08);border-radius:18px;margin-bottom:12px">
                        <div style="width:44px;height:44px;border-radius:13px;background:#fdecdc;color:#e07d24;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 15V3 m7 8 5-5 5 5 M20 16.5V19a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2.5"/>
                            </svg>
                        </div>
                        <div style="flex:1;min-width:0">
                            <div style="font-weight:700;font-size:15px"><?= htmlspecialchars($r['title']) ?></div>
                            <div style="color:#61756e;font-size:13px;margin-top:2px">Due <?= date('d M Y', strtotime($r['due_date'])) ?> &middot; <?= htmlspecialchars($r['description'] ?? 'Upload files when ready') ?></div>
                        </div>
                        <a href="/client/documents/upload" style="background:#ef8f3c;color:#fff;border:none;padding:11px 18px;border-radius:13px;font-weight:700;font-size:13.5px;cursor:pointer;white-space:nowrap;box-shadow:0 8px 16px -9px rgba(239,143,60,.9)">Upload documents</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Quick Actions Panel -->
        <div style="flex:1;min-width:260px;background:#fff;border-radius:24px;padding:26px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -24px rgba(16,54,45,.4)">
            <h3 style="margin:0 0 18px;font-size:17px;font-weight:700">Quick actions</h3>
            <div style="display:flex;flex-direction:column;gap:12px">
                <a href="/client/documents/upload" style="display:flex;align-items:center;gap:14px;padding:15px 16px;border-radius:17px;background:#dff1ee;cursor:pointer;color:#213330">
                    <div style="width:40px;height:40px;border-radius:12px;background:#0d9488;color:#fff;display:flex;align-items:center;justify-content:center">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 15V3 m7 8 5-5 5 5 M20 16.5V19a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2.5"/>
                        </svg>
                    </div>
                    <div style="font-weight:700;font-size:14.5px">Upload documents</div>
                </a>
                
                <a href="/client/meetings/book" style="display:flex;align-items:center;gap:14px;padding:15px 16px;border-radius:17px;background:#f3f7f5;cursor:pointer;color:#213330">
                    <div style="width:40px;height:40px;border-radius:12px;background:#ef8f3c;color:#fff;display:flex;align-items:center;justify-content:center">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M8 2v4 M16 2v4 M4 9h16 M5 4h14a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z m9 15 2 2 4-4"/>
                        </svg>
                    </div>
                    <div style="font-weight:700;font-size:14.5px">Book a meeting</div>
                </a>

                <a href="/client/messages" style="display:flex;align-items:center;gap:14px;padding:15px 16px;border-radius:17px;background:#f3f7f5;cursor:pointer;color:#213330">
                    <div style="width:40px;height:40px;border-radius:12px;background:#41556f;color:#fff;display:flex;align-items:center;justify-content:center">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15a2 2 0 0 1-2 2H8l-4 4V5a2 2 0 0 1 2-2h13a2 2 0 0 1 2 2z"/>
                        </svg>
                    </div>
                    <div style="font-weight:700;font-size:14.5px">Messages</div>
                </a>
            </div>
        </div>
    </div>
</div>
