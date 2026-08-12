<?php

declare(strict_types=1);

use App\Core\Env;

/**
 * Anything secret is read from .env, which git ignores. The defaults below are
 * the XAMPP ones, so a fresh checkout still runs without a .env file — but a
 * real password or client secret must never be typed into this file, because
 * this file is committed.
 */
return [
    'name'  => 'MoraConnect',

    'debug' => Env::get('APP_DEBUG', 'true') !== 'false',

    'db' => [
        'host'    => Env::get('DB_HOST', 'localhost'),
        'name'    => Env::get('DB_NAME', 'moraconnect'),
        'user'    => Env::get('DB_USER', 'root'),
        'pass'    => Env::get('DB_PASS'),
        'charset' => 'utf8mb4',
    ],

    /**
     * Google sign-in. With no client id configured the buttons are hidden and
     * the routes refuse, so a checkout without credentials behaves as though
     * the feature does not exist rather than erroring.
     */
    'google' => [
        'client_id'     => Env::get('GOOGLE_CLIENT_ID'),
        'client_secret' => Env::get('GOOGLE_CLIENT_SECRET'),
        // Must match an Authorised redirect URI in the Google Cloud console
        'redirect_uri'  => Env::get('GOOGLE_REDIRECT_URI', 'http://localhost/Blog/google-callback.php'),
    ],

    /**
     * Column headings for the Topics menu in the header. Any category missing
     * from this map still appears, grouped under "More" — so adding a category
     * below can never make it disappear from the menu.
     */
    'topic_groups' => [
        'Build'          => ['Web Development', 'Mobile'],
        'Run and deploy' => ['DevOps', 'Systems', 'Databases'],
        'Data and safety' => ['Machine Learning', 'Security'],
    ],

    'categories' => [
        'Web Development'  => ['bg' => '#1f3bff', 'ink' => '#ffffff'],
        'DevOps'           => ['bg' => '#0e9f6e', 'ink' => '#ffffff'],
        'Machine Learning' => ['bg' => '#7c3aed', 'ink' => '#ffffff'],
        'Databases'        => ['bg' => '#f5a524', 'ink' => '#111111'],
        'Security'         => ['bg' => '#be123c', 'ink' => '#ffffff'],
        'Systems'          => ['bg' => '#334155', 'ink' => '#ffffff'],
        'Mobile'           => ['bg' => '#0891b2', 'ink' => '#ffffff'],
        'Other'            => ['bg' => '#64748b', 'ink' => '#ffffff'],
    ],
];
