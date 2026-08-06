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

    'categories' => [
        'Technology'   => 'linear-gradient(135deg, #2e4d44, #45655b)',
        'Philosophy'   => 'linear-gradient(135deg, #44474a, #74777a)',
        'Psychology'   => 'linear-gradient(135deg, #12181b, #42484b)',
        'Data Science' => 'linear-gradient(135deg, #1a1c1c, #595f63)',
        'Architecture' => 'linear-gradient(135deg, #2e4d44, #12181b)',
        'Other'        => 'linear-gradient(135deg, #74777a, #c4c7c9)',
    ],
];
