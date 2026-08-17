<?php

use App\Core\Auth;
use App\Models\Post;
use App\Models\User;

/**
 * Article settings, which double as the publish confirmation.
 *
 * These are ordinary form fields inside the editor's form, so publishing posts
 * them with the body in one request — there is no second endpoint to keep in
 * step, and a validation failure re-renders with every value intact.
 *
 * @var array<string, mixed> $post
 * @var bool                 $published
 * @var int                  $words
 * @var int                  $minutes
 */

$visibility = ($post['visibility'] ?? Post::VISIBILITY_PUBLIC) === Post::VISIBILITY_UNLISTED
    ? Post::VISIBILITY_UNLISTED
    : Post::VISIBILITY_PUBLIC;

$comments = (int) ($post['comments_enabled'] ?? 1) === 0 ? 0 : 1;
$cover    = trim((string) ($post['image_url'] ?? ''));
?>
<div class="sheet" id="settingsSheet" hidden>
    <?php /* Clicking the backdrop closes the dialog; it is not focusable, so
             keyboard users use Escape or the Close button instead. */ ?>
    <div class="sheet-backdrop" data-close-sheet></div>

    <div class="sheet-panel" role="dialog" aria-modal="true" aria-labelledby="settingsTitle">
        <div class="sheet-head">
            <h2 id="settingsTitle"><?= $published ? 'Article settings' : 'Ready to publish?' ?></h2>
            <button type="button" class="icon-btn" data-close-sheet aria-label="Close settings">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <path d="M6 6l12 12M18 6L6 18"></path>
                </svg>
            </button>
        </div>

        <div class="sheet-body">
            <div class="sheet-grid">
                <div class="sheet-main">
                    <div class="form-group">
                        <label for="category">Topic</label>
                        <select id="category" name="category" class="form-control">
                            <option value="">Choose a topic</option>
                            <?php /* The colours travel with the option so the preview
                                     can badge the topic without a second lookup. */ ?>
                            <?php foreach (Post::categories() as $category): ?>
                                <?php $colors = Post::colorsFor($category); ?>
                                <option value="<?= e($category) ?>"
                                        data-bg="<?= e($colors['bg']) ?>"
                                        data-ink="<?= e($colors['ink']) ?>"
                                    <?= ($post['category'] ?? '') === $category ? 'selected' : '' ?>>
                                    <?= e($category) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="hint">Sets the colour coding and which topic page lists the article.</p>
                    </div>

                    <div class="form-group">
                        <label for="image_url">Cover image URL <span class="muted">(optional)</span></label>
                        <input type="url" id="image_url" name="image_url" class="form-control"
                               value="<?= e($cover) ?>" maxlength="500"
                               placeholder="https://example.com/image.jpg">
                        <p class="hint">Leave blank and a coloured topic card is used on listings instead.</p>

                        <?php /* Filled in by the editor as the field changes, so a
                                 typo is obvious before publishing. */ ?>
                        <div class="cover-preview" id="coverPreview"<?= $cover === '' ? ' hidden' : '' ?>>
                            <img src="<?= e($cover) ?>" alt="" id="coverPreviewImg" referrerpolicy="no-referrer">
                            <span class="cover-preview-failed" id="coverPreviewFailed" hidden>
                                That address did not load an image.
                            </span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Summary <span class="muted">(optional)</span></label>
                        <textarea id="description" name="description" class="form-control sheet-textarea"
                                  maxlength="500" rows="3"
                                  placeholder="One or two sentences for cards and search results."><?= e((string) ($post['description'] ?? '')) ?></textarea>
                        <p class="hint">
                            <span id="descriptionCount">0</span>/500 &middot;
                            left blank, the opening of the article is used.
                        </p>
                    </div>

                    <div class="form-group">
                        <label for="tags">Tags <span class="muted">(up to 5, comma separated)</span></label>
                        <input type="text" id="tags" name="tags" class="form-control"
                               value="<?= e((string) ($post['tags'] ?? '')) ?>" maxlength="255"
                               placeholder="php, testing, performance">
                        <div class="tag-preview" id="tagPreview"></div>
                    </div>

                    <div class="form-group">
                        <label for="slug">Address</label>
                        <div class="slug-field">
                            <span class="slug-prefix"><?= e(url('posts/')) ?></span>
                            <input type="text" id="slug" name="slug" class="form-control"
                                   value="<?= e((string) ($post['slug'] ?? '')) ?>" maxlength="200"
                                   placeholder="follows-the-title">
                        </div>
                        <p class="hint">Left blank it follows the title. Numbers stay valid links either way.</p>
                    </div>
                </div>

                <div class="sheet-side">
                    <div class="panel">
                        <h3>This article</h3>
                        <ul class="panel-list stat-list">
                            <li><span>Status</span><span class="count"><?= $published ? 'Published' : 'Draft' ?></span></li>
                            <li><span>Author</span><span class="count"><?= e(User::nameFor(Auth::user() ?? [])) ?></span></li>
                            <li><span>Words</span><span class="count" id="statWords"><?= $words ?></span></li>
                            <li><span>Characters</span><span class="count" id="statChars">0</span></li>
                            <li><span>Reading time</span><span class="count" id="statMinutes"><?= $minutes ?> min</span></li>
                        </ul>
                    </div>

                    <div class="form-group">
                        <span class="field-label">Visibility</span>

                        <label class="choice">
                            <input type="radio" name="visibility" value="<?= Post::VISIBILITY_PUBLIC ?>"
                                <?= $visibility === Post::VISIBILITY_PUBLIC ? 'checked' : '' ?>>
                            <span><strong>Public</strong><em>Listed on the homepage, topics and search</em></span>
                        </label>

                        <label class="choice">
                            <input type="radio" name="visibility" value="<?= Post::VISIBILITY_UNLISTED ?>"
                                <?= $visibility === Post::VISIBILITY_UNLISTED ? 'checked' : '' ?>>
                            <span><strong>Unlisted</strong><em>Only people with the link can read it</em></span>
                        </label>
                    </div>

                    <div class="form-group">
                        <span class="field-label">Responses</span>

                        <label class="choice">
                            <input type="radio" name="comments_enabled" value="1" <?= $comments === 1 ? 'checked' : '' ?>>
                            <span><strong>Allow comments</strong></span>
                        </label>

                        <label class="choice">
                            <input type="radio" name="comments_enabled" value="0" <?= $comments === 0 ? 'checked' : '' ?>>
                            <span><strong>Turn comments off</strong></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="sheet-foot">
            <?php /* Warns about anything still missing before publishing;
                     filled in by the editor when the dialog opens. */ ?>
            <p class="sheet-note" id="publishNote"></p>

            <div class="sheet-actions">
                <button type="button" class="btn" data-close-sheet>Keep editing</button>
                <button type="submit" class="btn btn-primary" id="confirmPublish" value="publish">
                    <?= $published ? 'Update article' : 'Publish now' ?>
                </button>
            </div>
        </div>
    </div>
</div>
