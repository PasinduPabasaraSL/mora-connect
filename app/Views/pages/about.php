<?php

use App\Core\Auth;
use App\Core\View;

/**
 * @var array{articles: int, writers: int, topics: int}                    $stats
 * @var array{entries: int, authors: int, sources: int, updated: ?string}  $radar
 * @var int                                                               $topics
 */

$sections = [
    'publishing' => 'How publishing works',
    'radar'      => 'Where Radar comes from',
    'colophon'   => 'How the site is built',
    'faq'        => 'Questions',
];

$updated = $radar['updated'] ?? null;
?>
<div class="doc-layout">
    <?php View::partial('partials/_tech_field'); ?>

    <div class="doc-main">
        <header class="doc-head">
            <span class="eyebrow">About</span>
            <h1>A place for students to write things down.</h1>
            <p class="doc-standfirst">
                MoraConnect is a technical publishing platform run by and for
                University of Moratuwa students. It exists because most of what we
                learn while building things never gets written down anywhere the
                next person can find it.
            </p>
            <p>
                Everything here is written by students about work they actually
                did: build logs, debugging stories, benchmarks and notes on tools
                they have used in anger. Concrete beats general — the command you
                ran and what it printed is worth more than a summary of the
                documentation.
            </p>
        </header>

        <?php /* Local articles and Radar entries are never added together. The
                 local count is left out entirely until there is at least one,
                 because a prominent zero says less than nothing. */ ?>
        <div class="stats doc-stats">
            <a class="stat" href="<?= e(url('radar')) ?>">
                <div class="num"><?= (int) $radar['entries'] ?></div>
                <div class="label">Articles on Radar</div>
            </a>
            <div class="stat">
                <div class="num"><?= (int) $topics ?></div>
                <div class="label">Topics covered</div>
            </div>
            <?php if ($stats['articles'] > 0): ?>
                <div class="stat">
                    <div class="num"><?= (int) $stats['articles'] ?></div>
                    <div class="label">Published here</div>
                </div>
                <div class="stat">
                    <div class="num"><?= (int) $stats['writers'] ?></div>
                    <div class="label">Student writers</div>
                </div>
            <?php else: ?>
                <div class="stat">
                    <div class="num"><?= (int) $radar['authors'] ?></div>
                    <div class="label">Authors indexed</div>
                </div>
            <?php endif; ?>
        </div>

        <section id="publishing" class="doc-section">
            <h2><?= e($sections['publishing']) ?></h2>
            <p>
                Nothing is reviewed before it goes out and nothing is scheduled.
                You write it, you publish it, and you can take it back down
                afterwards.
            </p>

            <ol class="steps">
                <li>
                    <h3>Write</h3>
                    <p>
                        Start from a blank page with a title, a subtitle and a
                        body. Select any text to format it, or use the plus button
                        to drop in an image, a code block, a quote or an embed.
                        Your work is saved as you type.
                    </p>
                </li>
                <li>
                    <h3>Set it up</h3>
                    <p>
                        Before publishing, choose a topic and add a cover image,
                        a short description, tags and a URL slug. You decide
                        whether the article is listed publicly or reachable only
                        by its link, and whether responses are open.
                    </p>
                </li>
                <li>
                    <h3>Preview, then publish</h3>
                    <p>
                        The preview is the published page, typography and all, so
                        there are no surprises. Publishing puts the article on the
                        homepage, in its topic and in search straight away.
                    </p>
                </li>
                <li>
                    <h3>Change your mind</h3>
                    <p>
                        Edit a published article at any time from your profile.
                        Moving it back to drafts hides it without deleting
                        anything, and deleting it is permanent and asks first.
                    </p>
                </li>
            </ol>
        </section>

        <section id="radar" class="doc-section">
            <h2><?= e($sections['radar']) ?></h2>
            <p>
                Radar is the one part of the site we did not write. It is a
                curated index of engineering articles published elsewhere,
                gathered so there is something worth reading here on a quiet week.
            </p>
            <p>
                Entries are fetched from the public
                <a class="link" href="https://dev.to" rel="noopener noreferrer" target="_blank">dev.to</a>
                API by a script that is run by hand, sorted into the same topics
                as everything else, and stored with the original author, source
                and cover image. We keep a title, a summary and a link — never a
                copy of the article. Every card on
                <a class="link" href="<?= e(url('radar')) ?>">Radar</a>
                is credited to its author and leads to the original page, and
                nothing on Radar is written by a student here.
            </p>
            <?php if ($updated !== null): ?>
                <p class="doc-note">
                    Radar was last refreshed <?= e(format_date($updated)) ?>,
                    covering <?= (int) $radar['entries'] ?> articles by
                    <?= (int) $radar['authors'] ?> authors.
                </p>
            <?php endif; ?>
        </section>

        <section id="colophon" class="doc-section">
            <h2><?= e($sections['colophon']) ?></h2>
            <p>
                The platform is itself a student project, written from scratch as
                coursework. There is no framework underneath it and nothing to
                install: cloning it and importing the schema is the whole setup.
            </p>

            <dl class="spec">
                <div>
                    <dt>Language</dt>
                    <dd>PHP 8, strict types throughout, no framework</dd>
                </div>
                <div>
                    <dt>Architecture</dt>
                    <dd>Hand-written MVC behind a single front controller, with its own router and autoloader</dd>
                </div>
                <div>
                    <dt>Storage</dt>
                    <dd>MySQL over PDO, every query a prepared statement</dd>
                </div>
                <div>
                    <dt>Editor</dt>
                    <dd>Native contenteditable, with submitted markup checked against a server-side allowlist</dd>
                </div>
                <div>
                    <dt>Accounts</dt>
                    <dd>Sessions with CSRF tokens, bcrypt password hashes, and Google sign-in</dd>
                </div>
                <div>
                    <dt>Front end</dt>
                    <dd>Hand-written CSS and plain JavaScript &mdash; no build step, no bundler</dd>
                </div>
                <div>
                    <dt>Dependencies</dt>
                    <dd>None. No Composer packages, no npm packages, no CDN scripts</dd>
                </div>
            </dl>
        </section>

        <section id="faq" class="doc-section">
            <h2><?= e($sections['faq']) ?></h2>

            <div class="faq">
                <details>
                    <summary>Who can write here?</summary>
                    <p>
                        Any student with an account. There is no application and
                        no editor to get past &mdash; register, and the editor is
                        open to you.
                    </p>
                </details>

                <details>
                    <summary>Do I need a university email address?</summary>
                    <p>
                        A university address is the sensible choice, since it is
                        what identifies you as a student to other readers, but any
                        working address is accepted. You can also sign in with a
                        Google account instead of choosing a password.
                    </p>
                </details>

                <details>
                    <summary>Who owns what I write?</summary>
                    <p>
                        You do. Publishing here does not sign anything over, and
                        you are free to post the same piece on your own site.
                        Deleting an article removes it from the site completely.
                    </p>
                </details>

                <details>
                    <summary>Can I publish something unfinished?</summary>
                    <p>
                        Drafts stay private to you for as long as you like, and
                        an article can be marked unlisted so it is readable by
                        anyone you send the link to while staying off the
                        homepage, out of its topic and out of search.
                    </p>
                </details>

                <details>
                    <summary>How is my password stored?</summary>
                    <p>
                        As a bcrypt hash, never as text, so nobody with access to
                        the database can read it. If you sign in with Google, no
                        password is stored for your account at all.
                    </p>
                </details>

                <details>
                    <summary>Why do some articles link away to other sites?</summary>
                    <p>
                        Those are Radar entries, not student work. They are
                        credited to their original authors and always open on the
                        site that published them &mdash; see
                        <a class="link" href="#radar">Where Radar comes from</a>.
                    </p>
                </details>
            </div>
        </section>

    </div>

    <?php /* Sticky rail: the same shape as an article page, so About does not
             read as though it came from a different site. */ ?>
    <aside class="doc-rail">
        <nav class="panel" aria-label="On this page">
            <h3>On this page</h3>
            <ul class="panel-list doc-nav">
                <?php foreach ($sections as $id => $label): ?>
                    <li><a href="#<?= e($id) ?>"><span><?= e($label) ?></span></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <?php if (Auth::check()): ?>
            <div class="panel panel-cta">
                <h3>Write something</h3>
                <p>If you fixed something awkward this semester, that is an article.</p>
                <a href="<?= e(url('posts/create')) ?>" class="btn btn-primary btn-block">Start writing</a>
            </div>
        <?php else: ?>
            <div class="panel panel-cta">
                <h3>Write something</h3>
                <p>If you fixed something awkward this semester, that is an article.</p>
                <a href="<?= e(url('register')) ?>" class="btn btn-primary btn-block">Create an account</a>
            </div>
        <?php endif; ?>
    </aside>
</div>
