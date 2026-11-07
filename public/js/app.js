/**
 * R2Uploader — Common Utilities
 */
(function () {
    'use strict';

    // Client-side translation helper
    window.__t = function (key, params = {}) {
        const dict = window.R2_I18N || {};
        let text = dict[key] || key;
        Object.keys(params).forEach(k => {
            text = text.replaceAll(':' + k, String(params[k]));
        });
        return text;
    };

    // Theme toggle
    window.toggleTheme = function () {
        const current = document.documentElement.getAttribute('data-theme');
        const next = current === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
    };

    // Toast notification
    window.showToast = function (message, type = 'success') {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;

        // Add icon based on type
        const icon = type === 'success'
            ? '<svg style="width:1.25rem;height:1.25rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>'
            : '<svg style="width:1.25rem;height:1.25rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>';

        toast.innerHTML = icon + ' ';
        const span = document.createElement('span');
        span.textContent = message;
        toast.appendChild(span);
        container.appendChild(toast);

        // Show popover container if not already open
        if (container.showPopover && !container.matches(':popover-open')) {
            container.showPopover();
        }

        // Trigger reflow for animation
        toast.offsetHeight;
        toast.classList.add('show');

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
                // Hide popover if empty
                if (container.children.length === 0 && container.hidePopover) {
                    container.hidePopover();
                }
            }, 400); // match CSS transition duration
        }, 3000);
    };

    // Copy to clipboard
    window.copyToClipboard = function (text) {
        navigator.clipboard.writeText(text).then(() => {
            showToast(window.__t('msg_link_copied'));
        }).catch(err => {
            console.error('Failed to copy: ', err);
        });
    };

    /**
     * Formats UTC date/time string or timestamp into the user's browser local time.
     * Option 2: 'DD MMM YYYY, HH:mm:ss' (24-hour time), e.g. '17 Sep 2026, 21:45:16'.
     * Handles SQLite 'YYYY-MM-DD HH:MM:SS' strings, ISO strings, timestamps, etc.
     */
    window.formatLocalDateTime = function (dateInput, options) {
        if (!dateInput || dateInput === '-') return '-';
        let str = String(dateInput).trim();
        if (!str || str === '-') return '-';

        // Treat SQLite UTC format "YYYY-MM-DD HH:MM:SS" as ISO UTC with trailing Z
        if (/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}$/.test(str)) {
            str = str.replace(' ', 'T') + 'Z';
        } else if (/^\d{4}-\d{2}-\d{2}$/.test(str)) {
            // Plain date string: parse as local date
            str = str + 'T00:00:00';
        }

        const d = new Date(str);
        if (isNaN(d.getTime())) return dateInput;

        const defaultOptions = {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
        };

        const activeLang = document.documentElement.lang || 'id';
        const locale = activeLang === 'id' ? 'id-ID' : 'en-GB';

        const formatted = d.toLocaleString(locale, options || defaultOptions);
        // Normalize time delimiter dots to colons for standard digital clock format (e.g. 21.45.16 -> 21:45:16)
        return formatted.replace(/(\d{2})\.(\d{2})\.(\d{2})/, '$1:$2:$3');
    };

    /**
     * Automatically scan and update all elements with [data-utc] to local browser time.
     */
    window.initLocalDates = function (root = document) {
        root.querySelectorAll('[data-utc]').forEach(el => {
            const utc = el.getAttribute('data-utc');
            if (utc && utc !== '-') {
                el.title = utc + ' UTC';
                el.textContent = window.formatLocalDateTime(utc);
            }
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => window.initLocalDates());
    } else {
        window.initLocalDates();
    }
})();

