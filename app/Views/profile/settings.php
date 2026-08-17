<?php

use App\Core\Csrf;
use App\Core\View;
use App\Models\User;

/**
 * @var array<string, mixed> $user
 * @var list<string>         $errors
 * @var list<string>         $years
 * @var list<string>         $categories
 * @var list<string>         $interests
 * @var bool                 $hasPassword
 */

$errors = $errors ?? [];
$source = (string) ($user['avatar_source'] ?? User::AVATAR_INITIALS);

$hasUpload = trim((string) ($user['avatar_path'] ?? '')) !== '';
$hasGoogle = trim((string) ($user['google_avatar'] ?? '')) !== '';

$sections = [
    'profile'  => 'Profile',
    'picture'  => 'Profile picture',
    'academic' => 'Study',
    'links'    => 'Links',
    'security' => 'Password',
    'danger'   => 'Delete account',
];

$field = static fn (string $key): string => (string) ($user[$key] ?? '');
?>
<div class="doc-layout">
    <div class="doc-main settings">
        <header class="doc-head">
            <span class="eyebrow">Settings</span>
            <h1>Your profile</h1>
            <p class="doc-standfirst">
                This is what readers see next to everything you publish.
            </p>
        </header>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endforeach; ?>

        <?php /* One form per section. Each posts to its own action, so a mistake
                 in one never discards what was typed into another. */ ?>
        <form method="POST" action="<?= e(url('settings/profile')) ?>" class="settings-form">
            <?= Csrf::field() ?>

            <section id="profile" class="settings-section">
                <h2><?= e($sections['profile']) ?></h2>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" class="form-control" value="<?= e($field('username')) ?>"
                           disabled aria-describedby="username-hint">
                    <?php /* Disabled rather than readonly: a disabled input is not
                             submitted at all, and the field is not in the model's
                             editable list either, so there are two reasons a
                             crafted post cannot change it. */ ?>
                    <p class="hint" id="username-hint">
                        Usernames cannot be changed. Yours is the address of your public page
                        (<code>/authors/<?= e($field('username')) ?></code>), and other people's
                        links to you would break.
                    </p>
                </div>

                <div class="form-group">
                    <label for="display_name">Display name</label>
                    <input type="text" id="display_name" name="display_name" class="form-control"
                           value="<?= e($field('display_name')) ?>" maxlength="80"
                           placeholder="<?= e($field('username')) ?>" aria-describedby="display-hint">
                    <p class="hint" id="display-hint">
                        Shown on your articles instead of your username. Leave blank to use
                        <?= e($field('username')) ?>.
                    </p>
                </div>

                <div class="form-group">
                    <label for="headline">Headline</label>
                    <input type="text" id="headline" name="headline" class="form-control"
                           value="<?= e($field('headline')) ?>" maxlength="160"
                           placeholder="Final year computer science undergraduate">
                    <p class="hint">One line, under your name.</p>
                </div>

                <div class="form-group">
                    <label for="bio">Bio</label>
                    <textarea id="bio" name="bio" class="form-control sheet-textarea" rows="4"
                              maxlength="600" placeholder="What you work on, and what you write about."><?= e($field('bio')) ?></textarea>
                    <p class="hint">Up to 600 characters.</p>
                </div>
            </section>

            <section id="academic" class="settings-section">
                <h2><?= e($sections['academic']) ?></h2>

                <div class="form-row">
                    <div class="form-group">
                        <label for="programme">Degree programme</label>
                        <input type="text" id="programme" name="programme" class="form-control"
                               value="<?= e($field('programme')) ?>" maxlength="120"
                               placeholder="BSc Computer Science &amp; Engineering">
                    </div>

                    <div class="form-group">
                        <label for="study_year">Year of study</label>
                        <select id="study_year" name="study_year" class="form-control">
                            <option value="">Prefer not to say</option>
                            <?php foreach ($years as $year): ?>
                                <option value="<?= e($year) ?>"<?= $field('study_year') === $year ? ' selected' : '' ?>>
                                    <?= e($year) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="faculty">Faculty or department</label>
                    <input type="text" id="faculty" name="faculty" class="form-control"
                           value="<?= e($field('faculty')) ?>" maxlength="120"
                           placeholder="Faculty of Engineering">
                </div>
            </section>

            <section id="links" class="settings-section">
                <h2><?= e($sections['links']) ?></h2>

                <div class="form-group">
                    <label for="website">Website</label>
                    <input type="url" id="website" name="website" class="form-control"
                           value="<?= e($field('website')) ?>" maxlength="255"
                           placeholder="https://your-site.dev">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="github">GitHub username</label>
                        <input type="text" id="github" name="github" class="form-control"
                               value="<?= e($field('github')) ?>" maxlength="64" placeholder="octocat">
                        <p class="hint">The username only, not the full address.</p>
                    </div>

                    <div class="form-group">
                        <label for="linkedin">LinkedIn</label>
                        <input type="url" id="linkedin" name="linkedin" class="form-control"
                               value="<?= e($field('linkedin')) ?>" maxlength="255"
                               placeholder="https://linkedin.com/in/you">
                    </div>
                </div>

                <div class="form-group">
                    <span class="field-label">Topics you write about</span>
                    <p class="hint">Shown as chips on your profile.</p>

                    <div class="choice-grid">
                        <?php foreach ($categories as $category): ?>
                            <label class="choice">
                                <input type="checkbox" name="interests[]" value="<?= e($category) ?>"
                                       <?= in_array($category, $interests, true) ? 'checked' : '' ?>>
                                <span><?= e($category) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <div class="settings-save">
                <button type="submit" class="btn btn-primary">Save profile</button>
            </div>
        </form>

        <section id="picture" class="settings-section">
            <h2><?= e($sections['picture']) ?></h2>

            <form method="POST" action="<?= e(url('settings/avatar')) ?>" enctype="multipart/form-data">
                <?= Csrf::field() ?>

                <div class="avatar-editor">
                    <?php View::partial('partials/_avatar', [
                        'user'  => $user,
                        'size'  => 'xl',
                        'class' => 'avatar-preview',
                    ]); ?>

                    <div class="avatar-controls">
                        <div class="form-group">
                            <label for="avatar">Upload a picture</label>
                            <input type="file" id="avatar" name="avatar" class="form-control"
                                   accept="image/jpeg,image/png,image/webp,image/gif"
                                   aria-describedby="avatar-hint">
                            <p class="hint" id="avatar-hint">
                                JPEG, PNG, WebP or GIF, up to 4 MB. It will be cropped square
                                and resized to 256&times;256.
                            </p>
                        </div>

                        <?php /* Only worth showing when there is more than one
                                 picture to choose between. */ ?>
                        <?php if ($hasUpload || $hasGoogle): ?>
                            <div class="form-group">
                                <span class="field-label">Show</span>

                                <?php if ($hasUpload): ?>
                                    <label class="choice">
                                        <input type="radio" name="avatar_choice" value="<?= e(User::AVATAR_UPLOAD) ?>"
                                               <?= $source === User::AVATAR_UPLOAD ? 'checked' : '' ?>>
                                        <span>My uploaded picture</span>
                                    </label>
                                <?php endif; ?>

                                <?php if ($hasGoogle): ?>
                                    <label class="choice">
                                        <input type="radio" name="avatar_choice" value="<?= e(User::AVATAR_GOOGLE) ?>"
                                               <?= $source === User::AVATAR_GOOGLE ? 'checked' : '' ?>>
                                        <span>My Google picture</span>
                                    </label>
                                <?php endif; ?>

                                <label class="choice">
                                    <input type="radio" name="avatar_choice" value="remove"
                                           <?= $source === User::AVATAR_INITIALS ? 'checked' : '' ?>>
                                    <span>Just my initials</span>
                                </label>
                            </div>
                        <?php endif; ?>

                        <button type="submit" class="btn btn-primary">Save picture</button>
                    </div>
                </div>
            </form>
        </section>

        <section id="security" class="settings-section">
            <h2><?= $hasPassword ? 'Change password' : 'Set a password' ?></h2>

            <?php if (!$hasPassword): ?>
                <p class="hint">
                    This account signs in with Google and has no password. Setting one lets
                    you sign in either way.
                </p>
            <?php endif; ?>

            <form method="POST" action="<?= e(url('settings/password')) ?>">
                <?= Csrf::field() ?>

                <?php if ($hasPassword): ?>
                    <div class="form-group">
                        <label for="current_password">Current password</label>
                        <input type="password" id="current_password" name="current_password"
                               class="form-control" autocomplete="current-password" required>
                    </div>
                <?php endif; ?>

                <div class="form-row">
                    <div class="form-group">
                        <label for="new_password">New password</label>
                        <input type="password" id="new_password" name="new_password" class="form-control"
                               minlength="8" autocomplete="new-password" required>
                        <p class="hint">At least 8 characters.</p>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm new password</label>
                        <input type="password" id="confirm_password" name="confirm_password"
                               class="form-control" minlength="8" autocomplete="new-password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <?= $hasPassword ? 'Change password' : 'Set password' ?>
                </button>
            </form>
        </section>

        <section id="danger" class="settings-section danger-zone">
            <h2><?= e($sections['danger']) ?></h2>

            <p>
                This removes your account, your profile and every article you have
                written, published or not. It cannot be undone and nothing can be
                recovered afterwards.
            </p>

            <form method="POST" action="<?= e(url('settings/delete')) ?>"
                  data-confirm="Your account and all <?= e($field('username')) ?>'s articles will be deleted for good. There is no way back."
                  data-confirm-title="Delete your account?"
                  data-confirm-accept="Delete my account">
                <?= Csrf::field() ?>

                <div class="form-group">
                    <label for="confirm_username">Type <strong><?= e($field('username')) ?></strong> to confirm</label>
                    <input type="text" id="confirm_username" name="confirm_username" class="form-control"
                           autocomplete="off" spellcheck="false" placeholder="<?= e($field('username')) ?>"
                           data-confirm-match="<?= e($field('username')) ?>">
                </div>

                <?php /* Disabled until the username matches, so this cannot be
                         clicked through by reflex. Re-checked on the server,
                         which is what actually enforces it. */ ?>
                <button type="submit" class="btn btn-danger" data-confirm-submit disabled>
                    Delete my account
                </button>
            </form>
        </section>
    </div>

    <aside class="doc-rail">
        <nav class="panel" aria-label="Settings sections">
            <h3>On this page</h3>
            <ul class="panel-list doc-nav">
                <?php foreach ($sections as $id => $label): ?>
                    <li><a href="#<?= e($id) ?>"><span><?= e($label) ?></span></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <div class="panel">
            <h3>Your public page</h3>
            <p>This is your profile as a reader sees it.</p>
            <a class="btn btn-block" href="<?= e(url('authors/' . rawurlencode($field('username')))) ?>">
                View public profile
            </a>
        </div>
    </aside>
</div>
