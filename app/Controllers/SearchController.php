<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Post;

final class SearchController extends Controller
{
    public function index(): void
    {
        $term  = $this->request->input('q');
        $posts = new Post();

        // With no search term this page is the "Explore" listing, so it shows
        // everything rather than an empty result set.
        $this->view('search/index', [
            'title' => $term === '' ? 'Explore articles — MoraConnect' : 'Search: ' . $term,
            'term'  => $term,
            'posts' => $term === '' ? $posts->allWithAuthor() : $posts->search($term),
        ]);
    }
}
