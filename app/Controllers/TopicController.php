<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Post;

final class TopicController extends Controller
{
    public function show(string $slug): void
    {
        $posts = new Post();
        $category = Post::categoryFromSlug($slug);

        if ($category === null) {
            $this->abort(404, 'That topic does not exist.');
        }

        $this->view('topics/show', [
            'title'    => $category . ' - MoraConnect',
            'category' => $category,
            'posts'    => $posts->byCategory($category),
            'counts'   => $posts->countsByCategory(),
        ]);
    }
}
