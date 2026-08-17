<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle ?? 'TriNova Client Portal') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif; color: #213330; background: #eef4f1; -webkit-font-smoothing: antialiased; }
        a { color: #0d9488; text-decoration: none; }
        a:hover { color: #0f766e; }
        input, button, textarea { font-family: inherit; }
        input:focus, textarea:focus { outline: none; border-color: #0d9488 !important; box-shadow: 0 0 0 4px rgba(13,148,136,.12); }
        input::placeholder, textarea::placeholder { color: #9aaba5; }
        ::-webkit-scrollbar { width: 10px; height: 10px; }
        ::-webkit-scrollbar-thumb { background: rgba(20,60,50,.14); border-radius: 20px; border: 3px solid transparent; background-clip: padding-box; }
        @keyframes tnfade { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
        @keyframes tnpop { from { opacity: 0; transform: scale(.96); } to { opacity: 1; transform: none; } }
        .tn-screen { animation: tnfade .34s cubic-bezier(.22,.61,.36,1); }
        .tn-page-progress { position:fixed;top:0;left:0;height:3px;width:0;background:#0d9488;z-index:9999;opacity:0;transition:width .2s ease,opacity .2s ease; }
        .tn-page-progress.is-loading { width:72%;opacity:1; }
        .tn-page-progress.is-complete { width:100%;opacity:0; }
        .tn-toast-stack { position:fixed;right:24px;bottom:24px;z-index:9998;display:flex;flex-direction:column;gap:10px;pointer-events:none; }
        .tn-toast { min-width:280px;max-width:420px;padding:14px 18px;border-radius:15px;background:#e2f3ea;color:#287a52;font-size:14px;font-weight:700;box-shadow:0 16px 40px -20px rgba(16,54,45,.55);animation:tnpop .22s ease; }
        .tn-toast.is-error { background:#fdecdc;color:#b45e18; }
        .tn-message-day { align-self:center;color:#7d8e88;background:#eef4f1;border-radius:999px;padding:5px 11px;font-size:11px;font-weight:800;letter-spacing:.02em; }
        .tn-message-bubble { max-width:78%;border-radius:18px;padding:15px 18px;box-shadow:0 2px 8px rgba(16,54,45,.04); }
        .tn-message-bubble.is-mine { align-self:flex-end;border-bottom-right-radius:4px;background:#0d9488;color:#fff; }
        [data-message-thread][data-current-role="staff"] .tn-message-bubble.is-mine { background:#41556f; }
        .tn-message-bubble.is-theirs { align-self:flex-start;border-bottom-left-radius:4px;background:#fff;color:#213330;border:1px solid rgba(20,60,50,.08); }
        .tn-message-meta { font-size:12px;font-weight:700;margin-bottom:5px;opacity:.78; }
        .tn-message-body { font-size:14px;line-height:1.5;white-space:pre-wrap;overflow-wrap:anywhere; }
        .tn-main[aria-busy="true"] #portal-content { opacity:.62;transition:opacity .15s ease; }
        .tn-welcome-text { margin-top:4px;color:#7d8e88;font-size:12.5px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
        .tn-notification-wrap { position:relative; }
        .tn-notification-button { width:44px;height:44px;border-radius:14px;border:1px solid rgba(20,60,50,.08);background:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#5f726c;position:relative; }
        .tn-notification-badge { position:absolute;top:5px;right:5px;min-width:18px;height:18px;padding:0 5px;background:#ef8f3c;color:#fff;border-radius:999px;border:2px solid #fff;font-size:10px;font-weight:800;line-height:14px;text-align:center; }
        .tn-notification-panel { position:absolute;right:0;top:52px;width:min(360px,calc(100vw - 32px));background:#fff;border:1px solid rgba(20,60,50,.09);border-radius:17px;box-shadow:0 22px 55px -24px rgba(16,54,45,.55);overflow:hidden;z-index:30; }
        .tn-notification-heading { padding:15px 17px;border-bottom:1px solid #edf2f0;font-size:13px;font-weight:800;color:#41556f; }
        .tn-notification-list { max-height:360px;overflow:auto; }
        .tn-notification-item { display:block;padding:13px 17px;border-bottom:1px solid #f0f4f2;color:#213330; }
        .tn-notification-item.is-unread { background:#eef8f5;border-left:3px solid #0d9488;padding-left:14px; }
        .tn-notification-item:hover { background:#f7faf9;color:#0f766e; }
        .tn-notification-title { display:block;font-size:12px;font-weight:800;color:#0f766e;margin-bottom:3px; }
        .tn-notification-message { display:block;font-size:13px;font-weight:700;line-height:1.4; }
        .tn-notification-time { display:block;margin-top:4px;color:#8a9a94;font-size:11px;font-weight:600; }
        .tn-notification-empty { padding:24px 17px;text-align:center;color:#8a9a94;font-size:13px; }
        .tn-side nav { min-height:0;overflow-y:auto;overflow-x:hidden;scrollbar-width:thin; }
        [data-entity-fields] input,
        [data-deadline-row] input,
        form[action="/staff/deadlines/create"] input:not([type="hidden"]) { width:100%;min-width:0;padding:11px 12px;border:1px solid #dfe9e5;border-radius:11px;background:#fbfdfc;color:#213330;font-size:13px; }
        [data-entity-fields] input:focus,
        [data-deadline-row] input:focus { background:#fff; }
        .tn-brand-logo { display:block;width:178px;max-width:100%;height:52px;object-fit:contain;object-position:left center; }
        .tn-auth-logo { display:block;width:230px;max-width:78vw;height:auto;margin:0 auto 26px; }
        /* Premium authentication surface */
        :root {
            --auth-bg:#f4f7f5;--auth-surface:rgba(255,255,255,.96);--auth-text:#172d29;
            --auth-muted:#63756f;--auth-subtle:#84938e;--auth-border:#dce6e2;
            --auth-brand:#0d9488;--auth-brand-hover:#0b7f75;--auth-danger:#b94a48;
            --auth-radius-card:28px;--auth-radius-input:15px;
            --auth-focus:0 0 0 4px rgba(13,148,136,.13);
            --auth-shadow:0 30px 70px -42px rgba(18,55,48,.42),0 10px 28px -22px rgba(18,55,48,.3),inset 0 1px 0 rgba(255,255,255,.9);
        }
        .auth-shell { position:relative;isolation:isolate;min-height:100vh;min-height:100dvh;display:grid;place-items:center;padding:clamp(28px,5vh,64px) 20px;background:var(--auth-bg);overflow:hidden; }
        .auth-atmosphere { position:absolute;inset:0;z-index:-1;pointer-events:none;background:radial-gradient(52rem 34rem at -8% -8%,rgba(68,181,166,.13),transparent 68%),radial-gradient(42rem 30rem at 108% 104%,rgba(232,145,100,.09),transparent 70%),radial-gradient(28rem 22rem at 92% 5%,rgba(155,149,192,.045),transparent 72%); }
        .auth-atmosphere::after { content:"";position:absolute;inset:0;opacity:.2;background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 180 180' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.9' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.055'/%3E%3C/svg%3E"); }
        .auth-container { width:min(100%,540px);animation:auth-enter .42s cubic-bezier(.22,.7,.25,1) both; }
        .auth-brand { display:flex;justify-content:center;margin-bottom:clamp(24px,3.5vh,32px); }
        .auth-brand__logo { display:block;width:clamp(205px,20vw,246px);max-width:72vw;height:auto;filter:drop-shadow(0 7px 15px rgba(20,70,61,.09)); }
        .auth-card { padding:clamp(30px,4vw,44px);background:var(--auth-surface);border:1px solid rgba(119,145,137,.2);border-radius:var(--auth-radius-card);box-shadow:var(--auth-shadow); }
        .auth-card__header { margin-bottom:28px; }
        .auth-card__header h1 { margin:0 0 10px;color:var(--auth-text);font-size:clamp(28px,3vw,32px);line-height:1.18;font-weight:750;letter-spacing:-.035em; }
        .auth-card__header p { max-width:440px;margin:0;color:var(--auth-muted);font-size:15.5px;line-height:1.62; }
        .auth-alert { display:flex;align-items:flex-start;gap:11px;margin:0 0 22px;padding:13px 15px;border:1px solid;border-radius:13px;font-size:13.5px;font-weight:600;line-height:1.45;animation:auth-alert-in .22s ease both; }
        .auth-alert svg { flex:0 0 18px;width:18px;height:18px;margin-top:1px; }
        .auth-alert--error { color:#9f3f3d;background:#fff6f5;border-color:#f1d2cf; }
        .auth-alert--success { color:#257154;background:#f0faf5;border-color:#cee9dc; }
        .auth-form { display:grid;gap:20px; }
        .auth-field label { display:block;color:#293e39;font-size:14px;font-weight:700;line-height:1.3; }
        .auth-label-row { display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:9px; }
        .auth-field:not(:has(.auth-label-row)) > label { margin-bottom:9px; }
        .auth-forgot { position:relative;color:var(--auth-brand);font-size:13.5px;font-weight:700;text-decoration:none; }
        .auth-forgot::after { content:"";position:absolute;right:0;bottom:-3px;left:0;height:1px;background:currentColor;transform:scaleX(0);transform-origin:right;transition:transform .18s ease; }
        .auth-forgot:hover { color:var(--auth-brand-hover); }
        .auth-forgot:hover::after { transform:scaleX(1);transform-origin:left; }
        .auth-input-wrap { position:relative; }
        .auth-input { display:block;width:100%;height:57px;padding:0 48px;color:var(--auth-text);background:#f8faf9;border:1px solid var(--auth-border);border-radius:var(--auth-radius-input);font-size:15px;font-weight:500;line-height:1;caret-color:var(--auth-brand);transition:border-color .18s ease,background-color .18s ease,box-shadow .18s ease,transform .18s ease; }
        .auth-input:not(.auth-input--password) { padding-right:16px; }
        .auth-input:hover:not(:disabled) { border-color:#c5d5d0;background:#fbfdfc; }
        .auth-input:focus { outline:0;border-color:var(--auth-brand) !important;background:#fff;box-shadow:var(--auth-focus); }
        .auth-input[aria-invalid="true"] { border-color:#d98a86;background:#fffafa; }
        .auth-input:disabled { color:#94a19d;background:#eef2f0;cursor:not-allowed; }
        .auth-input::placeholder { color:#98a6a1;opacity:1; }
        .auth-input-icon { position:absolute;top:50%;left:16px;width:19px;height:19px;color:#788b84;transform:translateY(-50%);pointer-events:none;transition:color .18s ease; }
        .auth-input-wrap:focus-within .auth-input-icon { color:var(--auth-brand); }
        .auth-password-toggle { position:absolute;top:50%;right:8px;width:40px;height:40px;display:grid;place-items:center;padding:0;color:#71847e;background:transparent;border:0;border-radius:10px;cursor:pointer;transform:translateY(-50%);transition:color .18s ease,background-color .18s ease; }
        .auth-password-toggle:hover { color:var(--auth-brand-hover);background:#eaf4f1; }
        .auth-password-toggle:focus-visible,.auth-forgot:focus-visible,.auth-support a:focus-visible { outline:2px solid var(--auth-brand);outline-offset:3px; }
        .auth-eye { width:19px;height:19px; }
        .auth-eye--hide { display:none; }
        .auth-password-toggle[aria-pressed="true"] .auth-eye--show { display:none; }
        .auth-password-toggle[aria-pressed="true"] .auth-eye--hide { display:block; }
        .auth-submit { position:relative;width:100%;height:58px;display:flex;align-items:center;justify-content:center;gap:9px;margin-top:4px;padding:0 22px;color:#fff;background:linear-gradient(180deg,#109b8f 0%,#0d8e83 100%);border:1px solid rgba(5,102,94,.25);border-radius:15px;box-shadow:0 13px 24px -14px rgba(13,148,136,.85),inset 0 1px 0 rgba(255,255,255,.18);font-size:15.5px;font-weight:750;letter-spacing:-.01em;cursor:pointer;transition:background .18s ease,box-shadow .18s ease,transform .18s ease; }
        .auth-submit:hover:not(:disabled) { background:linear-gradient(180deg,#0e8f84,#0b7f75);box-shadow:0 16px 28px -14px rgba(13,126,116,.8),inset 0 1px 0 rgba(255,255,255,.16);transform:translateY(-1px); }
        .auth-submit:active:not(:disabled) { box-shadow:0 7px 16px -12px rgba(13,126,116,.75);transform:translateY(0); }
        .auth-submit:focus-visible { outline:0;box-shadow:0 0 0 4px rgba(13,148,136,.2),0 13px 24px -14px rgba(13,148,136,.85); }
        .auth-submit:disabled { opacity:.78;cursor:wait; }
        .auth-submit__arrow { width:18px;height:18px;transition:transform .18s ease; }
        .auth-submit:hover .auth-submit__arrow { transform:translateX(2px); }
        .auth-submit__spinner { display:none;width:18px;height:18px;border:2px solid rgba(255,255,255,.42);border-top-color:#fff;border-radius:50%;animation:auth-spin .7s linear infinite; }
        .auth-submit.is-loading .auth-submit__arrow { display:none; }
        .auth-submit.is-loading .auth-submit__spinner { display:block; }
        .auth-trust { display:flex;align-items:center;justify-content:center;gap:8px;margin-top:23px;color:#687c75;font-size:12.5px;font-weight:650;text-align:center; }
        .auth-trust svg { flex:0 0 17px;width:17px;height:17px;color:#56887a; }
        .auth-support { margin:24px auto 0;color:#778983;font-size:12.5px;font-weight:550;line-height:1.65;text-align:center; }
        .auth-support a { color:#365e55;font-weight:750;white-space:nowrap; }
        .auth-support a:hover { color:var(--auth-brand-hover); }
        .auth-input:-webkit-autofill,.auth-input:-webkit-autofill:hover,.auth-input:-webkit-autofill:focus { -webkit-text-fill-color:var(--auth-text);-webkit-box-shadow:0 0 0 1000px #f8faf9 inset;transition:background-color 9999s ease-out;caret-color:var(--auth-text); }
        @keyframes auth-enter { from { opacity:0;transform:translateY(7px); } to { opacity:1;transform:none; } }
        @keyframes auth-alert-in { from { opacity:0;transform:translateY(-3px); } to { opacity:1;transform:none; } }
        @keyframes auth-spin { to { transform:rotate(360deg); } }
        @media(max-width:600px) {
            .auth-shell { align-items:center;padding:24px 16px; }
            .auth-brand { margin-bottom:22px; }
            .auth-brand__logo { width:205px; }
            .auth-card { padding:28px 24px;border-radius:22px; }
            .auth-card__header { margin-bottom:24px; }
            .auth-card__header p { font-size:14.5px; }
            .auth-input,.auth-submit { height:56px; }
            .auth-support { max-width:310px;margin-top:20px; }
        }
        @media(max-width:380px) {
            .auth-shell { padding-right:14px;padding-left:14px; }
            .auth-card { padding:25px 20px; }
            .auth-label-row { gap:12px; }
            .auth-forgot { font-size:13px; }
        }
        @media(prefers-reduced-motion:reduce) {
            .auth-container,.auth-alert { animation:none; }
            .auth-input,.auth-forgot::after,.auth-password-toggle,.auth-submit,.auth-submit__arrow { transition:none; }
        }
        /* Premium staff workspace */
        :root { --portal-page:#f3f7f5;--portal-card:#fff;--portal-ink:#172d29;--portal-muted:#657771;--portal-subtle:#87958f;--portal-line:#dfe8e4;--portal-teal:#0d9488;--portal-teal-dark:#0b776f;--portal-teal-soft:#e9f6f2;--portal-warning:#c66b2b;--portal-warning-soft:#fff4e9;--portal-radius:20px;--portal-shadow:0 1px 2px rgba(18,55,48,.025),0 18px 40px -34px rgba(18,55,48,.32); }
        .tn-main { background:var(--portal-page); }
        .staff-sidebar { width:258px;flex-shrink:0;height:100vh;display:flex;flex-direction:column;padding:24px 16px 18px;background:#fff;border-right:1px solid rgba(42,77,68,.09);position:sticky;top:0;z-index:10; }
        .staff-sidebar__brand { display:flex;align-items:center;height:72px;padding:2px 10px 20px;border-bottom:1px solid #edf2f0;margin-bottom:18px; }
        .staff-sidebar .tn-brand-logo { width:184px;height:auto;max-height:48px;object-fit:contain;object-position:left center; }
        .staff-sidebar nav { display:flex;flex:1;flex-direction:column;gap:4px;min-height:0;overflow-y:auto;overflow-x:hidden; }
        .staff-sidebar .tn-navitem { min-height:46px;display:flex;align-items:center;gap:12px;padding:10px 13px;color:#566a64;border:1px solid transparent;border-radius:12px;font-size:14px;font-weight:650;line-height:1;text-decoration:none;transition:color .18s ease,background-color .18s ease,border-color .18s ease,transform .18s ease; }
        .staff-sidebar .tn-navitem svg { width:20px;height:20px;flex:0 0 20px; }
        .staff-sidebar a.tn-navitem:hover { color:#21483f;background:#f2f8f6; }
        .staff-sidebar .tn-navitem.is-active { color:var(--portal-teal-dark);background:var(--portal-teal-soft);border-color:rgba(13,148,136,.09);font-weight:750; }
        .staff-sidebar .tn-navitem:focus-visible { outline:0;box-shadow:0 0 0 3px rgba(13,148,136,.15); }
        .staff-sidebar__section { margin:21px 13px 7px;color:#96a49f;font-size:10px;font-weight:800;letter-spacing:.1em;text-transform:uppercase; }
        .staff-sidebar__logout { margin:14px 0 0;padding-top:14px;border-top:1px solid #edf2f0; }
        .staff-sidebar__logout button { width:100%;background:transparent;cursor:pointer;text-align:left; }
        .staff-sidebar__logout button:hover { color:#314a44;background:#f5f8f7; }
        .tn-topbar { min-height:88px;display:flex;align-items:center;justify-content:space-between;gap:24px;padding:18px clamp(20px,3vw,38px);position:sticky;top:0;background:rgba(243,247,245,.9);border-bottom:1px solid rgba(44,80,71,.045);backdrop-filter:blur(14px);z-index:8; }
        .tn-topbar__title { min-width:0; }
        .tn-pagetitle { margin:0;color:var(--portal-ink);font-size:clamp(23px,2.2vw,30px);font-weight:750;line-height:1.2;letter-spacing:-.035em; }
        .tn-welcome-text { margin-top:5px;color:#72847e;font-size:12.5px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
        .tn-topbar__actions { display:flex;align-items:center;gap:12px; }
        .tn-notification-button { width:46px;height:46px;display:grid;place-items:center;padding:0;color:#5d706a;background:#fff;border:1px solid #dfe8e4;border-radius:13px;box-shadow:0 5px 14px -12px rgba(18,55,48,.6);cursor:pointer;position:relative;transition:color .18s ease,border-color .18s ease,background-color .18s ease,box-shadow .18s ease,transform .18s ease; }
        .tn-notification-button svg { width:20px;height:20px; }
        .tn-notification-button:hover { color:var(--portal-teal-dark);background:#fbfdfc;border-color:#cadbd5;transform:translateY(-1px); }
        .tn-notification-button:focus-visible { outline:0;box-shadow:0 0 0 4px rgba(13,148,136,.13); }
        .tn-profile { min-width:142px;height:48px;display:flex;align-items:center;gap:10px;padding:5px 12px 5px 6px;background:rgba(255,255,255,.72);border:1px solid rgba(44,80,71,.08);border-radius:14px; }
        .tn-profile__avatar { width:36px;height:36px;display:grid;place-items:center;flex:0 0 36px;color:#fff;background:#36574f;border-radius:10px;font-size:13px;font-weight:800; }
        .tn-profile__identity { min-width:0;display:flex;flex-direction:column;line-height:1.2; }
        .tn-profile__identity strong { max-width:110px;color:#2b423c;font-size:12.5px;font-weight:750;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
        .tn-profile__identity small { margin-top:3px;color:#87958f;font-size:10.5px;font-weight:600; }
        #portal-content { width:100%;max-width:1500px;margin:0 auto;padding:10px clamp(20px,3vw,38px) 48px !important; }
        .staff-dashboard { width:100%;color:var(--portal-ink); }
        .staff-dashboard__intro { min-height:42px;display:flex;align-items:flex-start;justify-content:space-between;gap:20px;margin:0 0 22px; }
        .staff-dashboard__intro p { max-width:670px;margin:0;color:var(--portal-muted);font-size:14.5px;line-height:1.6; }
        .staff-dashboard__date { flex:0 0 auto;color:#84938e;font-size:12px;font-weight:700; }
        .staff-metrics { display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:18px;margin-bottom:22px; }
        .staff-metric { min-height:166px;display:flex;flex-direction:column;padding:20px 21px;background:var(--portal-card);border:1px solid rgba(56,89,80,.105);border-radius:var(--portal-radius);box-shadow:var(--portal-shadow); }
        .staff-metric__top { display:flex;align-items:center;justify-content:space-between;gap:12px; }
        .staff-metric__label { color:#536862;font-size:13px;font-weight:700; }
        .staff-metric__icon { width:36px;height:36px;display:grid;place-items:center;color:var(--portal-teal-dark);background:#edf7f4;border-radius:11px; }
        .staff-metric__icon svg { width:18px;height:18px; }
        .staff-metric--warning .staff-metric__icon { color:var(--portal-warning);background:var(--portal-warning-soft); }
        .staff-metric__value { margin-top:auto;color:var(--portal-ink);font-size:36px;font-weight:750;line-height:1;letter-spacing:-.05em; }
        .staff-metric--warning .staff-metric__value { color:#8e4b22; }
        .staff-metric__context { margin-top:8px;color:#8a9893;font-size:11.5px;font-weight:550;line-height:1.35; }
        .staff-dashboard__grid { display:grid;grid-template-columns:minmax(0,1.8fr) minmax(310px,.9fr);gap:22px;align-items:start; }
        .staff-panel { background:var(--portal-card);border:1px solid rgba(56,89,80,.105);border-radius:22px;box-shadow:var(--portal-shadow); }
        .staff-panel__header { min-height:86px;display:flex;align-items:center;justify-content:space-between;gap:18px;padding:22px 24px;border-bottom:1px solid #edf2f0; }
        .staff-panel__header--compact { min-height:0;padding-bottom:18px;border-bottom:0; }
        .staff-panel__eyebrow { display:block;margin-bottom:5px;color:#8a9893;font-size:9.5px;font-weight:800;letter-spacing:.105em;text-transform:uppercase; }
        .staff-panel h2 { margin:0;color:#203833;font-size:18px;font-weight:750;letter-spacing:-.02em; }
        .staff-panel__link { display:inline-flex;align-items:center;gap:6px;color:var(--portal-teal-dark);font-size:11.5px;font-weight:750;white-space:nowrap; }
        .staff-panel__link svg { width:14px;height:14px;transition:transform .18s ease; }
        .staff-panel__link:hover svg { transform:translateX(2px); }
        .staff-panel__link:focus-visible { outline:2px solid var(--portal-teal);outline-offset:4px;border-radius:4px; }
        .staff-activity__list { padding:2px 24px 10px; }
        .staff-activity__item { min-height:63px;display:grid;grid-template-columns:12px minmax(0,1fr) auto;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid #eff3f1; }
        .staff-activity__item:last-child { border-bottom:0; }
        .staff-activity__marker { width:8px;height:8px;background:#65aa9a;border:2px solid #e6f4f0;border-radius:50%;box-shadow:0 0 0 3px #f4faf8; }
        .staff-activity__content { min-width:0;display:flex;align-items:baseline;gap:6px;font-size:12.5px;line-height:1.45; }
        .staff-activity__content strong { color:#2c443e;font-weight:750; }
        .staff-activity__content span { color:#6f817b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
        .staff-activity__item time { display:flex;align-items:center;gap:6px;color:#909d99;font-size:10.5px;white-space:nowrap; }
        .staff-activity__item time strong { color:#60736d;font-weight:700; }
        .staff-empty { min-height:290px;display:flex;align-items:center;justify-content:center;flex-direction:column;padding:36px;color:#899793;text-align:center; }
        .staff-empty__icon { width:42px;height:42px;display:grid;place-items:center;margin-bottom:13px;color:#739087;background:#f0f6f4;border-radius:13px; }
        .staff-empty__icon svg { width:20px;height:20px; }
        .staff-empty strong { color:#526761;font-size:13.5px; }
        .staff-empty > span:last-child { margin-top:5px;font-size:11.5px; }
        .staff-command { padding-bottom:22px;overflow:hidden; }
        .staff-search { padding:0 22px; }
        .staff-search label { display:block;margin-bottom:8px;color:#536862;font-size:12px;font-weight:700; }
        .staff-search__control { position:relative; }
        .staff-search__control > svg { position:absolute;top:50%;left:14px;width:18px;height:18px;color:#83938e;transform:translateY(-50%);pointer-events:none; }
        .staff-search input { width:100%;height:51px;padding:0 48px 0 42px;color:var(--portal-ink);background:#f8faf9;border:1px solid var(--portal-line);border-radius:13px;font-size:12.5px;font-weight:550;transition:border-color .18s ease,box-shadow .18s ease,background-color .18s ease; }
        .staff-search input:focus { background:#fff; }
        .staff-search button { position:absolute;top:7px;right:7px;width:37px;height:37px;display:grid;place-items:center;padding:0;color:#fff;background:var(--portal-teal);border:0;border-radius:10px;cursor:pointer;transition:background-color .18s ease,transform .18s ease; }
        .staff-search button:hover { background:var(--portal-teal-dark);transform:translateX(1px); }
        .staff-search button:focus-visible { outline:0;box-shadow:0 0 0 4px rgba(13,148,136,.17); }
        .staff-search button svg { width:16px;height:16px; }
        .staff-command__actions { display:grid;gap:10px;padding:18px 22px 0; }
        .staff-action { min-height:62px;display:grid;grid-template-columns:34px minmax(0,1fr) 16px;align-items:center;gap:11px;padding:10px 13px;border-radius:13px;transition:background-color .18s ease,border-color .18s ease,transform .18s ease,box-shadow .18s ease; }
        .staff-action > svg:first-child { width:19px;height:19px;justify-self:center; }
        .staff-action span { min-width:0;display:flex;flex-direction:column;gap:3px; }
        .staff-action strong { font-size:12.5px;font-weight:750; }
        .staff-action small { font-size:10.5px;font-weight:550;opacity:.75; }
        .staff-action__arrow { width:15px !important;height:15px !important;transition:transform .18s ease; }
        .staff-action:hover .staff-action__arrow { transform:translateX(2px); }
        .staff-action--primary { color:#fff;background:var(--portal-teal);border:1px solid #0b897f;box-shadow:0 9px 18px -15px rgba(13,148,136,.85); }
        .staff-action--primary:hover { color:#fff;background:var(--portal-teal-dark);transform:translateY(-1px); }
        .staff-action--secondary { color:#8d4e27;background:#fff7ef;border:1px solid #f1d8c5; }
        .staff-action--secondary:hover { color:#78401f;background:#fff2e5;border-color:#e8c5a9; }
        .staff-action:focus-visible { outline:0;box-shadow:0 0 0 4px rgba(13,148,136,.14); }
        .staff-attention { margin:20px 22px 0;padding-top:17px;border-top:1px solid #edf2f0; }
        .staff-attention__heading { display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;color:#677a74;font-size:10px;font-weight:800;letter-spacing:.07em;text-transform:uppercase; }
        .staff-attention__dot { width:7px;height:7px;background:#dd8140;border-radius:50%;box-shadow:0 0 0 3px #fff0e4; }
        .staff-attention a { min-height:35px;display:flex;align-items:center;justify-content:space-between;padding:6px 0;color:#63756f;font-size:11.5px;font-weight:650;border-bottom:1px solid #f1f4f3; }
        .staff-attention a:last-child { border-bottom:0; }
        .staff-attention a:hover { color:var(--portal-teal-dark); }
        .staff-attention strong { min-width:28px;padding:4px 7px;color:#9b5629;background:#fff1e5;border-radius:8px;font-size:10.5px;text-align:center; }
        @media(max-width:1180px) { .staff-metrics { grid-template-columns:repeat(2,minmax(0,1fr)); }.staff-metric { min-height:145px; }.staff-dashboard__grid { grid-template-columns:minmax(0,1.45fr) minmax(290px,.85fr); } }
        @media(max-width:900px) { .tn-profile { min-width:0;padding-right:6px; }.tn-profile__identity { display:none; }.staff-dashboard__grid { grid-template-columns:1fr; }.staff-command { display:grid;grid-template-columns:minmax(250px,.9fr) minmax(300px,1.1fr);padding:22px;gap:0 20px; }.staff-command .staff-panel__header { grid-column:1/-1;padding:0 0 18px; }.staff-search,.staff-command__actions { padding:0; }.staff-command__actions { padding-left:0; }.staff-attention { grid-column:1/-1;margin:20px 0 0; } }
        .tn-side > a:last-child { flex-shrink:0;background:#f6f9f8; }
        .tn-side > a:last-child:hover { background:#fdecdc;color:#b45e18 !important; }
        /* Mobile navigation drawer & hamburger styling */
        .tn-mobile-toggle { display:none;width:42px;height:42px;align-items:center;justify-content:center;padding:0;background:#fff;border:1px solid #dfe8e4;border-radius:12px;color:#354e47;cursor:pointer;flex-shrink:0;box-shadow:0 4px 12px -8px rgba(18,55,48,.3); }
        .tn-mobile-toggle svg { width:22px;height:22px; }
        .tn-mobile-close { display:none;width:36px;height:36px;align-items:center;justify-content:center;padding:0;background:#f0f5f3;border:none;border-radius:10px;color:#61756e;font-size:24px;line-height:1;cursor:pointer;margin-left:auto;flex-shrink:0; }
        .tn-mobile-close:hover { background:#e2ede8;color:#213330; }
        .tn-mobile-overlay { position:fixed;inset:0;background:rgba(18,40,35,.55);backdrop-filter:blur(4px);z-index:90;opacity:0;pointer-events:none;transition:opacity .25s ease; }
        body.tn-mobile-menu-open { overflow:hidden !important; }
        body.tn-mobile-menu-open .tn-mobile-overlay { opacity:1;pointer-events:auto; }
        .staff-sidebar__brand-row, .tn-sidebar-brand-row { display:flex;align-items:center;justify-content:space-between;width:100%; }

        @media(max-width:1080px){
            .tn-navlabel, .tn-brandword, .tn-logout-label { display:none !important; }
            .tn-side { width:80px !important;align-items:center; }
            .tn-navitem { justify-content:center; }
            .tn-sidebar-brand { width:56px;padding-left:0 !important;padding-right:0 !important;justify-content:flex-start;overflow:hidden; }
            .tn-brand-logo { width:150px;max-width:none;height:44px;object-position:left center; }
            .staff-sidebar__brand { border-bottom:0;margin-bottom:10px; }
            .staff-sidebar__logout { width:100%; }
            .staff-sidebar nav { width:100%; }
            .staff-sidebar .tn-navitem { padding-right:0;padding-left:0; }
        }
        @media(max-width:768px){
            body { overflow-x:hidden; }
            .tn-mobile-toggle { display:inline-flex !important; }
            .tn-mobile-close { display:inline-flex !important; }
            .tn-side { position:fixed !important;top:0 !important;left:0 !important;bottom:0 !important;right:auto !important;width:285px !important;max-width:86vw !important;height:100vh !important;height:100dvh !important;z-index:100 !important;transform:translateX(-105%);transition:transform .28s cubic-bezier(.22,1,.36,1);box-shadow:0 0 40px rgba(0,0,0,.25);background:#fff !important;padding:20px 16px !important;flex-direction:column !important;align-items:stretch !important;overflow-y:auto !important; }
            body.tn-mobile-menu-open .tn-side { transform:translateX(0) !important; }
            .tn-side nav { display:flex !important;flex-direction:column !important;width:100% !important;gap:4px !important;flex:1 !important;overflow-y:auto !important; }
            .tn-side .tn-navitem { display:flex !important;align-items:center !important;gap:12px !important;padding:12px 14px !important;font-size:14px !important;border-radius:13px !important;text-align:left !important;width:100% !important;min-width:0 !important;justify-content:flex-start !important; }
            .tn-side .tn-navitem svg { width:20px !important;height:20px !important;flex:0 0 20px !important; }
            .tn-navlabel, .tn-logout-label { display:inline-block !important;font-size:14px !important;opacity:1 !important;max-width:none !important; }
            .staff-sidebar__section { display:block !important;margin:18px 12px 6px !important;color:#96a49f;font-size:10px;font-weight:800;letter-spacing:.1em;text-transform:uppercase; }
            .staff-sidebar__logout, form[action="/logout"] { display:block !important;margin-top:auto !important;padding-top:14px !important;border-top:1px solid #edf2f0 !important;width:100% !important; }
            .tn-main { width:100%;padding-bottom:0 !important; }
            .tn-topbar { min-height:68px !important;padding:12px 16px !important;gap:12px !important;position:sticky !important;top:0 !important;z-index:8 !important;background:rgba(243,247,245,.95) !important;backdrop-filter:blur(14px) !important; }
            .tn-pagetitle { font-size:19px !important;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:180px; }
            .tn-welcome-text { display:none !important; }
            .tn-profile { display:none !important; }
            #portal-content { padding:14px 16px 40px !important; }
            .tn-toast-stack { right:12px;left:12px;bottom:24px; }
            .tn-toast { min-width:0;max-width:none;width:100%; }
            .tn-notification-panel { position:fixed !important;top:72px !important;right:14px !important;left:14px !important;width:auto !important;max-width:420px !important;z-index:120 !important; }
            .staff-dashboard__intro { margin-bottom:18px;flex-direction:column;gap:8px; }
            .staff-dashboard__date { display:none; }
            .staff-metrics { grid-template-columns:1fr 1fr !important;gap:11px !important;margin-bottom:16px !important; }
            .staff-metric { min-height:132px;padding:16px;border-radius:17px; }
            .staff-metric__icon { width:32px;height:32px;border-radius:10px; }
            .staff-metric__value { font-size:28px !important; }
            .staff-metric__context { font-size:10.5px !important; }
            .staff-panel { border-radius:19px; }
            .staff-panel__header { padding:19px; }
            .staff-activity__list { padding:0 19px 8px; }
            .staff-activity__item { grid-template-columns:10px minmax(0,1fr);gap:10px;padding:13px 0; }
            .staff-activity__content { display:block; }
            .staff-activity__content strong,.staff-activity__content span { display:block; }
            .staff-activity__content span { margin-top:2px; }
            .staff-activity__item time { grid-column:2;justify-content:flex-start;margin-top:-7px; }
            .staff-command { display:block;padding:20px; }
            .staff-command .staff-panel__header { padding:0 0 18px; }
            .staff-command__actions { padding-top:16px; }
            .staff-attention { margin-top:18px; }
            .tn-client-toolbar { align-items:stretch !important;flex-direction:column !important; }
            .tn-client-toolbar-actions { width:100% !important;flex-direction:column !important;align-items:stretch !important;gap:10px !important; }
            .tn-client-toolbar-actions form { width:100% !important;flex-direction:row !important;flex-wrap:wrap !important;gap:8px !important; }
            .tn-client-toolbar-actions input[type="search"] { width:100% !important;min-width:0 !important;flex:1 1 auto !important; }
            .tn-client-toolbar-actions select { flex:0 0 108px !important; }
            .tn-client-toolbar-actions a, .tn-create-client-button { width:100% !important;display:flex !important;justify-content:center !important;text-align:center !important;box-sizing:border-box !important;padding:13px 18px !important;border-radius:14px !important; }
            [data-entity-fields], [data-deadline-row], form[action="/staff/deadlines/create"] { grid-template-columns:1fr !important; }
            .tn-client-list { padding:0 !important;background:transparent !important;box-shadow:none !important; }
            #clientsTable, #clientsTable tbody { display:block !important;width:100% !important; }
            #clientsTable thead { display:none !important; }
            #clientsTable .client-row { display:flex !important;flex-direction:column !important;background:#fff !important;border-radius:18px !important;margin-bottom:12px !important;padding:16px !important;border:1px solid rgba(44,80,71,.08) !important;box-shadow:0 4px 16px -10px rgba(16,54,45,.2) !important;position:relative !important; }
            #clientsTable .client-row td { display:flex !important;flex-direction:column !important;align-items:flex-start !important;padding:6px 0 !important;border:0 !important;font-size:13.5px !important;width:100% !important;box-sizing:border-box !important; }
            #clientsTable .client-row td::before { content:attr(data-label);display:block;color:#8a9a94;font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;margin-bottom:3px; }
            #clientsTable .client-row td:first-child, #clientsTable .client-row td:nth-child(2) { display:inline-flex !important;flex-direction:row !important;align-items:center !important;gap:8px !important;padding:0 !important;margin-bottom:6px !important; }
            #clientsTable .client-row td:first-child::before, #clientsTable .client-row td:nth-child(2)::before { display:none !important; }
            #clientsTable .client-row td:last-child { margin-top:10px !important;padding-top:10px !important;border-top:1px solid #f0f4f2 !important;width:100% !important; }
            #clientsTable .client-row td:last-child > div { display:flex !important;width:100% !important;gap:10px !important; }
            #clientsTable .client-row td:last-child a { flex:1 !important;justify-content:center !important;padding:10px 14px !important;font-size:13.5px !important;border-radius:12px !important; }
            #clientsTable .client-row td:last-child button { width:42px !important;height:42px !important;flex:0 0 42px !important;border-radius:12px !important; }
            #clientsTable .tn-client-empty { display:block;background:#fff;border-radius:20px; }
            #clientsTable .tn-client-empty td { display:block !important;width:100%; }
        }
        @media(max-width:480px) {
            .staff-metrics { grid-template-columns:1fr !important; }
            .staff-metric__label { font-size:12px; }
            .staff-metric__context { max-width:none; }
            .tn-pagetitle { max-width:140px !important; }
        }
        @media(prefers-reduced-motion:reduce) { .staff-sidebar .tn-navitem,.tn-notification-button,.staff-panel__link svg,.staff-search button,.staff-action,.staff-action__arrow,.tn-side,.tn-mobile-overlay { transition:none !important; } }
    </style>
</head>
<body data-portal-authenticated="<?= \Application\Core\Session::get('user_id') ? '1' : '0' ?>" data-csrf-token="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">

<div id="tnPageProgress" class="tn-page-progress" aria-hidden="true"></div>
<div id="tnToastStack" class="tn-toast-stack" aria-live="polite" aria-atomic="true"></div>
<div id="tnMobileOverlay" class="tn-mobile-overlay" aria-hidden="true"></div>

<?php
$userId = \Application\Core\Session::get('user_id');
$role = \Application\Core\Session::get('role');
?>

<?php if (!$userId): ?>
    <?= $content ?>
<?php else: ?>
    <div style="display:flex;min-height:100vh;background:#eef4f1">
        <?php if ($role === 'staff'): ?>
            <?php require __DIR__ . '/sidebar-staff.php'; ?>
        <?php else: ?>
            <?php require __DIR__ . '/sidebar-client.php'; ?>
        <?php endif; ?>

        <div class="tn-main" style="flex:1;min-width:0;display:flex;flex-direction:column">
            <?php require __DIR__ . '/header.php'; ?>
            
            <main id="portal-content" tabindex="-1" style="padding:8px 32px 48px;flex:1">
                <?php if ($flashSuccess = \Application\Core\Session::getFlash('success')): ?>
                    <div style="max-width:1120px;margin-bottom:16px;padding:14px 18px;background:#e2f3ea;color:#3f9d6d;border-radius:16px;font-weight:600;font-size:14px">
                        <?= htmlspecialchars($flashSuccess) ?>
                    </div>
                <?php endif; ?>
                <?php if ($flashError = \Application\Core\Session::getFlash('error')): ?>
                    <div style="max-width:1120px;margin-bottom:16px;padding:14px 18px;background:#fdecdc;color:#e07d24;border-radius:16px;font-weight:600;font-size:14px">
                        <?= htmlspecialchars($flashError) ?>
                    </div>
                <?php endif; ?>

                <?= $content ?>
            </main>
        </div>
    </div>
<?php endif; ?>

<script src="/assets/js/app.js?v=<?= (int)@filemtime(dirname(__DIR__, 3) . '/public/assets/js/app.js') ?>" defer></script>
</body>
</html>
