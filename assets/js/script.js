/**
 * Header behaviour: mobile drawer, theme switch, dropdown menus and search.
 *
 * The initial theme is applied by a small inline script in the layout <head>,
 * before this file loads — otherwise the page would paint in light mode and
 * visibly flash before switching. This file only handles interaction.
 */
document.addEventListener('DOMContentLoaded', function () {
    setupMobileNav();
    setupThemeToggle();
    setupDropdowns();
    setupHeaderSearch();
});

function setupMobileNav() {
    var toggle = document.getElementById('navToggle');
    var drawer = document.getElementById('mobileNav');

    if (!toggle || !drawer) {
        return;
    }

    toggle.addEventListener('click', function () {
        var open = drawer.classList.toggle('open');

        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
}

function setupThemeToggle() {
    var toggle = document.getElementById('themeToggle');

    if (!toggle) {
        return;
    }

    toggle.addEventListener('click', function () {
        var next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';

        document.documentElement.dataset.theme = next;
        localStorage.setItem('theme', next);
        syncThemeToggle(toggle, next);
    });

    syncThemeToggle(toggle, document.documentElement.dataset.theme);
}

/**
 * Which icon is visible is handled in CSS; this only keeps the accessible
 * state in step with the theme.
 */
function syncThemeToggle(button, theme) {
    var dark = theme === 'dark';

    button.setAttribute('aria-pressed', dark ? 'true' : 'false');
    button.setAttribute('title', dark ? 'Switch to light theme' : 'Switch to dark theme');
}

/**
 * Every menu in the header works the same way: a button marked [data-dropdown]
 * controls the .dropdown-panel that immediately follows it. One handler covers
 * the Topics mega menu and the account menu, and any menu added later.
 */
function setupDropdowns() {
    document.querySelectorAll('[data-dropdown]').forEach(function (toggle) {
        toggle.addEventListener('click', function (event) {
            event.stopPropagation();

            var panel = toggle.nextElementSibling;
            var willOpen = !panel.classList.contains('open');

            closeDropdowns();

            if (willOpen) {
                panel.classList.add('open');
                toggle.setAttribute('aria-expanded', 'true');
            }
        });
    });

    document.addEventListener('click', closeDropdowns);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeDropdowns();
        }
    });
}

function closeDropdowns() {
    document.querySelectorAll('.dropdown-panel.open').forEach(function (panel) {
        panel.classList.remove('open');
        panel.previousElementSibling.setAttribute('aria-expanded', 'false');
    });
}

/**
 * The search icon expands into a field. A second click submits when something
 * has been typed, so the icon behaves like a search button once it is open.
 */
function setupHeaderSearch() {
    var toggle = document.getElementById('searchToggle');
    var input = document.getElementById('searchInput');

    if (!toggle || !input) {
        return;
    }

    var form = toggle.closest('.search-inline');

    toggle.addEventListener('click', function (event) {
        event.stopPropagation();

        if (!form.classList.contains('open')) {
            openSearch(form, toggle, input);
        } else if (input.value.trim() !== '') {
            form.submit();
        } else {
            closeSearch(form, toggle, input);
        }
    });

    input.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeSearch(form, toggle, input);
            toggle.focus();
        }
    });

    // Clicking away collapses the field, unless a query would be lost
    document.addEventListener('click', function (event) {
        if (!form.contains(event.target) && input.value.trim() === '') {
            closeSearch(form, toggle, input);
        }
    });
}

function openSearch(form, toggle, input) {
    form.classList.add('open');
    toggle.setAttribute('aria-expanded', 'true');
    input.removeAttribute('tabindex');
    input.focus();
}

function closeSearch(form, toggle, input) {
    form.classList.remove('open');
    toggle.setAttribute('aria-expanded', 'false');
    // Keep the collapsed field out of the tab order
    input.setAttribute('tabindex', '-1');
}
