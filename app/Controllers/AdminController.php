<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AdminAuth;
use App\Core\Config;
use App\Core\Controller;
use App\Core\Session;
use App\Models\Post;
use App\Models\RadarPost;
use App\Models\Stats;

/**
 * The admin panel: one signed-in view of the whole portal.
 *
 * Everything here reads. Nothing in this controller writes to the database, so
 * a mistake in it cannot damage an article, an account or the Radar table — the
 * panel answers questions and leaves changing things to the pages that own it.
 *
 * /admin serves the sign-in form and the dashboard from the same address, so
 * there is no separate login page to find. Sections live below it and all
 * redirect back here when the session is gone.
 */
final class AdminController extends Controller
{
    private const LAYOUT = 'admin';

    public function index(): void
    {
        $this->available();

        // Same URL, two answers, depending only on whether you are signed in
        if (!AdminAuth::check()) {
            $this->loginForm();
        }

        $stats = new Stats();

        $this->panel('overview', 'Overview', [
            'figures'      => $stats->overview(),
            'signups'      => $stats->signupsByMonth(),
            'publications' => $stats->publicationsByMonth(),
            'recent'       => $stats->recentArticles(),
            'members'      => $stats->newestMembers(),
            'radar'        => (new RadarPost())->stats(),
        ]);
    }

    public function login(): void
    {
        $this->available();
        $this->requireCsrf();

        $waiting = AdminAuth::lockedFor();

        if ($waiting > 0) {
            Session::flash('error', sprintf(
                'Too many failed attempts. Try again in %d minute%s.',
                (int) ceil($waiting / 60),
                $waiting > 60 ? 's' : ''
            ));

            $this->redirect('admin');
        }

        // raw() rather than input(), which trims: a password is whatever was
        // typed, spaces and all.
        $ok = AdminAuth::attempt(
            $this->request->input('username'),
            $this->request->raw('password')
        );

        if (!$ok) {
            // Deliberately vague. Which half was wrong is not the operator's
            // problem to diagnose from a login screen.
            Session::flash('error', 'Those credentials were not accepted.');

            $this->redirect('admin');
        }

        Session::flash('success', 'Signed in to the admin panel.');
        $this->redirect('admin');
    }

    public function logout(): void
    {
        $this->requireCsrf();

        AdminAuth::logout();
        Session::flash('success', 'Signed out of the admin panel.');

        $this->redirect('admin');
    }

    public function content(): void
    {
        $this->guard();

        $stats = new Stats();

        $this->panel('content', 'Content', [
            'breakdown' => $stats->contentBreakdown(),
            'missing'   => $stats->missingMetadata(),
            'topics'    => $stats->byTopic(),
            'stale'     => $stats->staleDrafts(),
        ]);
    }

    public function writers(): void
    {
        $this->guard();

        $stats = new Stats();

        $this->panel('writers', 'Writers', [
            'writers' => $stats->writers(),
            'figures' => $stats->overview(),
        ]);
    }

    public function radar(): void
    {
        $this->guard();

        $radar = new RadarPost();
        $stats = new Stats();

        $this->panel('radar', 'Radar', [
            'summary'    => $radar->stats(),
            'byCategory' => $radar->countsByCategory(),
            'span'       => $stats->radarSpan(),
            'sources'    => $stats->radarSources(),
            'authors'    => $stats->radarAuthors(),
        ]);
    }

    public function community(): void
    {
        $this->guard();

        $stats = new Stats();

        $this->panel('community', 'Community', [
            'figures'      => $stats->overview(),
            'faculties'    => $stats->byFaculty(),
            'years'        => $stats->byStudyYear(),
            'signIn'       => $stats->signInMethods(),
            'completeness' => $stats->profileCompleteness(),
            'interests'    => $stats->statedInterests(),
            'signups'      => $stats->signupsByMonth(),
        ]);
    }

    /**
     * With no admin configured the panel behaves as though it was never built,
     * rather than advertising a door with no lock on it.
     */
    private function available(): void
    {
        if (!AdminAuth::configured()) {
            $this->abort(404);
        }
    }

    /**
     * Sections send you back to /admin rather than showing their own form, so
     * there is only ever one place to sign in.
     */
    private function guard(): void
    {
        $this->available();

        if (!AdminAuth::check()) {
            Session::flash('error', 'Please sign in to open the admin panel.');

            $this->redirect('admin');
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function panel(string $section, string $heading, array $data): void
    {
        $this->view('admin/' . $section, $data + [
            'title'   => $heading . ' - Admin - ' . Config::get('name'),
            'heading' => $heading,
            'section' => $section,
            'topics'  => Post::categories(),
        ], self::LAYOUT);
    }

    private function loginForm(): never
    {
        $this->view('admin/login', [
            'title'  => 'Admin sign-in',
            'chrome' => false,
            'locked' => AdminAuth::lockedFor(),
        ], self::LAYOUT);

        exit;
    }
}
