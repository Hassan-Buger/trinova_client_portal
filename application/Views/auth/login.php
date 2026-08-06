<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:28px;background:radial-gradient(1200px 620px at 15% -10%,#dff1ee 0%,rgba(223,241,238,0) 60%),radial-gradient(1000px 560px at 110% 120%,#fdecdc 0%,rgba(253,236,220,0) 55%),#eef4f1">
  <div style="width:100%;max-width:428px;animation:tnpop .4s cubic-bezier(.22,.61,.36,1)">
    <img class="tn-auth-logo" src="/assets/images/trinova-accounting-login-logo.png" alt="TriNova Accounting">
    
    <div style="background:#fff;border-radius:28px;padding:36px 34px;box-shadow:0 2px 4px rgba(16,54,45,.04),0 24px 60px -28px rgba(16,54,45,.35)">
      <h1 style="margin:0 0 6px;font-size:24px;font-weight:800;letter-spacing:-.02em">Sign in to your portal</h1>
      <p style="margin:0 0 26px;color:#61756e;font-size:14.5px;line-height:1.5">Welcome back. Your documents, messages and deadlines are all in one calm place.</p>
      
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

      <form action="/login" method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
        
        <label style="display:block;font-size:12.5px;font-weight:600;color:#3a4d47;margin-bottom:7px">Email address</label>
        <input type="email" name="email" placeholder="test.client.alpha@example.invalid" required style="width:100%;padding:14px 16px;border:1.5px solid #e0e9e5;border-radius:15px;font-size:15px;margin-bottom:16px;background:#fbfdfc">
        
        <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:7px">
            <label style="font-size:12.5px;font-weight:600;color:#3a4d47">Password</label>
            <a href="/password/reset" style="font-size:12.5px;font-weight:600;cursor:pointer">Forgot password?</a>
        </div>
        <input type="password" name="password" placeholder="••••••••••" required style="width:100%;padding:14px 16px;border:1.5px solid #e0e9e5;border-radius:15px;font-size:15px;margin-bottom:24px;background:#fbfdfc">
        
        <button type="submit" style="width:100%;padding:15px;background:#0d9488;color:#fff;border:none;border-radius:15px;font-size:15.5px;font-weight:700;cursor:pointer;box-shadow:0 8px 18px -8px rgba(13,148,136,.7);transition:background .15s">Sign in securely</button>
      </form>

      <div style="display:flex;align-items:center;gap:8px;justify-content:center;margin-top:20px;color:#7d8e88;font-size:12.5px;font-weight:500">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
        </svg>
        <span>Invitation-only access &middot; protected sign-in</span>
      </div>
    </div>
    <p style="text-align:center;margin:20px 0 0;color:#8a9a94;font-size:12px">Need help? Call the TriNova team on 0113 496 0100</p>
  </div>
</div>
