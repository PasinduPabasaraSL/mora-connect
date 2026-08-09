<?php

declare(strict_types=1);

return [
    'name'  => 'MoraConnect',

    'debug' => true,

    'db' => [
        'host'    => 'localhost',
        'name'    => 'moraconnect',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
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
