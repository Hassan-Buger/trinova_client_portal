<div class="tn-screen" style="max-width:1120px">
    <div style="margin-bottom:24px">
        <p style="margin:0;color:#61756e;font-size:14.5px">Select a client thread to read conversation history and send responses.</p>
    </div>

    <div style="display:flex;gap:20px;min-height:560px;flex-wrap:wrap">
        <!-- Client Threads Sidebar -->
        <div style="width:300px;flex-shrink:0;background:#fff;border-radius:24px;padding:20px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -24px rgba(16,54,45,.4);display:flex;flex-direction:column;gap:8px">
            <h3 style="margin:0 0 12px;font-size:16px;font-weight:800;color:#213330;padding:0 6px">Client Accounts</h3>
            <?php foreach ($clients as $c): ?>
                <?php
                $isSelected = ((int)$c['id'] === (int)$selectedClientId);
                $itemBg = $isSelected ? 'background:#e6ecf5;border-color:transparent;' : 'background:#fbfdfc;border-color:rgba(20,60,50,.08);';
                ?>
                <a href="/staff/messages?client_id=<?= $c['id'] ?>" data-ajax-link style="display:block;padding:14px;border-radius:16px;border:1px solid;<?= $itemBg ?>text-decoration:none;transition:all .15s">
                    <div style="font-weight:700;font-size:14.5px;color:#213330"><?= htmlspecialchars($c['name']) ?></div>
                    <div style="font-size:12.5px;color:#7d8e88;margin-top:2px"><?= htmlspecialchars($c['email']) ?></div>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Selected Thread Panel -->
        <div style="flex:1;min-width:340px;background:#fff;border-radius:24px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -24px rgba(16,54,45,.4);display:flex;flex-direction:column;overflow:hidden">
            <?php if (!$activeClient): ?>
                <div style="flex:1;display:flex;align-items:center;justify-content:center;color:#8a9a94">Select a client thread to start messaging.</div>
            <?php else: ?>
                <div style="padding:18px 24px;border-bottom:1px solid #eef4f1;background:#fbfdfc;display:flex;align-items:center;justify-content:space-between">
                    <div>
                        <h3 style="margin:0;font-size:17px;font-weight:800;color:#213330"><?= htmlspecialchars($activeClient['name']) ?></h3>
                        <div style="font-size:12.5px;color:#7d8e88"><?= htmlspecialchars($activeClient['email']) ?> &middot; Phone: <?= htmlspecialchars($activeClient['phone'] ?? 'N/A') ?></div>
                    </div>
                    <span style="font-size:12px;font-weight:700;color:#41556f;background:#e6ecf5;padding:4px 10px;border-radius:999px">Shared Access</span>
                </div>

                <div data-message-thread data-current-role="staff" data-feed-url="/staff/messages/feed?client_id=<?= (int) $activeClient['id'] ?>" style="flex:1;padding:24px;overflow-y:auto;display:flex;flex-direction:column;gap:14px;background:#fcfdfe;max-height:560px">
                    <?php if (empty($messages)): ?>
                        <div data-empty-thread style="padding:48px 0;text-align:center;color:#8a9a94;font-size:14px">No messages in this client thread yet.</div>
                    <?php else: ?>
                        <?php $lastMessageDay = null; ?>
                        <?php foreach ($messages as $msg): ?>
                            <?php
                            $isStaff = ($msg['sender_role'] === 'staff');
                            $messageDay = date('Y-m-d', strtotime($msg['created_at']));
                            ?>
                            <?php if ($messageDay !== $lastMessageDay): ?>
                                <div class="tn-message-day"><?= date('l, d M Y', strtotime($msg['created_at'])) ?></div>
                                <?php $lastMessageDay = $messageDay; ?>
                            <?php endif; ?>
                            <article class="tn-message-bubble <?= $isStaff ? 'is-mine' : 'is-theirs' ?>" data-message-id="<?= (int) $msg['id'] ?>" data-message-day="<?= $messageDay ?>">
                                <div class="tn-message-meta" title="<?= htmlspecialchars(date('l, d F Y \a\t H:i', strtotime($msg['created_at']))) ?>">
                                    <?= htmlspecialchars($msg['sender_name'] ?? 'User') ?> &middot; <?= date('H:i', strtotime($msg['created_at'])) ?>
                                </div>
                                <div class="tn-message-body"><?= htmlspecialchars($msg['body']) ?></div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div style="padding:18px 24px;border-top:1px solid #eef4f1;background:#fff">
                    <form action="/staff/messages/send" method="POST" data-ajax-form data-message-form data-ajax-refresh="false" style="margin:0;display:flex;gap:12px">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
                        <input type="hidden" name="client_id" value="<?= $activeClient['id'] ?>">
                        <select name="entity_id" required aria-label="Message context" style="max-width:210px;padding:10px;border:1.5px solid #e0e9e5;border-radius:14px;background:#fbfdfc"><?php foreach(($entities??[]) as $entity): ?><?php if((int)$entity['client_id']===(int)$activeClient['id']): ?><option value="<?= (int)$entity['id'] ?>"><?= htmlspecialchars($entity['company_name']) ?></option><?php endif; ?><?php endforeach; ?></select>
                        <textarea name="body" rows="2" placeholder="Write response to <?= htmlspecialchars($activeClient['name']) ?>..." required style="flex:1;padding:12px 16px;border:1.5px solid #e0e9e5;border-radius:14px;font-size:14px;background:#fbfdfc;resize:none"></textarea>
                        <button type="submit" data-loading-text="Sending…" style="background:#41556f;color:#fff;border:none;padding:0 22px;border-radius:14px;font-weight:700;font-size:14px;cursor:pointer;white-space:nowrap">Send Reply</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
