/* Performance Toolkit – admin sidebar toggle */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var toggle = document.getElementById('ptk-sidebar-toggle');
        var shell  = document.querySelector('.ptk-shell');

        if (! toggle || ! shell) {
            return;
        }

        toggle.addEventListener('click', function () {
            var isOpen = shell.classList.toggle('ptk-sidebar-open');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        // Close sidebar when a nav link is clicked on narrow screens
        document.querySelectorAll('.ptk-nav-link').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.innerWidth <= 960) {
                    shell.classList.remove('ptk-sidebar-open');
                    toggle.setAttribute('aria-expanded', 'false');
                }
            });
        });
    });
}());

