<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Post;

final class PageController extends Controller
{
    public function about(): void
    {
        $this->view('pages/about', [
            'title' => 'About',
            'stats' => (new Post())->stats(),
        ]);
    }
}
