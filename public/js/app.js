/**
 * R2Uploader — Common Utilities
 */
(function () {
    'use strict';

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

        toast.innerHTML = `${icon} <span>${message}</span>`;
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
            showToast('Link disalin ke clipboard!');
        }).catch(err => {
            console.error('Gagal menyalin: ', err);
        });
    };
})();
