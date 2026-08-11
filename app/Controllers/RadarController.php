<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Post;
use App\Models\RadarPost;

final class RadarController extends Controller
{
    private const PER_PAGE = 10;

    public function index(): void
    {
        $radar  = new RadarPost();
        $filter = $this->request->input('topic');

        $category = $filter === '' ? null : Post::categoryFromSlug($filter);

        $total = $radar->total($category);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));

        $page   = min(max(1, (int) $this->request->input('page', '1')), $pages);
        $offset = ($page - 1) * self::PER_PAGE;

        $this->view('radar/index', [
            'title'    => 'Radar',
            'posts'    => $radar->page($category, self::PER_PAGE, $offset),
            'counts'   => $radar->countsByCategory(),
            'stats'    => $radar->stats(),
            'category' => $category,
            'page'     => $page,
            'pages'    => $pages,
            'total'    => $total,
            'from'     => $total === 0 ? 0 : $offset + 1,
            'to'       => min($offset + self::PER_PAGE, $total),
            'query'    => $category === null ? [] : ['topic' => Post::slugFor($category)],
        ]);
    }
}
