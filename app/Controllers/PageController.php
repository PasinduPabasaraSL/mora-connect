<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Post;
use App\Models\RadarPost;

final class PageController extends Controller
{
    public function about(): void
    {
        $this->view('pages/about', [
            'title' => 'About',
            'stats' => (new Post())->stats(),
            'radar' => (new RadarPost())->stats(),
            'topics' => count(Post::categories()),
        ]);
    }
}
