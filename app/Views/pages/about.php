<?php

use App\Core\Auth;

/** @var array{articles: int, writers: int, topics: int} $stats */
?>
<div class="reading">
    <section class="hero" style="padding: var(--s4) 0;">
        <span class="eyebrow">About</span>
        <h1>A place for students to write things down.</h1>
        <p class="lead">
            MoraConnect is a technical publishing platform run by and for
            University of Moratuwa students. It exists because most of what we
            learn while building things never gets written down anywhere the
            next person can find it.
        </p>
    </section>

    <div class="stats" style="margin-bottom: var(--s5);">
        <div class="stat">
            <div class="num"><?= (int) $stats['articles'] ?></div>
            <div class="label">Articles published</div>
        </div>
        <div class="stat">
            <div class="num"><?= (int) $stats['writers'] ?></div>
            <div class="label">Student writers</div>
        </div>
        <div class="stat">
            <div class="num"><?= (int) $stats['topics'] ?></div>
            <div class="label">Topics covered</div>
        </div>
    </div>

    <div class="panel">
        <h3>What belongs here</h3>
        <p>
            Build logs, debugging stories, benchmarks, project write-ups and
            notes on tools you have actually used. Concrete beats general: the
            command you ran and what it printed is more useful than a summary of
            documentation.
        </p>
    </div>

    <div class="panel">
        <h3>How it works</h3>
        <p>
            Create an account with your university email, write your article,
            pick a topic and publish. You can edit or delete anything you have
            written at any time from your profile. Every article is public, so
            anyone can read it without signing in.
        </p>
    </div>

    <div class="panel">
        <h3>Built as coursework</h3>
        <p>
            The platform itself is a student project: plain PHP on an MVC
            structure with no framework, MySQL for storage and hand-written CSS.
            No build step, no dependencies.
        </p>
    </div>

    <?php if (!Auth::check()): ?>
        <div class="panel panel-cta">
            <h3>Write something</h3>
            <p>If you have fixed something awkward this semester, that is an article.</p>
            <a href="<?= e(url('register')) ?>" class="btn btn-primary">Create an account</a>
        </div>
    <?php endif; ?>
</div>
