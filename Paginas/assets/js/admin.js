(function () {
    'use strict';

    const sidebar = document.getElementById('admin-sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const toggle = document.getElementById('sidebar-toggle');
    const close = document.getElementById('sidebar-close');

    function openSidebar() {
        if (sidebar) sidebar.classList.add('open');
        if (overlay) overlay.classList.add('open');
    }

    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('open');
    }

    if (toggle) toggle.addEventListener('click', openSidebar);
    if (close) close.addEventListener('click', closeSidebar);
    if (overlay) overlay.addEventListener('click', closeSidebar);

    const userButton = document.getElementById('user-button');
    const userDropdown = document.getElementById('user-dropdown');
    if (userButton && userDropdown) {
        userButton.addEventListener('click', function (event) {
            event.stopPropagation();
            const open = userDropdown.classList.toggle('open');
            userButton.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        document.addEventListener('click', function (event) {
            if (!userDropdown.contains(event.target) && event.target !== userButton) {
                userDropdown.classList.remove('open');
                userButton.setAttribute('aria-expanded', 'false');
            }
        });
    }

    document.querySelectorAll('.alert-close').forEach(function (button) {
        button.addEventListener('click', function () {
            const alert = button.closest('.alert');
            if (alert) alert.remove();
        });
    });

    document.querySelectorAll('[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            const message = form.getAttribute('data-confirm') || '¿Confirmás esta acción?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('a[data-confirm-link]').forEach(function (link) {
        link.addEventListener('click', function (event) {
            const message = link.getAttribute('data-confirm-link') || '¿Confirmás esta acción?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('input[type="file"][data-preview]').forEach(function (input) {
        const target = document.getElementById(input.getAttribute('data-preview'));
        input.addEventListener('change', function () {
            if (!target || !input.files || !input.files[0]) return;
            const url = URL.createObjectURL(input.files[0]);
            target.innerHTML = '<img src="' + url + '" alt="Vista previa">';
        });
    });

    document.querySelectorAll('.alert').forEach(function (alert) {
        window.setTimeout(function () {
            alert.style.transition = 'opacity .4s ease';
            alert.style.opacity = '0';
            window.setTimeout(function () { alert.remove(); }, 450);
        }, 8000);
    });

    const printButtons = document.querySelectorAll('[data-print]');
    printButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            window.print();
        });
    });

    document.querySelectorAll('.tabs').forEach(function (tabs) {
        tabs.querySelectorAll('.tab-link').forEach(function (tab) {
            tab.addEventListener('click', function (event) {
                const targetId = tab.getAttribute('data-tab-target');
                if (!targetId) return;
                event.preventDefault();
                tabs.querySelectorAll('.tab-link').forEach(function (t) { t.classList.remove('active'); });
                tab.classList.add('active');
                const scope = tabs.parentElement;
                scope.querySelectorAll('[data-tab-panel]').forEach(function (panel) {
                    panel.classList.toggle('hidden', panel.getAttribute('data-tab-panel') !== targetId);
                });
            });
        });
    });
})();
