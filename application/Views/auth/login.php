<main class="auth-shell">
  <div class="auth-atmosphere" aria-hidden="true"></div>

  <section class="auth-container" aria-labelledby="auth-title">
    <div class="auth-brand">
      <img class="auth-brand__logo" src="/assets/images/trinova-accounting-login-logo.png" alt="TriNova Accounting">
    </div>

    <div class="auth-card">
      <header class="auth-card__header">
        <h1 id="auth-title">Sign in to your portal</h1>
        <p>Welcome back. Access your practice documents, messages, and compliance deadlines in one secure place.</p>
      </header>

      <?php if (!empty($error)): ?>
        <div class="auth-alert auth-alert--error" id="login-error" role="alert">
          <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="9"></circle><path d="M12 8v5"></path><path d="M12 16.5h.01"></path>
          </svg>
          <span><?= htmlspecialchars($error) ?></span>
        </div>
      <?php endif; ?>

      <?php if (!empty($success)): ?>
        <div class="auth-alert auth-alert--success" id="login-success" role="status">
          <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="9"></circle><path d="m8 12 2.6 2.6L16.5 9"></path>
          </svg>
          <span><?= htmlspecialchars($success) ?></span>
        </div>
      <?php endif; ?>

      <form class="auth-form" action="/login" method="POST" data-login-form>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">

        <div class="auth-field">
          <label for="loginEmail">Email address</label>
          <div class="auth-input-wrap">
            <svg class="auth-input-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="5" width="18" height="14" rx="2.5"></rect><path d="m4 7 8 6 8-6"></path>
            </svg>
            <input class="auth-input" id="loginEmail" type="email" name="email" placeholder="name@company.co.uk" required autocomplete="email" autocapitalize="none" spellcheck="false"<?= !empty($error) ? ' aria-describedby="login-error" aria-invalid="true"' : '' ?>>
          </div>
        </div>

        <div class="auth-field">
          <div class="auth-label-row">
            <label for="loginPassword">Password</label>
            <a class="auth-forgot" href="/password/reset">Forgot password?</a>
          </div>
          <div class="auth-input-wrap">
            <svg class="auth-input-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <rect x="4" y="10" width="16" height="11" rx="2.5"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path>
            </svg>
            <input class="auth-input auth-input--password" id="loginPassword" type="password" name="password" placeholder="Enter your password" required autocomplete="current-password"<?= !empty($error) ? ' aria-describedby="login-error" aria-invalid="true"' : '' ?>>
            <button class="auth-password-toggle" type="button" data-password-toggle aria-label="Show password" aria-pressed="false" aria-controls="loginPassword">
              <svg class="auth-eye auth-eye--show" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"></path><circle cx="12" cy="12" r="2.5"></circle>
              </svg>
              <svg class="auth-eye auth-eye--hide" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="m3 3 18 18"></path><path d="M10.7 6.1A10.6 10.6 0 0 1 12 6c6 0 9.5 6 9.5 6a15.5 15.5 0 0 1-2.1 2.8"></path><path d="M6.6 6.6A15.6 15.6 0 0 0 2.5 12s3.5 6 9.5 6a9.5 9.5 0 0 0 4-.9"></path>
              </svg>
            </button>
          </div>
        </div>

        <button class="auth-submit" type="submit" data-login-submit>
          <span class="auth-submit__label">Sign in securely</span>
          <svg class="auth-submit__arrow" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="m14 7 5 5-5 5"></path></svg>
          <span class="auth-submit__spinner" aria-hidden="true"></span>
        </button>
      </form>

      <div class="auth-trust">
        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"></path><path d="m9.5 12 1.7 1.7 3.5-3.7"></path></svg>
        <span>Protected with secure encrypted sign-in</span>
      </div>
    </div>

    <p class="auth-support">Need account assistance? Call TriNova Accounting on <a href="tel:+441134960100">0113 496 0100</a></p>
  </section>
</main>
