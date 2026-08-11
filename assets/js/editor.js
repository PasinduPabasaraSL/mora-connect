/**
 * Article editor.
 *
 * The writing surface is a contenteditable element driven by document
 * execCommand. That API is officially deprecated but is the only formatting
 * engine every browser ships, and using it keeps this file dependency free —
 * the alternative is importing an editor framework for one page.
 *
 * Everything typed is mirrored into a hidden textarea before submit, and the
 * markup is sanitised again on the server, so nothing here is trusted.
 */
(function () {
    'use strict';

    var form = document.getElementById('editorForm');

    if (!form) {
        return;
    }

    var body        = document.getElementById('editorBody');
    var titleField  = document.getElementById('titleField');
    var subField    = document.getElementById('subtitleField');
    var contentHold = document.getElementById('contentField');
    var actionField = document.getElementById('editorAction');
    var postIdField = document.getElementById('postId');

    var formatBar  = document.getElementById('formatBar');
    var insertTool = document.getElementById('insertTool');
    var insertMenu = document.getElementById('insertMenu');
    var insertToggle = document.getElementById('insertToggle');
    var imageTool  = document.getElementById('imageTool');
    var askBox     = document.getElementById('askBox');

    var saveState = document.getElementById('saveState');
    var sheet     = document.getElementById('settingsSheet');
    var overlay   = document.getElementById('previewOverlay');

    /** Blocks allowed at the top level of an article. */
    var BLOCKS = 'P,H2,H3,H4,UL,OL,BLOCKQUOTE,PRE,FIGURE,HR';

    var AUTOSAVE_IDLE = 1500;
    var BACKUP_KEY = 'moraconnect:draft:' + (form.dataset.postId !== '0' ? form.dataset.postId : 'new');

    var dirty = false;
    var submitting = false;
    var saveTimer = null;
    var backupTimer = null;
    var selectedImage = null;
    var savedRange = null;

    /* ------------------------------------------------------------------
     * Setup
     * ------------------------------------------------------------------ */

    // Floating tools are moved to <body> so their absolute coordinates are
    // document coordinates. Left inside the form, a positioned ancestor such as
    // the sticky bar would shift every measurement.
    [formatBar, insertTool, imageTool, askBox].forEach(function (node) {
        document.body.appendChild(node);
    });

    try {
        // Enter should produce a paragraph, and formatting should be tags rather
        // than inline styles, because the sanitiser keeps tags and drops styles.
        document.execCommand('defaultParagraphSeparator', false, 'p');
        document.execCommand('styleWithCSS', false, false);
    } catch (err) {
        /* Older browsers reject the setup calls; formatting still works. */
    }

    ensureParagraph();
    autoGrow(titleField);
    autoGrow(subField);
    updateCounts();
    restoreBackup();

    /* ------------------------------------------------------------------
     * Title and subtitle
     * ------------------------------------------------------------------ */

    [titleField, subField].forEach(function (field) {
        field.addEventListener('input', function () {
            autoGrow(field);
            markDirty();
        });

        // Enter in the heading fields moves on rather than adding a line, which
        // is what every publishing tool does with a title.
        field.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter') {
                return;
            }

            event.preventDefault();
            (field === titleField ? subField : body).focus();
        });
    });

    function autoGrow(field) {
        field.style.height = 'auto';
        field.style.height = field.scrollHeight + 'px';
    }

    /* ------------------------------------------------------------------
     * Body input
     * ------------------------------------------------------------------ */

    body.addEventListener('input', function () {
        ensureParagraph();
        updateCounts();
        markDirty();
        positionInsertTool();
    });

    body.addEventListener('keydown', function (event) {
        var pre = closest(getNode(), 'PRE');

        if (pre) {
            handleCodeKeys(event, pre);

            return;
        }

        if (event.key === 'Enter' && !event.shiftKey) {
            // Leaving a quote on an empty line returns to normal paragraphs,
            // so a quote does not swallow the rest of the article.
            var quote = closest(getNode(), 'BLOCKQUOTE');

            if (quote && currentBlockText() === '') {
                event.preventDefault();
                leaveQuote(quote);
            }

            return;
        }

        if (!(event.metaKey || event.ctrlKey)) {
            return;
        }

        var key = event.key.toLowerCase();

        if (key === 'k') {
            event.preventDefault();
            askForLink();
        } else if (key === 's') {
            // Ctrl+S is muscle memory; save rather than opening a file dialog
            event.preventDefault();
            saveNow();
        } else if (key === 'e') {
            event.preventDefault();
            toggleInlineCode();
        }
    });

    // Pasting carries markup from wherever it came from. Only the text is kept,
    // so a paste cannot smuggle styles or scripts into the body.
    body.addEventListener('paste', function (event) {
        var text = (event.clipboardData || window.clipboardData).getData('text/plain');

        event.preventDefault();

        if (closest(getNode(), 'PRE')) {
            insertText(text);
        } else {
            // Blank lines become separate paragraphs, matching how the text read
            // in its original source.
            text.split(/\n{2,}/).forEach(function (block, index) {
                if (index > 0) {
                    document.execCommand('insertParagraph');
                }

                insertText(block.replace(/\n/g, ' '));
            });
        }

        markDirty();
        updateCounts();
    });

    body.addEventListener('click', function (event) {
        var image = event.target.closest ? event.target.closest('img') : null;

        if (image && body.contains(image)) {
            selectImage(image);
        } else {
            deselectImage();
            anchorCaret();
        }

        positionInsertTool();
    });

    body.addEventListener('focus', function () {
        anchorCaret();
        positionInsertTool();
    });

    // Last line of defence: a keystroke must never start a block-less text node
    body.addEventListener('beforeinput', anchorCaret);

    /**
     * A contenteditable with no element inside puts typing in a bare text node,
     * which then cannot be turned into a heading or a list. One paragraph is
     * always kept so every command has a block to work on.
     *
     * The paragraph holds a <br> rather than nothing: an empty block has no line
     * box, so the caret cannot be placed inside it and would land on the
     * container instead.
     */
    function ensureParagraph() {
        if (body.querySelector(BLOCKS)) {
            return;
        }

        var text = body.textContent;

        body.innerHTML = '<p><br></p>';

        if (text.trim() !== '') {
            body.firstChild.textContent = text;
        }
    }

    /**
     * Pulls the caret inside a block when it has landed on the container, which
     * happens when the reader clicks past the end of the article. Text typed at
     * the container level belongs to no block, and every formatting command
     * needs one to act on.
     */
    function anchorCaret() {
        if (document.activeElement !== body || currentBlock() !== null) {
            return;
        }

        ensureParagraph();

        var blocks = body.querySelectorAll(BLOCKS);
        var last = blocks[blocks.length - 1];

        if (!last) {
            return;
        }

        var range = document.createRange();

        range.selectNodeContents(last);
        range.collapse(false);
        select(range);
    }

    /* ------------------------------------------------------------------
     * Selection toolbar
     * ------------------------------------------------------------------ */

    document.addEventListener('selectionchange', function () {
        // Deferred so the browser has finished updating the selection
        window.requestAnimationFrame(function () {
            // Kept on every move, because clicking a toolbar button takes focus
            // out of the body and the caret has to be put back afterwards.
            saveRange();
            updateFormatBar();
        });
    });

    body.addEventListener('keyup', updateFormatBar);
    body.addEventListener('mouseup', updateFormatBar);
    window.addEventListener('scroll', function () {
        if (!formatBar.hidden) {
            updateFormatBar();
        }

        positionInsertTool();
    }, { passive: true });

    function updateFormatBar() {
        var selection = window.getSelection();

        if (!askBox.hidden) {
            return;
        }

        if (!selection || selection.isCollapsed || selection.rangeCount === 0) {
            formatBar.hidden = true;

            return;
        }

        var range = selection.getRangeAt(0);

        if (!body.contains(range.commonAncestorContainer) || range.toString().trim() === '') {
            formatBar.hidden = true;

            return;
        }

        formatBar.hidden = false;
        place(formatBar, range.getBoundingClientRect());
        reflectActiveStates();
    }

    /** Shows which commands apply to the current selection. */
    function reflectActiveStates() {
        var node = getNode();

        formatBar.querySelectorAll('[data-command]').forEach(function (button) {
            var command = button.dataset.command;
            var active = false;

            if (command === 'inlineCode') {
                active = !!closest(node, 'CODE');
            } else if (command !== 'link' && command !== 'unlink') {
                try {
                    active = document.queryCommandState(command);
                } catch (err) {
                    active = false;
                }
            }

            button.classList.toggle('is-active', active);
        });

        formatBar.querySelectorAll('[data-block]').forEach(function (button) {
            var block = closest(node, button.dataset.block.toUpperCase());

            button.classList.toggle('is-active', !!block);
        });

        document.getElementById('unlinkBtn').hidden = !closest(node, 'A');
    }

    // mousedown, not click: the default action of pressing a button would blur
    // the body and collapse the selection before the command could run.
    formatBar.addEventListener('mousedown', function (event) {
        var button = event.target.closest('button');

        if (!button) {
            return;
        }

        event.preventDefault();

        if (button.dataset.block) {
            applyBlock(button.dataset.block);
        } else {
            runCommand(button.dataset.command);
        }
    });

    function runCommand(command) {
        if (command === 'inlineCode') {
            toggleInlineCode();
        } else if (command === 'link') {
            askForLink();
        } else if (command === 'unlink') {
            document.execCommand('unlink');
        } else {
            document.execCommand(command, false, null);
        }

        markDirty();
        reflectActiveStates();
    }

    /** Toggles a block: pressing H2 inside an h2 returns it to a paragraph. */
    function applyBlock(tag) {
        var current = closest(getNode(), tag.toUpperCase());

        document.execCommand('formatBlock', false, '<' + (current ? 'p' : tag) + '>');
        markDirty();
        updateCounts();
        reflectActiveStates();
    }

    function toggleInlineCode() {
        var existing = closest(getNode(), 'CODE');

        if (existing && !closest(existing, 'PRE')) {
            unwrap(existing);
            markDirty();

            return;
        }

        var selection = window.getSelection();

        if (!selection || selection.isCollapsed) {
            return;
        }

        // Inline code holds characters, not formatting, so the selection is
        // reduced to its text before being wrapped.
        document.execCommand('insertHTML', false, '<code>' + escapeHtml(selection.toString()) + '</code>');
        markDirty();
    }

    function unwrap(element) {
        var parent = element.parentNode;

        while (element.firstChild) {
            parent.insertBefore(element.firstChild, element);
        }

        parent.removeChild(element);
    }

    /* ------------------------------------------------------------------
     * Insert menu
     * ------------------------------------------------------------------ */

    insertToggle.addEventListener('click', function (event) {
        event.preventDefault();
        setInsertMenu(insertMenu.hidden);
    });

    insertMenu.addEventListener('click', function (event) {
        var button = event.target.closest('[data-insert]');

        if (!button) {
            return;
        }

        setInsertMenu(false);
        insertBlock(button.dataset.insert);
    });

    function setInsertMenu(open) {
        insertMenu.hidden = !open;
        insertToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        insertTool.classList.toggle('is-open', open);
    }

    function insertBlock(kind) {
        body.focus();
        restoreRange();

        if (kind === 'divider') {
            document.execCommand('insertHTML', false, '<hr><p><br></p>');
            afterInsert();
        } else if (kind === 'quote') {
            applyBlock('blockquote');
        } else if (kind === 'code') {
            ask({
                label: 'Language for highlighting',
                hint: 'Optional — php, js, sql, python, bash. Leave blank for plain code.',
                confirm: 'Add code block',
                value: '',
                onDone: function (language) {
                    var clean = language.toLowerCase().replace(/[^a-z0-9+#-]/g, '').slice(0, 20);

                    document.execCommand(
                        'insertHTML',
                        false,
                        '<pre data-fresh="1"' + (clean ? ' data-language="' + clean + '"' : '') +
                        '><code>\u200b</code></pre><p><br></p>'
                    );

                    // A pre is seeded with a zero-width space so the caret has
                    // somewhere to land inside it; it is stripped before saving.
                    caretInto(claimFresh('pre[data-fresh]'), 'code');
                    afterInsert();
                }
            });
        } else if (kind === 'image') {
            ask({
                label: 'Image address',
                hint: 'A direct https:// link to a picture.',
                confirm: 'Insert image',
                value: '',
                validate: isHttpUrl,
                onDone: function (url) {
                    document.execCommand(
                        'insertHTML',
                        false,
                        '<figure data-fresh="1" data-align="center"><img src="' + escapeHtml(url) + '" alt="">' +
                        '<figcaption></figcaption></figure><p><br></p>'
                    );

                    // Straight into the caption, which is the next thing an
                    // author wants to write and easy to forget otherwise.
                    caretInto(claimFresh('figure[data-fresh]'), 'figcaption');
                    afterInsert();
                }
            });
        } else if (kind === 'embed') {
            ask({
                label: 'Link to embed',
                hint: 'YouTube, Vimeo, CodePen, CodeSandbox, Spotify or SoundCloud.',
                confirm: 'Embed',
                value: '',
                validate: isHttpUrl,
                onDone: function (url) {
                    document.execCommand('insertHTML', false, buildEmbed(url) + '<p><br></p>');
                    afterInsert();
                }
            });
        }
    }

    function afterInsert() {
        markDirty();
        updateCounts();
        positionInsertTool();
    }

    /**
     * Turns a watch URL into the matching player URL. Anything unrecognised
     * becomes a plain link card, because the server only allows iframes from
     * hosts it knows and would otherwise drop the block entirely.
     */
    function buildEmbed(url) {
        var youtube = url.match(/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([\w-]{6,20})/);

        if (youtube) {
            return frame('https://www.youtube.com/embed/' + youtube[1], 'YouTube video');
        }

        var vimeo = url.match(/vimeo\.com\/(?:video\/)?(\d{6,12})/);

        if (vimeo) {
            return frame('https://player.vimeo.com/video/' + vimeo[1], 'Vimeo video');
        }

        var pen = url.match(/codepen\.io\/([\w-]+)\/(?:pen|details|full)\/([\w-]+)/);

        if (pen) {
            return frame('https://codepen.io/' + pen[1] + '/embed/' + pen[2], 'CodePen embed');
        }

        var spotify = url.match(/open\.spotify\.com\/(track|album|playlist|episode|show)\/([\w-]+)/);

        if (spotify) {
            return frame('https://open.spotify.com/embed/' + spotify[1] + '/' + spotify[2], 'Spotify player');
        }

        if (/codesandbox\.io\/(s|p)\//.test(url)) {
            return frame(url.replace('/s/', '/embed/'), 'CodeSandbox embed');
        }

        if (/soundcloud\.com\//.test(url)) {
            return frame('https://w.soundcloud.com/player/?url=' + encodeURIComponent(url), 'SoundCloud player');
        }

        return '<figure class="embed-link"><a href="' + escapeHtml(url) + '">' + escapeHtml(url) + '</a>' +
               '<figcaption></figcaption></figure>';
    }

    function frame(src, title) {
        return '<figure class="embed"><iframe src="' + escapeHtml(src) + '" title="' + escapeHtml(title) +
               '" loading="lazy" allowfullscreen></iframe><figcaption></figcaption></figure>';
    }

    /* ------------------------------------------------------------------
     * Code blocks
     * ------------------------------------------------------------------ */

    /**
     * Inside a code block Enter means a new line, not a new paragraph, and
     * Escape or Ctrl+Enter is the way out — otherwise a code block at the end
     * of an article would be impossible to escape.
     */
    function handleCodeKeys(event, pre) {
        if (event.key === 'Escape' || (event.key === 'Enter' && (event.metaKey || event.ctrlKey))) {
            event.preventDefault();
            exitBlock(pre);

            return;
        }

        if (event.key === 'Enter') {
            event.preventDefault();
            insertText('\n');
            markDirty();

            return;
        }

        if (event.key === 'Tab') {
            event.preventDefault();
            insertText('    ');
            markDirty();
        }
    }

    /**
     * Finds the block just inserted and clears its marker.
     *
     * execCommand gives no reference to what it inserted, and searching by tag
     * would find the article's first code block rather than the new one, so the
     * markup is tagged on the way in and identified on the way out.
     */
    function claimFresh(selector) {
        var node = body.querySelector(selector);

        if (node) {
            node.removeAttribute('data-fresh');
        }

        return node;
    }

    /** Puts the caret at the end of a child of the given block. */
    function caretInto(block, childSelector) {
        var target = block && block.querySelector(childSelector);

        if (!target) {
            return;
        }

        var range = document.createRange();

        range.selectNodeContents(target);
        range.collapse(false);
        select(range);
    }

    /**
     * Enter on a blank line ends the quote. A quote that is blank altogether
     * becomes a paragraph again, since the author clearly did not want one;
     * otherwise the blank line is dropped and writing continues below.
     */
    function leaveQuote(quote) {
        var line = currentBlock();

        if (line === quote || quote.textContent.replace(/\u200b/g, '').trim() === '') {
            var paragraph = document.createElement('p');

            paragraph.appendChild(document.createElement('br'));
            quote.parentNode.replaceChild(paragraph, quote);
            caretTo(paragraph);
            markDirty();

            return;
        }

        if (line) {
            line.remove();
        }

        exitBlock(quote);
    }

    /** Moves the caret to a fresh paragraph after the given block. */
    function exitBlock(block) {
        var paragraph = document.createElement('p');

        paragraph.appendChild(document.createElement('br'));
        block.parentNode.insertBefore(paragraph, block.nextSibling);
        caretTo(paragraph);
        markDirty();
    }

    function caretTo(node) {
        var range = document.createRange();

        range.setStart(node, 0);
        range.collapse(true);
        select(range);
    }

    /* ------------------------------------------------------------------
     * Images
     * ------------------------------------------------------------------ */

    function selectImage(image) {
        deselectImage();
        selectedImage = image;
        image.classList.add('is-selected');

        var figure = closest(image, 'FIGURE');
        var align = (figure && figure.dataset.align) || 'center';
        var width = parseInt(image.getAttribute('width') || '100', 10);

        imageTool.hidden = false;
        imageTool.querySelectorAll('[data-align]').forEach(function (button) {
            button.classList.toggle('is-active', button.dataset.align === align);
        });

        document.getElementById('imageWidth').value = width;
        document.getElementById('imageWidthValue').textContent = width + '%';

        place(imageTool, image.getBoundingClientRect(), 'below');
    }

    function deselectImage() {
        if (selectedImage) {
            selectedImage.classList.remove('is-selected');
        }

        selectedImage = null;
        imageTool.hidden = true;
    }

    imageTool.addEventListener('mousedown', function (event) {
        var button = event.target.closest('button');

        if (!button || !selectedImage) {
            return;
        }

        event.preventDefault();

        if (button.dataset.align) {
            var figure = closest(selectedImage, 'FIGURE');

            if (figure) {
                figure.dataset.align = button.dataset.align;
            }

            selectImage(selectedImage);
        } else if (button.dataset.image === 'alt') {
            askForAlt();
        } else if (button.dataset.image === 'remove') {
            (closest(selectedImage, 'FIGURE') || selectedImage).remove();
            deselectImage();
            updateCounts();
        }

        markDirty();
    });

    document.getElementById('imageWidth').addEventListener('input', function () {
        if (!selectedImage) {
            return;
        }

        var width = parseInt(this.value, 10);

        selectedImage.setAttribute('width', String(width));
        document.getElementById('imageWidthValue').textContent = width + '%';
        markDirty();
    });

    function askForAlt() {
        var image = selectedImage;

        ask({
            label: 'Describe this image',
            hint: 'Read aloud by screen readers and shown if the picture fails to load.',
            confirm: 'Save description',
            value: image.getAttribute('alt') || '',
            allowEmpty: true,
            onDone: function (text) {
                image.setAttribute('alt', text);
                markDirty();
            }
        });
    }

    /* ------------------------------------------------------------------
     * Insert tool placement
     * ------------------------------------------------------------------ */

    /**
     * Parks the + button beside the block the caret is in, in the margin when
     * the screen is wide enough to have one. CSS pins it to a corner on small
     * screens, where there is no margin to sit in.
     */
    function positionInsertTool() {
        var block = currentBlock();

        if (!block || document.activeElement !== body) {
            if (insertMenu.hidden) {
                insertTool.hidden = true;
            }

            return;
        }

        insertTool.hidden = false;

        if (window.matchMedia('(max-width: 860px)').matches) {
            insertTool.classList.add('is-docked');
            insertTool.style.top = '';
            insertTool.style.left = '';

            return;
        }

        insertTool.classList.remove('is-docked');

        var rect = block.getBoundingClientRect();

        insertTool.style.top = (rect.top + window.scrollY + (rect.height - 32) / 2) + 'px';
        insertTool.style.left = (rect.left + window.scrollX - 46) + 'px';
    }

    function currentBlock() {
        var node = getNode();

        if (!node || !body.contains(node)) {
            return null;
        }

        while (node && node !== body) {
            if (node.nodeType === 1 && BLOCKS.indexOf(node.nodeName) !== -1) {
                return node;
            }

            node = node.parentNode;
        }

        return null;
    }

    function currentBlockText() {
        var block = currentBlock();

        return block ? block.textContent.replace(/\u200b/g, '').trim() : '';
    }

    /* ------------------------------------------------------------------
     * Shared prompt
     * ------------------------------------------------------------------ */

    var askDone = null;
    var askCheck = null;
    var askAllowEmpty = false;

    function ask(options) {
        saveRange();

        askDone = options.onDone;
        askCheck = options.validate || null;
        askAllowEmpty = options.allowEmpty === true;

        document.getElementById('askLabel').textContent = options.label;
        document.getElementById('askHint').textContent = options.hint || '';
        document.getElementById('askConfirm').textContent = options.confirm || 'Insert';

        var input = document.getElementById('askInput');

        input.value = options.value || '';
        input.classList.remove('is-invalid');

        askBox.hidden = false;
        formatBar.hidden = true;

        var anchor = selectedImage
            ? selectedImage.getBoundingClientRect()
            : (currentBlock() || body).getBoundingClientRect();

        place(askBox, anchor, 'below');
        input.focus();
        input.select();
    }

    document.getElementById('askConfirm').addEventListener('click', submitAsk);
    document.getElementById('askCancel').addEventListener('click', closeAsk);

    document.getElementById('askInput').addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            // Without this the form would submit and save a draft instead
            event.preventDefault();
            submitAsk();
        } else if (event.key === 'Escape') {
            event.preventDefault();
            closeAsk();
        }
    });

    function submitAsk() {
        var input = document.getElementById('askInput');
        var value = input.value.trim();

        if (value === '' && !askAllowEmpty) {
            closeAsk();

            return;
        }

        if (askCheck && value !== '' && !askCheck(value)) {
            input.classList.add('is-invalid');
            input.focus();

            return;
        }

        var done = askDone;

        closeAsk();

        if (done) {
            body.focus();
            restoreRange();
            done(value);
        }
    }

    function closeAsk() {
        askBox.hidden = true;
        askDone = null;
        askCheck = null;
        document.getElementById('askInput').classList.remove('is-invalid');
    }

    function askForLink() {
        var link = closest(getNode(), 'A');
        var selection = window.getSelection();
        var hadSelection = selection && !selection.isCollapsed;

        ask({
            label: link ? 'Edit link' : 'Link to',
            hint: 'A full https:// address, or /posts/12 for somewhere on this site.',
            confirm: link ? 'Update link' : 'Add link',
            value: link ? link.getAttribute('href') : '',
            validate: isLinkish,
            onDone: function (url) {
                if (link) {
                    link.setAttribute('href', url);
                } else if (hadSelection) {
                    document.execCommand('createLink', false, url);
                } else {
                    // No selection: the address itself becomes the link text
                    document.execCommand(
                        'insertHTML',
                        false,
                        '<a href="' + escapeHtml(url) + '">' + escapeHtml(url) + '</a>'
                    );
                }

                markDirty();
            }
        });
    }

    /* ------------------------------------------------------------------
     * Counts
     * ------------------------------------------------------------------ */

    function updateCounts() {
        var text = body.textContent.replace(/\u200b/g, '').replace(/\s+/g, ' ').trim();
        var words = text === '' ? 0 : text.split(' ').length;
        var minutes = Math.max(1, Math.round(words / 200));

        // An article with no words can still have an image or an embed in it,
        // and the placeholder would then sit on top of real content.
        body.classList.toggle(
            'is-empty',
            text === '' && body.querySelector('img, iframe, hr, pre') === null
        );

        setText('countWords', words);
        setText('countMinutes', minutes);
        setText('statWords', words);
        setText('statChars', text.length);
        setText('statMinutes', minutes + ' min');
    }

    /* ------------------------------------------------------------------
     * Saving
     * ------------------------------------------------------------------ */

    function markDirty() {
        dirty = true;
        setSaveState('dirty');
        scheduleBackup();

        if (form.dataset.status === 'published') {
            // A live article is never rewritten in the background; the author
            // presses Update when the change is ready to be seen.
            return;
        }

        window.clearTimeout(saveTimer);
        saveTimer = window.setTimeout(saveNow, AUTOSAVE_IDLE);
    }

    function saveNow() {
        window.clearTimeout(saveTimer);

        if (!dirty || submitting) {
            return;
        }

        if (form.dataset.status === 'published') {
            setSaveState('manual');

            return;
        }

        syncContent();
        setSaveState('saving');

        var payload = new FormData(form);

        payload.set('id', postIdField.value || '');

        window.fetch(form.dataset.autosave, {
            method: 'POST',
            body: payload,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'fetch' }
        }).then(function (response) {
            return response.json().catch(function () {
                throw new Error('Unreadable response');
            });
        }).then(function (data) {
            if (!data.saved) {
                setSaveState(data.reason === 'empty' ? 'idle' : 'manual');

                return;
            }

            adoptSavedId(data);
            dirty = false;
            setSaveState('saved', data.savedAt);
            clearBackup();
        }).catch(function () {
            // The local copy is the safety net when the request cannot land
            setSaveState('error');
            writeBackup();
        });
    }

    /**
     * The first autosave of a new article creates the row. From then on the
     * page must post to that article, and a refresh should land on it.
     */
    function adoptSavedId(data) {
        if (!data.created || !data.id) {
            return;
        }

        postIdField.value = data.id;
        form.dataset.postId = String(data.id);
        form.action = data.editUrl;

        if (window.history.replaceState) {
            window.history.replaceState({}, '', data.editUrl);
        }
    }

    function setSaveState(state, at) {
        var labels = {
            idle: postIdField.value ? 'All changes saved' : 'Not saved yet',
            dirty: 'Unsaved changes',
            saving: 'Saving\u2026',
            saved: 'Saved',
            manual: 'Unsaved changes \u2014 press Update to publish them',
            error: 'Could not reach the server \u2014 kept a copy in this browser'
        };

        saveState.dataset.state = state;
        saveState.textContent = labels[state] || labels.idle;

        if (state === 'saved' && at) {
            var when = new Date(at);

            if (!isNaN(when.getTime())) {
                saveState.textContent = 'Saved at ' + when.toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }
        }
    }

    /** Moves the body into the field that actually gets posted. */
    function syncContent() {
        contentHold.value = body.innerHTML.replace(/\u200b/g, '');
    }

    /* ------------------------------------------------------------------
     * Local backup
     * ------------------------------------------------------------------ */

    function scheduleBackup() {
        window.clearTimeout(backupTimer);
        backupTimer = window.setTimeout(writeBackup, 500);
    }

    function writeBackup() {
        try {
            window.localStorage.setItem(BACKUP_KEY, JSON.stringify({
                title: titleField.value,
                subtitle: subField.value,
                content: body.innerHTML,
                at: Date.now()
            }));
        } catch (err) {
            /* Private browsing or a full quota: the server copy still applies. */
        }
    }

    function clearBackup() {
        try {
            window.localStorage.removeItem(BACKUP_KEY);
        } catch (err) {
            /* Nothing to clean up. */
        }
    }

    /**
     * A backup only survives when a save never landed — a closed tab, a dead
     * connection, or edits to a published article. If it differs from what the
     * server sent, the author is offered it back rather than losing it.
     */
    function restoreBackup() {
        var raw;

        try {
            raw = window.localStorage.getItem(BACKUP_KEY);
        } catch (err) {
            return;
        }

        if (!raw) {
            return;
        }

        var saved;

        try {
            saved = JSON.parse(raw);
        } catch (err) {
            clearBackup();

            return;
        }

        var unchanged = saved.title === titleField.value
            && saved.subtitle === subField.value
            && saved.content === body.innerHTML;

        if (unchanged) {
            clearBackup();

            return;
        }

        var bar = document.getElementById('recoveryBar');
        var when = new Date(saved.at);

        document.getElementById('recoveryText').textContent =
            'Unsaved changes from ' + when.toLocaleString([], {
                dateStyle: 'medium',
                timeStyle: 'short'
            }) + ' were found in this browser.';

        bar.hidden = false;

        document.getElementById('recoveryRestore').addEventListener('click', function () {
            titleField.value = saved.title || '';
            subField.value = saved.subtitle || '';
            body.innerHTML = saved.content || '<p></p>';

            autoGrow(titleField);
            autoGrow(subField);
            ensureParagraph();
            updateCounts();
            bar.hidden = true;
            markDirty();
        });

        document.getElementById('recoveryDiscard').addEventListener('click', function () {
            clearBackup();
            bar.hidden = true;
        });
    }

    /* ------------------------------------------------------------------
     * Leaving the page
     * ------------------------------------------------------------------ */

    window.addEventListener('beforeunload', function (event) {
        if (!dirty || submitting) {
            return undefined;
        }

        writeBackup();

        // Browsers ignore custom text here and show their own wording
        event.preventDefault();
        event.returnValue = '';

        return '';
    });

    /* ------------------------------------------------------------------
     * Settings dialog
     * ------------------------------------------------------------------ */

    document.getElementById('settingsBtn').addEventListener('click', function () {
        openSheet();
    });

    document.getElementById('publishBtn').addEventListener('click', function () {
        openSheet();
    });

    sheet.addEventListener('click', function (event) {
        if (event.target.closest('[data-close-sheet]')) {
            closeSheet();
        }
    });

    var lastFocus = null;

    function openSheet() {
        lastFocus = document.activeElement;
        sheet.hidden = false;
        document.body.classList.add('has-sheet');
        formatBar.hidden = true;
        insertTool.hidden = true;

        refreshSheet();

        var first = sheet.querySelector('select, input, textarea');

        if (first) {
            first.focus();
        }
    }

    function closeSheet() {
        sheet.hidden = true;
        document.body.classList.remove('has-sheet');

        if (lastFocus) {
            lastFocus.focus();
        }
    }

    /** Keeps the dialog's derived pieces in step with the article. */
    function refreshSheet() {
        updateCounts();
        updateTagPreview();
        updateDescriptionCount();
        updateCoverPreview();

        var missing = [];

        if (titleField.value.trim() === '') {
            missing.push('a title');
        }

        if (body.textContent.replace(/\u200b/g, '').trim() === '') {
            missing.push('some writing');
        }

        if (document.getElementById('category').value === '') {
            missing.push('a topic');
        }

        var note = document.getElementById('publishNote');

        note.textContent = missing.length
            ? 'Still needed before publishing: ' + missing.join(', ') + '.'
            : '';
        note.classList.toggle('is-warning', missing.length > 0);
    }

    ['category', 'image_url', 'description', 'tags', 'slug'].forEach(function (id) {
        var field = document.getElementById(id);

        field.addEventListener('input', function () {
            markDirty();
            refreshSheet();
        });

        field.addEventListener('keydown', function (event) {
            // Enter in a settings field would otherwise submit the form
            if (event.key === 'Enter' && field.tagName !== 'TEXTAREA') {
                event.preventDefault();
            }
        });
    });

    sheet.querySelectorAll('input[type="radio"]').forEach(function (radio) {
        radio.addEventListener('change', markDirty);
    });

    function updateTagPreview() {
        var preview = document.getElementById('tagPreview');
        var tags = document.getElementById('tags').value
            .split(',')
            .map(function (tag) { return tag.trim(); })
            .filter(function (tag) { return tag !== ''; })
            .slice(0, 5);

        preview.textContent = '';

        tags.forEach(function (tag) {
            var chip = document.createElement('span');

            chip.className = 'tag-chip';
            chip.textContent = tag;
            preview.appendChild(chip);
        });
    }

    function updateDescriptionCount() {
        setText('descriptionCount', document.getElementById('description').value.length);
    }

    function updateCoverPreview() {
        var url = document.getElementById('image_url').value.trim();
        var wrap = document.getElementById('coverPreview');
        var image = document.getElementById('coverPreviewImg');
        var failed = document.getElementById('coverPreviewFailed');

        if (url === '') {
            wrap.hidden = true;

            return;
        }

        wrap.hidden = false;
        failed.hidden = true;
        image.hidden = false;
        image.src = url;

        image.onerror = function () {
            image.hidden = true;
            failed.hidden = false;
        };
    }

    /* ------------------------------------------------------------------
     * Preview
     * ------------------------------------------------------------------ */

    document.getElementById('previewBtn').addEventListener('click', openPreview);
    document.getElementById('previewClose').addEventListener('click', closePreview);

    function openPreview() {
        syncContent();

        var selected = document.getElementById('category').selectedOptions[0];
        var badge = document.getElementById('previewBadge');
        var title = titleField.value.trim();
        var subtitle = subField.value.trim();
        var cover = document.getElementById('image_url').value.trim();

        document.getElementById('previewTitle').textContent = title === '' ? 'Untitled' : title;

        var sub = document.getElementById('previewSubtitle');

        sub.textContent = subtitle;
        sub.hidden = subtitle === '';

        badge.textContent = selected && selected.value ? selected.value : 'No topic yet';
        badge.style.setProperty('--badge-bg', (selected && selected.dataset.bg) || 'var(--ink-faint)');
        badge.style.setProperty('--badge-ink', (selected && selected.dataset.ink) || '#ffffff');

        var coverWrap = document.getElementById('previewCover');

        coverWrap.hidden = cover === '';
        coverWrap.querySelector('img').src = cover;

        // The same class the article page uses, so the preview inherits the
        // published typography rather than a copy of it
        var preview = document.getElementById('previewBody');

        preview.innerHTML = contentHold.value;

        var tagWrap = document.getElementById('previewTags');
        var tags = document.getElementById('tags').value
            .split(',')
            .map(function (tag) { return tag.trim(); })
            .filter(function (tag) { return tag !== ''; })
            .slice(0, 5);

        tagWrap.textContent = '';
        tagWrap.hidden = tags.length === 0;

        tags.forEach(function (tag) {
            var chip = document.createElement('span');

            chip.className = 'tag-chip';
            chip.textContent = tag;
            tagWrap.appendChild(chip);
        });

        setText('previewReading', document.getElementById('countMinutes').textContent + ' min read');

        if (window.MoraHighlight) {
            window.MoraHighlight(preview);
        }

        overlay.hidden = false;
        document.body.classList.add('has-sheet');
        document.getElementById('previewClose').focus();
    }

    function closePreview() {
        overlay.hidden = true;
        document.body.classList.remove('has-sheet');
        document.getElementById('previewBtn').focus();
    }

    /* ------------------------------------------------------------------
     * Escape handling
     * ------------------------------------------------------------------ */

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') {
            return;
        }

        if (!askBox.hidden) {
            closeAsk();
        } else if (!overlay.hidden) {
            closePreview();
        } else if (!sheet.hidden) {
            closeSheet();
        } else if (!insertMenu.hidden) {
            setInsertMenu(false);
        } else if (selectedImage) {
            deselectImage();
        }
    });

    document.addEventListener('mousedown', function (event) {
        if (!insertMenu.hidden && !insertTool.contains(event.target)) {
            setInsertMenu(false);
        }

        if (!askBox.hidden && !askBox.contains(event.target)) {
            closeAsk();
        }
    });

    /* ------------------------------------------------------------------
     * Submitting
     * ------------------------------------------------------------------ */

    document.getElementById('saveDraftBtn').addEventListener('click', function () {
        actionField.value = 'draft';
    });

    document.getElementById('confirmPublish').addEventListener('click', function () {
        actionField.value = 'publish';
    });

    form.addEventListener('submit', function () {
        submitting = true;
        syncContent();
        window.clearTimeout(saveTimer);
        clearBackup();
    });

    /* ------------------------------------------------------------------
     * Helpers
     * ------------------------------------------------------------------ */

    function getNode() {
        var selection = window.getSelection();

        if (!selection || selection.rangeCount === 0) {
            return null;
        }

        var node = selection.getRangeAt(0).startContainer;

        return node.nodeType === 3 ? node.parentNode : node;
    }

    function closest(node, tagName) {
        while (node && node !== body) {
            if (node.nodeType === 1 && node.nodeName === tagName) {
                return node;
            }

            node = node.parentNode;
        }

        return null;
    }

    function saveRange() {
        var selection = window.getSelection();

        if (selection && selection.rangeCount > 0 && body.contains(selection.getRangeAt(0).startContainer)) {
            savedRange = selection.getRangeAt(0).cloneRange();
        }
    }

    function restoreRange() {
        if (savedRange) {
            select(savedRange);
        }
    }

    function select(range) {
        var selection = window.getSelection();

        selection.removeAllRanges();
        selection.addRange(range);
    }

    function insertText(text) {
        document.execCommand('insertText', false, text);
    }

    /**
     * Puts a floating panel next to a rect, kept inside the viewport so a
     * selection near an edge does not push the toolbar off screen.
     */
    function place(panel, rect, where) {
        var width = panel.offsetWidth;
        var height = panel.offsetHeight;
        var margin = 10;

        var left = rect.left + window.scrollX + (rect.width - width) / 2;
        var max = window.scrollX + document.documentElement.clientWidth - width - margin;

        left = Math.max(window.scrollX + margin, Math.min(left, max));

        var above = rect.top + window.scrollY - height - margin;
        var below = rect.bottom + window.scrollY + margin;
        var top = where === 'below' ? below : above;

        // Flip when there is no room on the preferred side
        if (where !== 'below' && above < window.scrollY + margin) {
            top = below;
        }

        panel.style.left = left + 'px';
        panel.style.top = top + 'px';
    }

    function isHttpUrl(value) {
        return /^https?:\/\/[^\s]+\.[^\s]+/i.test(value);
    }

    function isLinkish(value) {
        return isHttpUrl(value)
            || /^mailto:[^\s@]+@[^\s@]+$/i.test(value)
            || /^[/#][^\s]*$/.test(value);
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function setText(id, value) {
        var node = document.getElementById(id);

        if (node) {
            node.textContent = String(value);
        }
    }
}());
