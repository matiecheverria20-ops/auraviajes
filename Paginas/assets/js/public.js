(function () {
    'use strict';

    const navToggle = document.getElementById('nav-toggle');
    const mainNav = document.getElementById('main-nav');
    if (navToggle && mainNav) {
        navToggle.addEventListener('click', function () {
            const open = mainNav.classList.toggle('open');
            navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        mainNav.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                mainNav.classList.remove('open');
                navToggle.setAttribute('aria-expanded', 'false');
            });
        });
    }

    document.querySelectorAll('.alert-close').forEach(function (button) {
        button.addEventListener('click', function () {
            const alert = button.closest('.alert');
            if (alert) {
                alert.remove();
            }
        });
    });

    const toTop = document.getElementById('to-top');
    if (toTop) {
        const update = function () {
            toTop.classList.toggle('visible', window.scrollY > 500);
        };
        window.addEventListener('scroll', update, { passive: true });
        update();
        toTop.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    document.querySelectorAll('[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            const message = form.getAttribute('data-confirm') || '¿Confirmás esta acción?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('.alert').forEach(function (alert) {
        window.setTimeout(function () {
            alert.style.transition = 'opacity .4s ease';
            alert.style.opacity = '0';
            window.setTimeout(function () { alert.remove(); }, 450);
        }, 7000);
    });
})();
