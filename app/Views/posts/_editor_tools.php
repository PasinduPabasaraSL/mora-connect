<?php

/**
 * The two floating controls that make up the editor's chrome.
 *
 * Both ship as markup rather than being built in JavaScript, matching how the
 * header's icons work: the script only moves these around and toggles classes,
 * so no button markup lives in a JS string.
 */
?>

<?php /* Appears over the current selection. mousedown is cancelled by the
         script so clicking a button never drops the selection. */ ?>
<div class="format-bar" id="formatBar" hidden role="toolbar" aria-label="Text formatting">
    <button type="button" data-command="bold" aria-label="Bold" title="Bold (Ctrl+B)">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M7 5h6.5a3.5 3.5 0 0 1 0 7H7zM7 12h7.5a3.5 3.5 0 0 1 0 7H7z"></path>
        </svg>
    </button>

    <button type="button" data-command="italic" aria-label="Italic" title="Italic (Ctrl+I)">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" aria-hidden="true">
            <path d="M15 5h-5M14 19H9M15 5l-5 14"></path>
        </svg>
    </button>

    <button type="button" data-command="underline" aria-label="Underline" title="Underline (Ctrl+U)">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" aria-hidden="true">
            <path d="M7 4v7a5 5 0 0 0 10 0V4M6 20h12"></path>
        </svg>
    </button>

    <button type="button" data-command="strikeThrough" aria-label="Strikethrough" title="Strikethrough">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" aria-hidden="true">
            <path d="M5 12h14M16 7a4 4 0 0 0-4-2.5C9.5 4.5 8 6 8 7.5c0 1.6 1.6 2.4 4 3.2M8 17a4 4 0 0 0 4 2.5c2.5 0 4-1.4 4-3"></path>
        </svg>
    </button>

    <button type="button" data-command="inlineCode" aria-label="Inline code" title="Inline code">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M9 18l-6-6 6-6M15 6l6 6-6 6"></path>
        </svg>
    </button>

    <span class="format-sep" aria-hidden="true"></span>

    <?php /* Labelled H1-H3 for the writer, but they produce h2-h4: the article
             title is the page's only h1, so starting body headings at h2 keeps
             the document outline valid. */ ?>
    <button type="button" data-block="h2" class="format-text" aria-label="Heading 1" title="Large heading">H1</button>
    <button type="button" data-block="h3" class="format-text" aria-label="Heading 2" title="Medium heading">H2</button>
    <button type="button" data-block="h4" class="format-text" aria-label="Heading 3" title="Small heading">H3</button>

    <span class="format-sep" aria-hidden="true"></span>

    <button type="button" data-block="blockquote" aria-label="Quote" title="Quote">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M7 7h4v5a4 4 0 0 1-4 4zM15 7h4v5a4 4 0 0 1-4 4z"></path>
        </svg>
    </button>

    <button type="button" data-command="insertUnorderedList" aria-label="Bullet list" title="Bullet list">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" aria-hidden="true">
            <path d="M9 6h11M9 12h11M9 18h11M4.5 6h.01M4.5 12h.01M4.5 18h.01"></path>
        </svg>
    </button>

    <button type="button" data-command="insertOrderedList" aria-label="Numbered list" title="Numbered list">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M10 6h10M10 12h10M10 18h10M4 5h1v4M4 9h2M4 13h2l-2 3h2"></path>
        </svg>
    </button>

    <button type="button" data-command="link" aria-label="Add link" title="Add link (Ctrl+K)">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M10 13a4 4 0 0 0 5.7 0l2.6-2.6A4 4 0 0 0 12.6 4.7L11.3 6"></path>
            <path d="M14 11a4 4 0 0 0-5.7 0l-2.6 2.6a4 4 0 0 0 5.7 5.7l1.3-1.3"></path>
        </svg>
    </button>

    <button type="button" data-command="unlink" aria-label="Remove link" title="Remove link" hidden id="unlinkBtn">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M17 7l3-3M4 20l3-3M10 13a4 4 0 0 0 5.7 0M14 11a4 4 0 0 0-5.7 0"></path>
            <path d="M3 3l18 18"></path>
        </svg>
    </button>
</div>

<?php /* Sits in the margin of the block the cursor is in, the way Medium's
         does, and opens the insert menu. */ ?>
