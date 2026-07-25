<div class="tn-screen" style="max-width:960px">
    <div style="margin-bottom:24px">
        <h2 style="margin:0 0 6px;font-size:26px;font-weight:800;letter-spacing:-.02em">Secure Messages</h2>
        <p style="margin:0;color:#61756e;font-size:14.5px">Direct communication thread with your dedicated TriNova accounting team.</p>
    </div>

    <div style="background:#fff;border-radius:24px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -24px rgba(16,54,45,.4);overflow:hidden;display:flex;flex-direction:column;min-height:540px">
        <!-- Thread Header -->
        <div style="padding:20px 26px;border-bottom:1px solid #eef4f1;background:#fbfdfc;display:flex;align-items:center;justify-content:space-between">
            <div style="display:flex;align-items:center;gap:12px">
                <div style="width:40px;height:40px;border-radius:12px;background:#0d9488;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800">
                    TN
                </div>
                <div>
                    <div style="font-weight:800;font-size:15px;color:#213330">TriNova Accounting Team</div>
                    <div style="font-size:12.5px;color:#7d8e88">Kirsty, Jane, Emma & Jess</div>
                </div>
            </div>
            <span style="font-size:12px;font-weight:700;color:#3f9d6d;background:#e2f3ea;padding:4px 10px;border-radius:999px">&bull; Active Support</span>
        </div>

        <!-- Messages Stream -->
        <div style="flex:1;padding:26px;overflow-y:auto;display:flex;flex-direction:column;gap:16px;background:#fcfdfe">
            <?php if (empty($messages)): ?>
                <div style="padding:48px 0;text-align:center;color:#8a9a94;font-size:14px">
                    No messages in this thread yet. Send a message below to reach your accounting team.
                </div>
            <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                    <?php
                    $isMe = ($msg['sender_role'] === 'client');
                    $bubbleBg = $isMe ? '#0d9488' : '#fff';
                    $textColor = $isMe ? '#ffffff' : '#213330';
                    $align = $isMe ? 'align-self:flex-end;border-bottom-right-radius:4px;' : 'align-self:flex-start;border-bottom-left-radius:4px;border:1px solid rgba(20,60,50,.08);';
                    ?>
                    <div style="max-width:78%;<?= $align ?>border-radius:18px;padding:16px 20px;box-shadow:0 2px 8px rgba(16,54,45,.04)">
                        <div style="font-size:12px;font-weight:700;margin-bottom:6px;<?= $isMe ? 'color:rgba(255,255,255,.85);' : 'color:#5f726c;' ?>">
                            <?= htmlspecialchars($msg['sender_name']) ?> &middot; <?= date('H:i, d M Y', strtotime($msg['created_at'])) ?>
                        </div>
                        <div style="font-size:14.5px;line-height:1.5;color:<?= $textColor ?>;white-space:pre-wrap"><?= htmlspecialchars($msg['body']) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Reply Input Form -->
        <div style="padding:20px 26px;border-top:1px solid #eef4f1;background:#fff">
            <form action="/client/messages/send" method="POST" style="margin:0;display:flex;gap:12px">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
                <textarea name="body" rows="2" placeholder="Type your message to the TriNova team..." required style="flex:1;padding:13px 16px;border:1.5px solid #e0e9e5;border-radius:15px;font-size:14px;background:#fbfdfc;resize:none"></textarea>
                <button type="submit" style="background:#0d9488;color:#fff;border:none;padding:0 24px;border-radius:15px;font-weight:700;font-size:14.5px;cursor:pointer;box-shadow:0 8px 18px -8px rgba(13,148,136,.7);white-space:nowrap">Send Message</button>
            </form>
        </div>
    </div>
</div>
