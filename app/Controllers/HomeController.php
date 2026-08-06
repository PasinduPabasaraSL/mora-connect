<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Post;

final class HomeController extends Controller
{
    public function index(): void
    {
        $this->view('home/index', [
            'title' => 'MoraConnect — Student Publishing',
            'posts' => (new Post())->allWithAuthor(),
        ]);
    }
}
