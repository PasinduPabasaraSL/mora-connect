<?php

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Html;
use App\Core\View;
use App\Models\Post;

/**
 * The writing screen, shared by "new article" and "edit article".
 *
 * Everything the author types lives in three fields — title, subtitle and a
 * contenteditable body — while the metadata sits in a dialog so it never
 * competes with the writing. The body is mirrored into a hidden textarea on
 * submit, because a contenteditable div posts nothing on its own.
 *
 * @var array<string, mixed> $post
 * @var string               $action
 * @var list<string>         $errors
 * @var bool                 $isDraft
 */

$postId    = (int) ($post['id'] ?? 0);
$published = !$isDraft;

// A legacy plain-text article is converted on the way in, so old articles are
// editable here instead of being locked to the format they were written in.
$rawBody = (string) ($post['content'] ?? '');
$body    = ($post['content_format'] ?? 'html') === 'html'
    ? $rawBody
    : Html::fromPlainText($rawBody);

$words   = (int) ($post['word_count'] ?? 0) ?: Html::wordCount($body);
$minutes = (int) ($post['reading_minutes'] ?? 0) ?: Html::readingMinutes($words);
?>
<form class="editor" id="editorForm" method="POST" action="<?= e($action) ?>"
      data-post-id="<?= $postId ?>"
      data-status="<?= e((string) ($post['status'] ?? Post::STATUS_DRAFT)) ?>"
      data-autosave="<?= e(url('posts/autosave')) ?>"
      data-preview-base="<?= e(url('posts')) ?>">
    <?= Csrf::field() ?>

    <?php /* The editor drives these; they are hidden so the writing surface
             stays uncluttered, and the settings dialog edits them. */ ?>
    <input type="hidden" name="id" id="postId" value="<?= $postId ?: '' ?>">
    <input type="hidden" name="action" id="editorAction" value="draft">
    <textarea class="visually-hidden" name="content" id="contentField" aria-hidden="true" tabindex="-1"><?= e($body) ?></textarea>

    <div class="editor-bar">
        <div class="editor-bar-left">
            <a class="icon-btn" href="<?= e(url('profile')) ?>" aria-label="Back to your articles" title="Back to your articles">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M19 12H5M12 19l-7-7 7-7"></path>
                </svg>
            </a>

            <span class="status-pill<?= $published ? ' is-live' : '' ?>" id="statePill">
                <?= $published ? 'Published' : 'Draft' ?>
            </span>

            <?php /* Autosave feedback. The text is replaced by the editor; the
                     initial value describes a page that has not changed yet. */ ?>
            <span class="save-state" id="saveState" role="status" aria-live="polite" data-state="idle">
                <?= $postId === 0 ? 'Not saved yet' : 'All changes saved' ?>
            </span>
        </div>

        <div class="editor-bar-right">
            <span class="editor-count" id="editorCount"
                  title="Words, characters and estimated reading time">
                <span id="countWords"><?= $words ?></span> words
                &middot; <span id="countMinutes"><?= $minutes ?></span> min
            </span>

            <button type="button" class="btn btn-sm" id="previewBtn">Preview</button>

            <button type="button" class="btn btn-sm" id="settingsBtn" aria-haspopup="dialog">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-2.9 1.2 2 2 0 1 1-4 0 1.7 1.7 0 0 0-2.9-1.2l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1A1.7 1.7 0 0 0 4.6 15a2 2 0 1 1 0-4 1.7 1.7 0 0 0 1.2-2.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1A1.7 1.7 0 0 0 11 4.6a2 2 0 1 1 4 0A1.7 1.7 0 0 0 17.9 5.8l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0 1.2 2.9 2 2 0 1 1 0 4z"></path>
                </svg>
                Settings
            </button>

            <button type="submit" class="btn btn-sm" id="saveDraftBtn" value="draft">
                <?= $published ? 'Revert to draft' : 'Save draft' ?>
            </button>

            <?php /* Opens the dialog rather than posting straight away, so the
                     author confirms topic, summary and visibility first. */ ?>
            <button type="button" class="btn btn-primary btn-sm" id="publishBtn">
                <?= $published ? 'Update' : 'Publish' ?>
            </button>
        </div>
    </div>

    <?php if ($errors !== []): ?>
        <div class="editor-alerts">
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php /* Offers back work recovered from this browser after a crash or an
             accidental tab close. Hidden until the editor finds something. */ ?>
    <div class="editor-alerts recovery" id="recoveryBar" hidden>
        <div class="alert alert-recovery">
            <span id="recoveryText">Unsaved changes from your last visit were found.</span>
            <span class="recovery-actions">
                <button type="button" class="btn btn-sm" id="recoveryRestore">Restore</button>
                <button type="button" class="btn btn-sm" id="recoveryDiscard">Discard</button>
            </span>
        </div>
    </div>

    <div class="editor-canvas">
        <div class="editor-doc">
            <?php /* Textareas rather than inputs so long titles wrap instead of
                     scrolling sideways; the editor grows them to fit. */ ?>
            <textarea class="editor-title" name="title" id="titleField" rows="1"
                      maxlength="255" placeholder="Title"
                      aria-label="Article title"><?= e((string) ($post['title'] ?? '')) ?></textarea>

            <textarea class="editor-subtitle" name="subtitle" id="subtitleField" rows="1"
                      maxlength="300" placeholder="Add a subtitle that says why this is worth reading"
                      aria-label="Article subtitle"><?= e((string) ($post['subtitle'] ?? '')) ?></textarea>

            <?php 
            ?>
            <div class="article-body editor-body" id="editorBody" contenteditable="true" role="textbox"
                 aria-multiline="true" aria-label="Article body" spellcheck="true"
                 data-placeholder="Tell the story. Select text to format it, or use + to add an image, code block or quote."><?= $body ?></div>
        </div>
    </div>

    <?php View::partial('posts/_editor_tools'); ?>

    <?php View::partial('posts/_editor_settings', [
        'post'      => $post,
        'published' => $published,
        'words'     => $words,
        'minutes'   => $minutes,
    ]); ?>

    <?php /* Rendered by the editor into the same markup the article page uses,
             so the preview cannot drift from the published result. */ ?>
    <div class="preview-overlay" id="previewOverlay" hidden role="dialog" aria-modal="true"
         aria-label="Article preview">
        <div class="preview-bar">
            <span class="eyebrow">Preview</span>
            <span class="save-state">This is how readers will see it</span>
            <button type="button" class="btn btn-sm" id="previewClose">Close preview</button>
        </div>

        <div class="preview-scroll">
            <article class="preview-article" id="previewArticle">
                <div class="article-hero" id="previewCover" hidden><img src="" alt=""></div>

                <div class="article-head">
                    <span class="badge" id="previewBadge">Topic</span>
                    <h1 id="previewTitle">Untitled</h1>
                    <p class="article-standfirst" id="previewSubtitle" hidden></p>

                    <div class="byline">
                        <span class="avatar"><?= e(mb_substr((string) Auth::username(), 0, 2)) ?></span>
                        <span class="author"><?= e(Auth::username()) ?></span>
                        <span class="sep">&middot;</span>
                        <span><?= e(date('M j, Y')) ?></span>
                        <span class="sep">&middot;</span>
                        <span id="previewReading"><?= $minutes ?> min read</span>
                    </div>
                </div>

                <div class="article-body" id="previewBody"></div>

                <div class="article-tags" id="previewTags" hidden></div>
            </article>
        </div>
    </div>
</form>
