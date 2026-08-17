<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Post;
use App\Models\User;

/**
 * A writer's public page, at /authors/{username}.
 *
 * Looked up by username rather than id, which is why the username cannot be
 * changed: these URLs end up in other people's links.
 */
final class AuthorController extends Controller
{
    public function show(string $username): void
    {
        $author = (new User())->findByUsername($username);

        if ($author === null) {
            $this->abort(404, 'There is no writer with that username.');
        }

        $posts = new Post();
        $id    = (int) $author['id'];

        $this->view('authors/show', [
            'title'  => User::nameFor($author) . ' - MoraConnect',
            'author' => $author,
            'posts'  => $posts->publishedByUser($id),
            'stats'  => $posts->statsForUser($id),
            // Lets the page offer an edit link when you are looking at your own
            'isSelf' => Auth::id() === $id,
        ]);
    }
}
