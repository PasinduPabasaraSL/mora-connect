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

        $user = Auth::user();

        if ($user === null) {
            $this->redirect('login');
        }

        $this->view('profile/index', [
            'title' => 'Your profile',
            'user'  => $user,
            // Everything, drafts included: this is the author's own page
            'posts' => (new Post())->forUser((int) Auth::id()),
        ]);
    }
}
