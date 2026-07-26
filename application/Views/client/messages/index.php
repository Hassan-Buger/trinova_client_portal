<div class="tn-screen" style="max-width:960px">
    <div style="margin-bottom:24px">
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
        <div data-message-thread data-current-role="client" data-feed-url="/client/messages/feed" style="flex:1;padding:26px;overflow-y:auto;display:flex;flex-direction:column;gap:16px;background:#fcfdfe;max-height:560px">
            <?php if (empty($messages)): ?>
                <div data-empty-thread style="padding:48px 0;text-align:center;color:#8a9a94;font-size:14px">
                    No messages in this thread yet. Send a message below to reach your accounting team.
                </div>
            <?php else: ?>
                <?php $lastMessageDay = null; ?>
                <?php foreach ($messages as $msg): ?>
                    <?php
                    $isMe = ($msg['sender_role'] === 'client');
                    $messageDay = date('Y-m-d', strtotime($msg['created_at']));
                    ?>
                    <?php if ($messageDay !== $lastMessageDay): ?>
                        <div class="tn-message-day"><?= date('l, d M Y', strtotime($msg['created_at'])) ?></div>
                        <?php $lastMessageDay = $messageDay; ?>
                    <?php endif; ?>
                    <article class="tn-message-bubble <?= $isMe ? 'is-mine' : 'is-theirs' ?>" data-message-id="<?= (int) $msg['id'] ?>" data-message-day="<?= $messageDay ?>">
                        <div class="tn-message-meta" title="<?= htmlspecialchars(date('l, d F Y \a\t H:i', strtotime($msg['created_at']))) ?>">
                            <?= htmlspecialchars($msg['sender_name']) ?> &middot; <?= date('H:i', strtotime($msg['created_at'])) ?>
                        </div>
                        <div class="tn-message-body"><?= htmlspecialchars($msg['body']) ?></div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Reply Input Form -->
        <div style="padding:20px 26px;border-top:1px solid #eef4f1;background:#fff">
            <form action="/client/messages/send" method="POST" data-ajax-form data-message-form data-ajax-refresh="false" style="margin:0;display:flex;gap:12px">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
                <textarea name="body" rows="2" placeholder="Type your message to the TriNova team..." required style="flex:1;padding:13px 16px;border:1.5px solid #e0e9e5;border-radius:15px;font-size:14px;background:#fbfdfc;resize:none"></textarea>
                <button type="submit" data-loading-text="Sending…" style="background:#0d9488;color:#fff;border:none;padding:0 24px;border-radius:15px;font-weight:700;font-size:14.5px;cursor:pointer;box-shadow:0 8px 18px -8px rgba(13,148,136,.7);white-space:nowrap">Send Message</button>
            </form>
        </div>
    </div>
</div>
