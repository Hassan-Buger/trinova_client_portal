<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:28px;background:radial-gradient(1200px 620px at 15% -10%,#dff1ee 0%,rgba(223,241,238,0) 60%),radial-gradient(1000px 560px at 110% 120%,#fdecdc 0%,rgba(253,236,220,0) 55%),#eef4f1">
  <div style="width:100%;max-width:440px;animation:tnpop .4s cubic-bezier(.22,.61,.36,1)">
    <div style="display:flex;align-items:center;gap:12px;justify-content:center;margin-bottom:26px">
      <svg width="48" height="48" viewBox="0 0 40 40" fill="none">
        <rect x="0" y="0" width="40" height="40" rx="12" fill="#0d9488"/>
        <path d="M20 9 L30 28 L10 28 Z" fill="#fff" opacity="0.92"/>
        <path d="M20 17 L26 28 L14 28 Z" fill="#ef8f3c"/>
      </svg>
      <div>
        <div style="font-weight:800;font-size:18px;letter-spacing:-.01em">TriNova</div>
        <div style="font-size:11px;color:#61756e;font-weight:600;letter-spacing:.14em;text-transform:uppercase;margin-top:1px">Accounting</div>
      </div>
    </div>
    
    <div style="background:#fff;border-radius:28px;padding:36px 34px;box-shadow:0 2px 4px rgba(16,54,45,.04),0 24px 60px -28px rgba(16,54,45,.35)">
      <h1 style="margin:0 0 6px;font-size:23px;font-weight:800;letter-spacing:-.02em">Enter Verification Code</h1>
      <p style="margin:0 0 22px;color:#61756e;font-size:14px;line-height:1.5">A 6-digit verification code was sent to your email. Enter the code and set your new password below.</p>
      
      <?php if (!empty($error)): ?>
        <div style="margin-bottom:20px;padding:12px 16px;background:#fdecdc;color:#e07d24;border-radius:14px;font-size:13.5px;font-weight:600">
            <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($success)): ?>
        <div style="margin-bottom:20px;padding:12px 16px;background:#e2f3ea;color:#3f9d6d;border-radius:14px;font-size:13.5px;font-weight:600">
            <?= htmlspecialchars($success) ?>
        </div>
      <?php endif; ?>

      <form action="/password/verify" method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
        
        <div style="margin-bottom:16px">
          <label style="display:block;font-size:12.5px;font-weight:600;color:#3a4d47;margin-bottom:6px">Email Address</label>
          <input type="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required style="width:100%;padding:13px 16px;border:1.5px solid #e0e9e5;border-radius:14px;font-size:14.5px;background:#fbfdfc">
        </div>

        <div style="margin-bottom:18px">
          <label style="display:block;font-size:12.5px;font-weight:600;color:#3a4d47;margin-bottom:6px">6-Digit Code</label>
          <input type="text" name="code" placeholder="123456" maxlength="6" pattern="[0-9]{6}" required style="width:100%;padding:14px 16px;border:1.5px solid #e0e9e5;border-radius:14px;font-size:20px;font-weight:800;letter-spacing:6px;text-align:center;background:#fbfdfc;color:#0d9488">
        </div>

        <div style="margin-bottom:16px">
          <label style="display:block;font-size:12.5px;font-weight:600;color:#3a4d47;margin-bottom:6px">New Password</label>
          <input type="password" name="password" placeholder="At least 8 characters" required style="width:100%;padding:13px 16px;border:1.5px solid #e0e9e5;border-radius:14px;font-size:14.5px;background:#fbfdfc">
        </div>

        <div style="margin-bottom:24px">
          <label style="display:block;font-size:12.5px;font-weight:600;color:#3a4d47;margin-bottom:6px">Confirm New Password</label>
          <input type="password" name="password_confirm" placeholder="Repeat new password" required style="width:100%;padding:13px 16px;border:1.5px solid #e0e9e5;border-radius:14px;font-size:14.5px;background:#fbfdfc">
        </div>

        <button type="submit" style="width:100%;padding:15px;background:#0d9488;color:#fff;border:none;border-radius:15px;font-size:15.5px;font-weight:700;cursor:pointer;box-shadow:0 8px 18px -8px rgba(13,148,136,.7)">Verify Code &amp; Reset Password</button>
      </form>

      <div style="text-align:center;margin-top:18px">
        <a href="/login" style="font-size:13px;font-weight:600;color:#0d9488">&larr; Back to sign in</a>
      </div>
    </div>
  </div>
</div>
