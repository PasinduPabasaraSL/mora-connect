<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Post;
use App\Models\RadarPost;

final class HomeController extends Controller
{
    public function index(): void
    {
        $posts = new Post();

        $this->view('home/index', [
            'title'      => 'MoraConnect - Technical Writing by Moratuwa Students',
            'posts'      => $posts->allWithAuthor(),
            'counts'     => $posts->countsByCategory(),
            'stats'      => $posts->stats(),
            'radarCount' => (new RadarPost())->total(),
        ]);
    }
}