<div class="insert-tool" id="insertTool" hidden>
    <button type="button" class="insert-toggle" id="insertToggle"
            aria-label="Insert an image, code block, quote, divider or embed"
            aria-expanded="false" aria-haspopup="menu" title="Insert content">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" aria-hidden="true">
            <path d="M12 5v14M5 12h14"></path>
        </svg>
    </button>

    <div class="insert-menu" id="insertMenu" hidden role="menu" aria-label="Insert">
        <button type="button" role="menuitem" data-insert="image">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="3" y="4" width="18" height="16" rx="2"></rect>
                <circle cx="8.5" cy="9.5" r="1.5"></circle>
                <path d="M21 16l-5-5-4 4-2-2-7 7"></path>
            </svg>
            <span><strong>Image</strong><em>Paste a picture URL</em></span>
        </button>

        <button type="button" role="menuitem" data-insert="code">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M9 18l-6-6 6-6M15 6l6 6-6 6"></path>
            </svg>
            <span><strong>Code block</strong><em>Highlighted snippet</em></span>
        </button>

        <button type="button" role="menuitem" data-insert="quote">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M7 7h4v5a4 4 0 0 1-4 4zM15 7h4v5a4 4 0 0 1-4 4z"></path>
            </svg>
            <span><strong>Quote</strong><em>Pull out a passage</em></span>
        </button>

        <button type="button" role="menuitem" data-insert="divider">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                <path d="M4 12h16"></path>
            </svg>
            <span><strong>Divider</strong><em>Break between sections</em></span>
        </button>

        <button type="button" role="menuitem" data-insert="embed">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M10 13a4 4 0 0 0 5.7 0l2.6-2.6A4 4 0 0 0 12.6 4.7L11.3 6"></path>
                <path d="M14 11a4 4 0 0 0-5.7 0l-2.6 2.6a4 4 0 0 0 5.7 5.7l1.3-1.3"></path>
            </svg>
            <span><strong>Embed</strong><em>YouTube, Vimeo, CodePen</em></span>
        </button>
    </div>
</div>

<?php /* One small prompt shared by every "type a value" step — link target,
         image URL, embed URL, code language and alt text — so the editor never
         falls back to a native prompt() dialog. */ ?>
<div class="ask" id="askBox" hidden role="dialog" aria-modal="true" aria-labelledby="askLabel">
    <label class="ask-label" id="askLabel" for="askInput">Paste a link</label>
    <div class="ask-row">
        <input type="text" class="form-control" id="askInput" autocomplete="off" spellcheck="false">
        <button type="button" class="btn btn-primary btn-sm" id="askConfirm">Insert</button>
        <button type="button" class="btn btn-sm" id="askCancel">Cancel</button>
    </div>
    <p class="hint" id="askHint"></p>
</div>

<?php /* Toolbar for a selected image: alignment, size, alt text, remove. */ ?>
<div class="image-tool" id="imageTool" hidden role="toolbar" aria-label="Image options">
    <button type="button" data-align="left" aria-label="Align left" title="Inset">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" aria-hidden="true">
            <rect x="3" y="6" width="10" height="12" rx="1.5"></rect><path d="M16 9h5M16 15h5"></path>
        </svg>
    </button>
    <button type="button" data-align="center" aria-label="Centre" title="Centre">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" aria-hidden="true">
            <rect x="5" y="6" width="14" height="12" rx="1.5"></rect>
        </svg>
    </button>
    <button type="button" data-align="wide" aria-label="Full width" title="Full width">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" aria-hidden="true">
            <rect x="2" y="7" width="20" height="10" rx="1.5"></rect>
        </svg>
    </button>

    <span class="format-sep" aria-hidden="true"></span>

    <label class="image-size">
        <span class="visually-hidden">Image width</span>
        <input type="range" id="imageWidth" min="30" max="100" step="5" value="100">
    </label>
    <span class="image-size-value" id="imageWidthValue">100%</span>

    <span class="format-sep" aria-hidden="true"></span>

    <button type="button" data-image="alt" class="format-text" title="Describe the image for screen readers">Alt</button>
    <button type="button" data-image="remove" aria-label="Remove image" title="Remove image">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" aria-hidden="true">
            <path d="M6 6l12 12M18 6L6 18"></path>
        </svg>
    </button>
</div>
