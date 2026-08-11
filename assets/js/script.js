document.addEventListener('DOMContentLoaded', function () {
    setupMobileNav();
    setupThemeToggle();
    setupDropdowns();
    setupHeaderSearch();
    setupBrokenCovers();
    setupConfirmDialog();
    window.MoraHighlight(document);
});

function setupConfirmDialog() {
    var dialog = document.getElementById('confirmDialog');

    if (!dialog) {
        return;
    }

    var accept = document.getElementById('confirmAccept');
    var cancel = document.getElementById('confirmCancel');
    var pending = null;
    var lastFocus = null;

    document.addEventListener('submit', function (event) {
        var form = event.target;

        if (!form.dataset || !form.dataset.confirm || form.dataset.confirmed === 'yes') {
            return;
        }

        event.preventDefault();
        open(form);
    });

    function open(form) {
        pending = form;
        lastFocus = document.activeElement;

        document.getElementById('confirmTitle').textContent = form.dataset.confirmTitle || 'Are you sure?';
        document.getElementById('confirmBody').textContent = form.dataset.confirm;
        accept.textContent = form.dataset.confirmAccept || 'Delete';

        dialog.hidden = false;
        document.body.classList.add('has-sheet');

        cancel.focus();
    }

    function close() {
        dialog.hidden = true;
        document.body.classList.remove('has-sheet');
        pending = null;

        if (lastFocus) {
            lastFocus.focus();
        }
    }

    accept.addEventListener('click', function () {
        if (!pending) {
            return;
        }

        var form = pending;

        form.dataset.confirmed = 'yes';
        close();
        form.submit();
    });

    dialog.addEventListener('click', function (event) {
        if (event.target.closest('[data-confirm-cancel]')) {
            close();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (dialog.hidden) {
            return;
        }

        if (event.key === 'Escape') {
            close();

            return;
        }

        if (event.key === 'Tab') {
            event.preventDefault();
            (document.activeElement === accept ? cancel : accept).focus();
        }
    });
}

function setupBrokenCovers() {
    document.querySelectorAll('.cover img').forEach(function (img) {
        img.addEventListener('error', function () {
            showCoverFallback(img);
        });

        // An image that already failed before this ran fires no further events
        if (img.complete && img.naturalWidth === 0) {
            showCoverFallback(img);
        }
    });
}

function showCoverFallback(img) {
    var cover = img.parentElement;

    if (!cover || cover.classList.contains('cover-fallback')) {
        return;
    }

    var label = document.createElement('span');

    label.textContent = cover.dataset.topic || '';
    cover.classList.add('cover-fallback');
    cover.appendChild(label);
    img.remove();
}

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

/**
 * Colours code blocks in articles and in the editor's preview.
 *
 * This is deliberately a rough pass rather than a real parser: one language
 * agnostic tokeniser for strings, comments, numbers and common keywords covers
 * the languages students write about without shipping a highlighting library.
 * Tokens are found in the raw text and each piece is escaped as it is written
 * out, so the markup added here can only ever be the spans below.
 */
window.MoraHighlight = (function () {
    var KEYWORDS = [
        'abstract', 'and', 'as', 'async', 'await', 'begin', 'bool', 'boolean', 'break', 'case',
        'catch', 'char', 'class', 'const', 'constructor', 'continue', 'def', 'default', 'delete',
        'do', 'double', 'echo', 'elif', 'else', 'elseif', 'end', 'enum', 'except', 'export',
        'extends', 'false', 'final', 'finally', 'float', 'fn', 'for', 'foreach', 'from', 'func',
        'function', 'global', 'go', 'if', 'implements', 'import', 'in', 'include', 'instanceof',
        'int', 'interface', 'is', 'lambda', 'let', 'match', 'module', 'namespace', 'new', 'nil',
        'none', 'not', 'null', 'or', 'package', 'pass', 'print', 'private', 'protected', 'public',
        'raise', 'readonly', 'record', 'require', 'return', 'select', 'self', 'static', 'string',
        'struct', 'super', 'switch', 'template', 'then', 'this', 'throw', 'trait', 'true', 'try',
        'type', 'typeof', 'union', 'unset', 'use', 'var', 'void', 'when', 'where', 'while',
        'with', 'yield'
    ];

    // Languages where # starts a comment. In CSS it starts a colour or an id,
    // so guessing wrong there would grey out half the block.
    var HASH = ['php', 'py', 'python', 'rb', 'ruby', 'sh', 'bash', 'shell', 'zsh', 'yml', 'yaml',
                'toml', 'ini', 'conf', 'dockerfile', 'makefile', 'perl', 'r'];

    var DASH = ['sql', 'mysql', 'postgres', 'postgresql', 'sqlite', 'plsql', 'hs', 'haskell', 'lua'];

    function pattern(language) {
        var comments = ['/\\*[\\s\\S]*?(?:\\*/|$)', '//[^\\n]*'];

        if (HASH.indexOf(language) !== -1) {
            comments.push('#[^\\n]*');
        }

        if (DASH.indexOf(language) !== -1) {
            comments.push('--[^\\n]*');
        }

        return new RegExp(
            '(' + comments.join('|') + ')'
            + '|(\'(?:\\\\.|[^\'\\\\\\n])*\'|"(?:\\\\.|[^"\\\\\\n])*"|`(?:\\\\.|[^`\\\\])*`)'
            + '|(\\b0[xb][\\da-fA-F]+\\b|\\b\\d+(?:\\.\\d+)?\\b)'
            + '|(\\b(?:' + KEYWORDS.join('|') + ')\\b)',
            'g'
        );
    }

    function escape(value) {
        return value
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function highlight(code, language) {
        var regex = pattern(language);
        var out = '';
        var last = 0;
        var match;

        while ((match = regex.exec(code)) !== null) {
            out += escape(code.slice(last, match.index));

            var token = match[1] ? 'com' : match[2] ? 'str' : match[3] ? 'num' : 'key';

            out += '<span class="tok-' + token + '">' + escape(match[0]) + '</span>';
            last = regex.lastIndex;

            // A zero-length match would spin forever
            if (match[0] === '') {
                regex.lastIndex += 1;
            }
        }

        return out + escape(code.slice(last));
    }

    return function (root) {
        (root || document).querySelectorAll('pre > code').forEach(function (code) {
            if (code.dataset.highlighted === 'yes') {
                return;
            }

            var language = (code.parentNode.dataset.language || '').toLowerCase();

            code.dataset.highlighted = 'yes';
            code.innerHTML = highlight(code.textContent, language);
        });
    };
}());
