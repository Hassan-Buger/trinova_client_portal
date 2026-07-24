<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:28px;background:radial-gradient(1200px 620px at 15% -10%,#dff1ee 0%,rgba(223,241,238,0) 60%),radial-gradient(1000px 560px at 110% 120%,#fdecdc 0%,rgba(253,236,220,0) 55%),#eef4f1">
  <div style="width:100%;max-width:428px;animation:tnpop .4s cubic-bezier(.22,.61,.36,1)">
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
      <h1 style="margin:0 0 6px;font-size:24px;font-weight:800;letter-spacing:-.02em">Reset your password</h1>
      <p style="margin:0 0 26px;color:#61756e;font-size:14.5px;line-height:1.5">Enter your email and we will send a secure link to set a new password.</p>
      
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

      <form action="/password/reset" method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
        <label style="display:block;font-size:12.5px;font-weight:600;color:#3a4d47;margin-bottom:7px">Email address</label>
        <input type="email" name="email" placeholder="you@example.co.uk" required style="width:100%;padding:14px 16px;border:1.5px solid #e0e9e5;border-radius:15px;font-size:15px;margin-bottom:24px;background:#fbfdfc">
        <button type="submit" style="width:100%;padding:15px;background:#0d9488;color:#fff;border:none;border-radius:15px;font-size:15.5px;font-weight:700;cursor:pointer;box-shadow:0 8px 18px -8px rgba(13,148,136,.7)">Send reset link</button>
      </form>
      <div style="text-align:center;margin-top:18px">
        <a href="/login" style="font-size:13px;font-weight:600;cursor:pointer">&larr; Back to sign in</a>
      </div>
    </div>
  </div>
</div>
