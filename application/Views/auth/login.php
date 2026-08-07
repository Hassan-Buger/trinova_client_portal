<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;background:radial-gradient(1200px 700px at 10% -10%,#d5ebe6 0%,rgba(213,235,230,0) 65%),radial-gradient(1000px 600px at 110% 115%,#fde2ce 0%,rgba(253,226,206,0) 60%),#eef4f1;font-family:'Plus Jakarta Sans',system-ui,sans-serif">
  <div style="width:100%;max-width:440px;animation:tnpop .4s cubic-bezier(.22,.61,.36,1)">
    <div style="text-align:center;margin-bottom:28px">
      <img class="tn-auth-logo" src="/assets/images/trinova-accounting-login-logo.png" alt="TriNova Accounting" style="max-width:240px;height:auto;margin:0 auto;display:block;filter:drop-shadow(0 4px 12px rgba(13,148,136,.12))">
    </div>
    
    <div style="background:rgba(255,255,255,0.92);backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,0.8);border-radius:32px;padding:40px 36px;box-shadow:0 4px 6px -1px rgba(16,54,45,.03),0 28px 70px -24px rgba(16,54,45,.3)">
      <div style="margin-bottom:28px">
        <h1 style="margin:0 0 8px;font-size:26px;font-weight:800;letter-spacing:-.03em;color:#18332d">Sign in to your portal</h1>
        <p style="margin:0;color:#61756e;font-size:14px;line-height:1.55">Welcome back. Access your practice documents, messages, and compliance deadlines in one secure place.</p>
      </div>
      
      <?php if (!empty($error)): ?>
        <div style="margin-bottom:22px;padding:14px 18px;background:#fdecdc;color:#c25e10;border-left:4px solid #e07d24;border-radius:14px;font-size:13.5px;font-weight:600;display:flex;align-items:center;gap:10px">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
      <?php endif; ?>

      <?php if (!empty($success)): ?>
        <div style="margin-bottom:22px;padding:14px 18px;background:#e2f3ea;color:#287a52;border-left:4px solid #3f9d6d;border-radius:14px;font-size:13.5px;font-weight:600;display:flex;align-items:center;gap:10px">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
            <span><?= htmlspecialchars($success) ?></span>
        </div>
      <?php endif; ?>

      <form action="/login" method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
        
        <div style="margin-bottom:20px">
            <label for="loginEmail" style="display:block;font-size:13px;font-weight:700;color:#2a3e38;margin-bottom:8px">Email address</label>
            <div style="position:relative">
                <input id="loginEmail" type="email" name="email" placeholder="name@company.co.uk" required autocomplete="email" style="width:100%;padding:14px 16px 14px 44px;border:1.5px solid #dce8e4;border-radius:16px;font-size:14.5px;background:#fcfdfe;color:#18332d;transition:all .15s ease">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#7d8e88" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);pointer-events:none">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                    <polyline points="22,6 12,13 2,6"></polyline>
                </svg>
            </div>
        </div>
        
        <div style="margin-bottom:24px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                <label for="loginPassword" style="font-size:13px;font-weight:700;color:#2a3e38">Password</label>
                <a href="/password/reset" style="font-size:12.5px;font-weight:700;color:#0d9488;text-decoration:none">Forgot password?</a>
            </div>
            <div style="position:relative">
                <input id="loginPassword" type="password" name="password" placeholder="••••••••••••" required autocomplete="current-password" style="width:100%;padding:14px 44px 14px 44px;border:1.5px solid #dce8e4;border-radius:16px;font-size:14.5px;background:#fcfdfe;color:#18332d;transition:all .15s ease">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#7d8e88" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);pointer-events:none">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                <button type="button" onclick="const p=document.getElementById('loginPassword');const isP=p.type==='password';p.type=isP?'text':'password';this.querySelector('svg').style.stroke=isP?'#0d9488':'#7d8e88'" title="Toggle password visibility" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;padding:6px;cursor:pointer;display:flex;align-items:center;justify-content:center">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#7d8e88" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </button>
            </div>
        </div>
        
        <button type="submit" style="width:100%;padding:15px;background:#0d9488;color:#fff;border:none;border-radius:16px;font-size:15px;font-weight:700;cursor:pointer;box-shadow:0 10px 22px -8px rgba(13,148,136,.65);transition:all .15s ease" onmouseover="this.style.background='#0f766e'" onmouseout="this.style.background='#0d9488'">
            Sign in securely &rarr;
        </button>
      </form>

      <div style="display:flex;align-items:center;gap:8px;justify-content:center;margin-top:24px;color:#7d8e88;font-size:12.5px;font-weight:600">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
        </svg>
        <span>Protected 256-bit encrypted sign-in</span>
      </div>
    </div>
    
    <p style="text-align:center;margin:24px 0 0;color:#8a9a94;font-size:12.5px;font-weight:500">Need account assistance? Call TriNova Accounting on <strong>0113 496 0100</strong></p>
  </div>
</div>
