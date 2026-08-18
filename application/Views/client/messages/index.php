<div class="tn-screen" style="max-width:960px">
    <div style="margin-bottom:24px">
        <p style="margin:0;color:#61756e;font-size:14.5px">Direct communication thread with your dedicated TriNova accounting team.</p>
    </div>

    <div style="background:#fff;border-radius:24px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -24px rgba(16,54,45,.4);overflow:hidden;display:flex;flex-direction:column;height:calc(100vh - 165px);min-height:520px;max-height:720px">
        <!-- Thread Header -->
        <div style="padding:18px 26px;border-bottom:1px solid #eef4f1;background:#fbfdfc;display:flex;align-items:center;justify-content:space-between;flex-shrink:0">
            <div style="display:flex;align-items:center;gap:12px">
                <div style="width:40px;height:40px;border-radius:12px;background:#0d9488;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800">
                    TN
                </div>
                <div>
                    <div style="font-weight:800;font-size:15px;color:#213330">TriNova Accounting Team</div>
                    <div style="font-size:12.5px;color:#7d8e88">Your assigned accounting team</div>
                </div>
            </div>
            <span style="font-size:12px;font-weight:700;color:#3f9d6d;background:#e2f3ea;padding:4px 10px;border-radius:999px">&bull; Active Support</span>
        </div>

        <!-- Messages Stream -->
        <div data-message-thread data-current-role="client" data-feed-url="/client/messages/feed" style="flex:1;padding:26px;overflow-y:auto;display:flex;flex-direction:column;gap:16px;background:#fcfdfe">
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
                        <div class="tn-message-meta" title="<?= htmlspecialchars(date('l, d F Y \a\t H:i', strtotime($msg['created_at']))) ?>" style="display:flex;justify-content:space-between;align-items:center">
                            <span><?= htmlspecialchars($msg['sender_name']) ?> &middot; <?= date('H:i', strtotime($msg['created_at'])) ?></span>
                            <?php if ($isMe): ?>
                                <button type="button" onclick="tnClientDeleteMsg(<?= (int)$msg['id'] ?>)" title="Delete message" style="background:none;border:none;color:#dc2626;cursor:pointer;font-size:11px;font-weight:700;margin-left:8px;padding:0">Delete</button>
                            <?php endif; ?>
                        </div>
                        <div class="tn-message-body"><?= htmlspecialchars($msg['body']) ?></div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Reply Input Form -->
        <div style="padding:16px 26px;border-top:1px solid #eef4f1;background:#fff;flex-shrink:0">
            <form action="/client/messages/send" method="POST" data-ajax-form data-message-form data-ajax-refresh="false" style="margin:0;display:flex;align-items:flex-end;gap:12px;width:100%">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
                <textarea name="body" rows="2" placeholder="Type your message to the TriNova team..." required style="flex:1;width:100%;min-height:48px;max-height:120px;padding:12px 16px;border:1.5px solid #e0e9e5;border-radius:16px;font-size:14px;background:#fbfdfc;resize:none;outline:none"></textarea>
                <button type="submit" data-loading-text="Sending…" style="background:#0d9488;color:#fff;border:none;height:48px;padding:0 24px;border-radius:16px;font-weight:700;font-size:14px;cursor:pointer;white-space:nowrap;display:inline-flex;align-items:center;gap:8px;box-shadow:0 6px 16px -6px rgba(13,148,136,.6)">
                    <span>Send</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Delete Message Modal -->
<div id="deleteClientMsgModal" style="display:none;position:fixed;inset:0;background:rgba(20,40,35,.45);backdrop-filter:blur(6px);z-index:199;align-items:center;justify-content:center;padding:20px">
    <div style="background:#fff;border-radius:24px;width:100%;max-width:440px;padding:32px;box-shadow:0 24px 60px -28px rgba(0,0,0,.4)">
        <h3 style="margin:0 0 12px;font-size:19px;font-weight:800">Delete Message?</h3>
        <p style="color:#61756e;font-size:14px;margin:0 0 24px">This sent message will be soft-deleted.</p>
        <form action="/client/messages/delete" method="POST" data-ajax-form>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
            <input type="hidden" name="message_id" id="deleteClientMsgId">
            <div style="display:flex;justify-content:flex-end;gap:12px">
                <button type="button" onclick="document.getElementById('deleteClientMsgModal').style.display='none'" style="background:#f0f5f3;color:#5f726c;border:none;padding:11px 20px;border-radius:12px;font-weight:700;cursor:pointer">Cancel</button>
                <button type="submit" style="background:#dc2626;color:#fff;border:none;padding:11px 22px;border-radius:12px;font-weight:700;cursor:pointer">Delete Message</button>
            </div>
        </form>
    </div>
</div>

<script>
function tnClientDeleteMsg(id) {
    document.getElementById('deleteClientMsgId').value = id;
    document.getElementById('deleteClientMsgModal').style.display = 'flex';
}
</script>
