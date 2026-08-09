<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Post;
use App\Models\RadarPost;

final class RadarController extends Controller
{
    public function index(): void
    {
        $radar  = new RadarPost();
        $filter = $this->request->input('topic');

        $category = $filter === '' ? null : Post::categoryFromSlug($filter);

        $this->view('radar/index', [
            'title'    => 'Radar - technical reading from around the web',
            'posts'    => $category === null ? $radar->all() : $radar->byCategory($category),
            'counts'   => $radar->countsByCategory(),
            'stats'    => $radar->stats(),
            'category' => $category,
        ]);
    }
}
