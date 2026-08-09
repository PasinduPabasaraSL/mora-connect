<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Post;

final class ProfileController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();

        $this->view('profile/index', [
            'title' => Auth::username() . ' - MoraConnect',
            'posts' => (new Post())->forUser((int) Auth::id()),
        ]);
    }
}
