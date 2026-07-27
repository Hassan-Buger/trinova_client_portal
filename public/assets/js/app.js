/**
 * TriNova Portal progressive enhancement layer.
 * Every link and form continues to work through normal PHP navigation when this
 * file is unavailable or a request cannot be enhanced safely.
 */
(() => {
    'use strict';

    const state = {
        navigating: false,
        messageTimer: null,
        notificationTimer: null,
    };

    const content = () => document.getElementById('portal-content');
    const progress = () => document.getElementById('tnPageProgress');

    function setBusy(isBusy) {
        state.navigating = isBusy;
        const main = document.querySelector('.tn-main');
        if (main) main.setAttribute('aria-busy', isBusy ? 'true' : 'false');
        const bar = progress();
        if (!bar) return;
        bar.classList.toggle('is-loading', isBusy);
        if (!isBusy) {
            bar.classList.add('is-complete');
            window.setTimeout(() => bar.classList.remove('is-complete'), 240);
        }
    }

    function showToast(message, type = 'success') {
        if (!message) return;
        const stack = document.getElementById('tnToastStack');
        if (!stack) return;
        const toast = document.createElement('div');
        toast.className = `tn-toast${type === 'error' ? ' is-error' : ''}`;
        toast.textContent = message;
        stack.appendChild(toast);
        window.setTimeout(() => toast.remove(), 4500);
    }

    function runInlineScripts(root) {
        root.querySelectorAll('script').forEach((oldScript) => {
            const script = document.createElement('script');
            Array.from(oldScript.attributes).forEach((attr) => script.setAttribute(attr.name, attr.value));
            script.textContent = oldScript.textContent;
            oldScript.replaceWith(script);
        });
    }

    function updateNavigation(url) {
        const currentPath = new URL(url, window.location.origin).pathname.replace(/\/$/, '');
        document.querySelectorAll('.tn-side .tn-navitem').forEach((link) => {
            if (link.getAttribute('href') === '/logout') return;
            const linkPath = new URL(link.href, window.location.origin).pathname.replace(/\/$/, '');
            const active = currentPath === linkPath || (linkPath !== '' && currentPath.startsWith(`${linkPath}/`));
            link.setAttribute('aria-current', active ? 'page' : 'false');
            link.style.background = active ? '#ffffff' : 'transparent';
            link.style.color = active ? '#0d9488' : '#61756e';
        });
    }

    function updateHeaderContext(url) {
        const path = new URL(url, window.location.origin).pathname.replace(/\/$/, '');
        const welcome = document.getElementById('portalWelcomeText');
        if (welcome) welcome.hidden = !['/staff/dashboard', '/client/dashboard'].includes(path);
    }

    function applyPage(payload, url, pushState) {
        const target = content();
        if (!target || typeof payload.html !== 'string') return false;

        stopMessagePolling();
        target.innerHTML = payload.html;
        runInlineScripts(target);

        const title = payload.title || 'TriNova Client Portal';
        document.title = title;
        const heading = document.getElementById('portal-page-title');
        if (heading) heading.textContent = title;
        updateHeaderContext(url);
        updateNavigation(url);

        if (pushState) window.history.pushState({ trinova: true }, '', url);
        if (payload.flash?.success) showToast(payload.flash.success);
        if (payload.flash?.error) showToast(payload.flash.error, 'error');

        initialisePage();
        target.focus({ preventScroll: true });
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return true;
    }

    async function navigate(url, options = {}) {
        if (state.navigating || !content()) return;
        setBusy(true);
        try {
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Trinova-Partial': '1',
                },
                credentials: 'same-origin',
            });

            if (new URL(response.url).pathname.endsWith('/login')) {
                window.location.assign(response.url);
                return;
            }

            const contentType = response.headers.get('Content-Type') || '';
            if (!contentType.includes('application/json')) {
                const html = await response.text();
                if (response.ok && html.trim().startsWith('<')) {
                    const parsed = new DOMParser().parseFromString(html, 'text/html');
                    const nextContent = parsed.getElementById('portal-content');
                    if (nextContent && applyPage({ html: nextContent.innerHTML, title: parsed.title }, response.url || window.location.href, false)) {
                        return;
                    }
                }
                throw new Error('The server returned an unexpected response. Please refresh and try again.');
            }
            const payload = await response.json();
            if (!response.ok || !payload.success || !applyPage(payload, response.url || url, options.push !== false)) {
                throw new Error(payload.message || 'Unable to load this page.');
            }
        } catch (error) {
            showToast(error.message || 'Unable to load this page.', 'error');
            if (options.fallback !== false) window.location.assign(url);
        } finally {
            setBusy(false);
        }
    }

    function shouldEnhanceLink(link, event) {
        if (!content() || document.body.dataset.portalAuthenticated !== '1') return false;
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return false;
        if (link.target || link.hasAttribute('download') || link.dataset.noAjax !== undefined) return false;
        const url = new URL(link.href, window.location.origin);
        if (url.origin !== window.location.origin || url.hash) return false;
        if (url.pathname === '/logout' || url.pathname.includes('/documents/download/')) return false;
        return true;
    }

    async function openDocumentPreview(link) {
        const documentId = Number(link.dataset.documentId || 0);
        if (documentId <= 0) {
            showToast('This document could not be opened.', 'error');
            return;
        }

        const originalText = link.textContent;
        link.setAttribute('aria-busy', 'true');
        link.style.pointerEvents = 'none';
        link.textContent = 'Checking…';
        try {
            const response = await fetch(`/documents/availability/${documentId}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                cache: 'no-store',
            });
            const payload = await response.json();
            if (!response.ok || payload.success === false) {
                throw new Error(payload.message || 'This document is currently unavailable.');
            }
            // Navigate only after the server confirms the artifact exists. This
            // avoids popup blockers and prevents an empty error tab.
            window.location.assign(link.href);
        } catch (error) {
            showToast(error.message || 'This document is currently unavailable.', 'error');
        } finally {
            link.removeAttribute('aria-busy');
            link.style.pointerEvents = '';
            link.textContent = originalText;
        }
    }

    async function submitAjaxForm(form) {
        const method = (form.method || 'GET').toUpperCase();
        if (method === 'GET') {
            const url = new URL(form.action || window.location.href, window.location.origin);
            new FormData(form).forEach((value, key) => {
                if (String(value).trim() !== '') url.searchParams.set(key, value);
                else url.searchParams.delete(key);
            });
            await navigate(url.toString());
            return;
        }

        const submitter = form.querySelector('[type="submit"]');
        const originalLabel = submitter?.textContent;
        if (submitter) {
            submitter.disabled = true;
            submitter.textContent = submitter.dataset.loadingText || 'Working…';
        }

        try {
            const response = await fetch(form.action, {
                method,
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form),
                credentials: 'same-origin',
            });
            if (new URL(response.url).pathname.endsWith('/login')) {
                window.location.assign(response.url);
                return;
            }
            const contentType = response.headers.get('Content-Type') || '';
            if (!contentType.includes('application/json')) {
                const html = await response.text();
                if (response.ok && html.trim().startsWith('<')) {
                    const parsed = new DOMParser().parseFromString(html, 'text/html');
                    const nextContent = parsed.getElementById('portal-content');
                    if (nextContent && applyPage({ html: nextContent.innerHTML, title: parsed.title }, response.url || window.location.href, false)) {
                        return;
                    }
                }
                throw new Error('The server returned an unexpected response. Please refresh and try again.');
            }
            const payload = await response.json();
            if (!response.ok || payload.success === false) throw new Error(payload.message || 'The operation could not be completed.');

            if (typeof payload.html === 'string') {
                applyPage(payload, response.url || window.location.href, false);
                return;
            }

            showToast(payload.message || 'Saved successfully.');
            if (form.matches('[data-message-form]')) {
                const textarea = form.querySelector('textarea[name="body"]');
                if (textarea) textarea.value = '';
                await pollMessages(true);
            } else if (payload.redirect) {
                await navigate(payload.redirect);
            } else if (form.dataset.ajaxRefresh !== 'false') {
                await navigate(window.location.href, { push: false, fallback: false });
            }
        } catch (error) {
            showToast(error.message || 'The operation could not be completed.', 'error');
        } finally {
            if (submitter) {
                submitter.disabled = false;
                submitter.textContent = originalLabel;
            }
        }
    }

    function messageDateLabel(value) {
        const date = new Date(value);
        const today = new Date();
        const yesterday = new Date(today);
        yesterday.setDate(today.getDate() - 1);
        const key = date.toDateString();
        if (key === today.toDateString()) return 'Today';
        if (key === yesterday.toDateString()) return 'Yesterday';
        return new Intl.DateTimeFormat(undefined, { day: 'numeric', month: 'short', year: 'numeric' }).format(date);
    }

    function appendMessage(stream, message) {
        if (stream.querySelector(`[data-message-id="${message.id}"]`)) return;
        const date = new Date(message.created_at);
        const dayKey = date.toISOString().slice(0, 10);
        if (stream.dataset.lastDay !== dayKey) {
            const separator = document.createElement('div');
            separator.className = 'tn-message-day';
            separator.textContent = messageDateLabel(message.created_at);
            stream.appendChild(separator);
            stream.dataset.lastDay = dayKey;
        }

        stream.querySelector('[data-empty-thread]')?.remove();
        const mine = message.sender_role === stream.dataset.currentRole;
        const bubble = document.createElement('article');
        bubble.className = `tn-message-bubble ${mine ? 'is-mine' : 'is-theirs'}`;
        bubble.dataset.messageId = message.id;
        bubble.dataset.messageDay = dayKey;

        const meta = document.createElement('div');
        meta.className = 'tn-message-meta';
        const time = new Intl.DateTimeFormat(undefined, { hour: '2-digit', minute: '2-digit' }).format(date);
        meta.textContent = `${message.sender_name || 'User'} · ${time}`;
        meta.title = new Intl.DateTimeFormat(undefined, { dateStyle: 'full', timeStyle: 'short' }).format(date);

        const body = document.createElement('div');
        body.className = 'tn-message-body';
        body.textContent = message.body;
        bubble.append(meta, body);
        stream.appendChild(bubble);
    }

    async function pollMessages(forceScroll = false) {
        const stream = document.querySelector('[data-message-thread]');
        if (!stream || document.hidden) return;
        const nearBottom = stream.scrollHeight - stream.scrollTop - stream.clientHeight < 100;
        const messageNodes = stream.querySelectorAll('[data-message-id]');
        const last = messageNodes.length ? messageNodes[messageNodes.length - 1].dataset.messageId : '0';
        const url = new URL(stream.dataset.feedUrl, window.location.origin);
        url.searchParams.set('after_id', last);

        try {
            const response = await fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            const payload = await response.json();
            if (!response.ok || payload.success === false) return;
            (payload.messages || []).forEach((message) => appendMessage(stream, message));
            if (forceScroll || nearBottom) stream.scrollTop = stream.scrollHeight;
        } catch (_) {
            // Polling is optional; normal form submission and navigation still work.
        }
    }

    function stopMessagePolling() {
        if (state.messageTimer) window.clearInterval(state.messageTimer);
        state.messageTimer = null;
    }

    function initialiseMessages() {
        stopMessagePolling();
        const stream = document.querySelector('[data-message-thread]');
        if (!stream) return;
        const existing = stream.querySelectorAll('[data-message-day]');
        if (existing.length) stream.dataset.lastDay = existing[existing.length - 1].dataset.messageDay;
        stream.scrollTop = stream.scrollHeight;
        state.messageTimer = window.setInterval(() => pollMessages(false), 7000);
    }

    function notificationTime(value) {
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return '';
        const seconds = Math.max(0, Math.round((Date.now() - date.getTime()) / 1000));
        if (seconds < 60) return 'Just now';
        if (seconds < 3600) return `${Math.floor(seconds / 60)} min ago`;
        if (seconds < 86400) return `${Math.floor(seconds / 3600)} hr ago`;
        return new Intl.DateTimeFormat(undefined, { day: 'numeric', month: 'short', year: 'numeric' }).format(date);
    }

    function renderNotifications(items) {
        const list = document.getElementById('portalNotificationList');
        if (!list) return;
        list.replaceChildren();
        if (!items.length) {
            const empty = document.createElement('div');
            empty.className = 'tn-notification-empty';
            empty.textContent = 'No new notifications';
            list.appendChild(empty);
            return;
        }
        items.forEach((item) => {
            const link = document.createElement('a');
            link.className = `tn-notification-item${item.is_read ? '' : ' is-unread'}`;
            link.href = item.url;
            link.dataset.notificationId = String(item.id);
            const title = document.createElement('strong');
            title.className = 'tn-notification-title';
            title.textContent = item.title || 'Portal update';
            const message = document.createElement('span');
            message.className = 'tn-notification-message';
            message.textContent = item.message;
            const time = document.createElement('time');
            time.className = 'tn-notification-time';
            time.dateTime = item.created_at;
            time.textContent = notificationTime(item.created_at);
            time.title = new Intl.DateTimeFormat(undefined, { dateStyle: 'full', timeStyle: 'short' }).format(new Date(item.created_at));
            link.append(title, message, time);
            list.appendChild(link);
        });
    }

    function setNotificationCount(count) {
        const badge = document.getElementById('portalNotificationBadge');
        if (!badge) return;
        badge.hidden = count <= 0;
        badge.textContent = count > 99 ? '99+' : String(count);
    }

    async function loadNotifications() {
        if (document.body.dataset.portalAuthenticated !== '1' || document.hidden) return;
        try {
            const response = await fetch('/notifications/feed', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                cache: 'no-store',
            });
            const payload = await response.json();
            if (!response.ok || payload.success === false) return;
            const items = Array.isArray(payload.notifications) ? payload.notifications : [];
            renderNotifications(items);
            setNotificationCount(Number(payload.unread_count ?? payload.count) || 0);
        } catch (_) {
            // Notifications are progressive enhancement; the portal remains usable without polling.
        }
    }

    async function markNotificationsRead(ids) {
        if (!Array.isArray(ids) || !ids.length) return { success: true, unreadCount: 0 };
        const token = document.body.dataset.csrfToken || '';
        const body = new URLSearchParams({ csrf_token: token });
        ids.forEach((id) => body.append('notification_ids[]', String(id)));
        const response = await fetch('/notifications/read-all', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body,
        });
        const payload = await response.json();
        if (!response.ok || payload.success === false) throw new Error(payload.message || 'Unable to mark notifications as read.');
        const unreadCount = Number(payload.unread_count ?? payload.count) || 0;
        setNotificationCount(unreadCount);
        return { success: true, unreadCount };
    }

    async function markNotificationRead(id) {
        const token = document.body.dataset.csrfToken || '';
        const response = await fetch('/notifications/read-all', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: new URLSearchParams({ csrf_token: token, notification_id: String(id) }),
        });
        return response.ok;
    }

    function initialiseNotifications() {
        const button = document.getElementById('portalNotificationButton');
        const panel = document.getElementById('portalNotificationPanel');
        if (!button || !panel) return;

        button.addEventListener('click', async () => {
            const opening = panel.hidden;
            panel.hidden = !opening;
            button.setAttribute('aria-expanded', opening ? 'true' : 'false');
            if (opening) {
                await loadNotifications();
                const unreadItems = Array.from(panel.querySelectorAll('.tn-notification-item.is-unread'));
                const unreadIds = unreadItems.map((item) => Number(item.dataset.notificationId)).filter((id) => id > 0);
                try {
                    await markNotificationsRead(unreadIds);
                    unreadItems.forEach((item) => {
                        item.classList.remove('is-unread');
                        item.classList.add('is-read');
                    });
                } catch (error) {
                    showToast(error.message || 'Unable to mark notifications as read.', 'error');
                }
            }
        });
        panel.addEventListener('click', async (event) => {
            const item = event.target.closest('.tn-notification-item');
            if (!item) return;
            event.preventDefault();
            event.stopPropagation();
            const destination = item.href;
            const id = Number(item.dataset.notificationId);
            if (id > 0 && item.classList.contains('is-unread')) {
                try { await markNotificationRead(id); } catch (_) { /* Navigate even if marking fails. */ }
            }
            panel.hidden = true;
            button.setAttribute('aria-expanded', 'false');
            navigate(destination);
        });
        document.addEventListener('click', (event) => {
            if (!panel.hidden && !event.target.closest('.tn-notification-wrap')) {
                panel.hidden = true;
                button.setAttribute('aria-expanded', 'false');
            }
        });
        loadNotifications();
        // Short polling provides near-real-time cross-session updates without
        // introducing a separate WebSocket service on shared hosting.
        state.notificationTimer = window.setInterval(loadNotifications, 5000);
    }

    function initialisePage() {
        initialiseMessages();
    }

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');
        if (link?.matches('[data-document-preview]')) {
            event.preventDefault();
            openDocumentPreview(link);
            return;
        }
        if (!link || !shouldEnhanceLink(link, event)) return;
        event.preventDefault();
        navigate(link.href);
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.matches('[data-ajax-form]')) return;
        event.preventDefault();
        submitAjaxForm(form);
    });

    let searchTimer;
    document.addEventListener('input', (event) => {
        if (!event.target.matches('[data-ajax-search]')) return;
        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(() => event.target.form?.requestSubmit(), 350);
    });

    window.addEventListener('popstate', () => navigate(window.location.href, { push: false }));
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            pollMessages(false);
            loadNotifications();
        }
    });
    window.TrinovaPortal = { navigate, showToast, refreshMessages: pollMessages };

    initialisePage();
    initialiseNotifications();
})();
