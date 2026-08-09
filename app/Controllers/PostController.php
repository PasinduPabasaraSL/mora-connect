<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Models\Post;

final class PostController extends Controller
{
    private Post $posts;

    public function __construct(\App\Core\Request $request)
    {
        parent::__construct($request);

        $this->posts = new Post();
    }

    public function show(string $id): void
    {
        $post = $this->posts->findWithAuthor((int) $id);

        if ($post === null) {
            $this->abort(404, 'That article does not exist.');
        }

        $this->view('posts/show', [
            'title'   => $post['title'],
            'post'    => $post,
            'isOwner' => Auth::check() && (int) $post['user_id'] === Auth::id(),
            'related' => $this->posts->related((string) $post['category'], (int) $post['id']),
            'counts'  => $this->posts->countsByCategory(),
        ]);
    }

    public function create(): void
    {
        $this->requireLogin();

        $this->view('posts/create', [
            'title' => 'Write a new article',
            'post'  => ['title' => '', 'content' => '', 'category' => '', 'image_url' => ''],
        ]);
    }

    public function store(): void
    {
        $this->requireLogin();
        $this->requireCsrf();

        $input  = $this->postInput();
        $errors = $this->validate($input);

        if ($errors === []) {
            $id = $this->posts->create(
                (int) Auth::id(),
                $input['title'],
                $input['content'],
                $input['category'],
                $this->imageOrNull($input['image_url'])
            );

            $this->redirect('posts/' . $id);
        }

        $this->view('posts/create', [
            'title'  => 'Write a new post',
            'errors' => $errors,
            'post'   => $input,
        ]);
    }

    public function edit(string $id): void
    {
        $this->requireLogin();

        $post = $this->ownedPostOrAbort((int) $id);

        $this->view('posts/edit', [
            'title' => 'Edit post',
            'post'  => $post,
        ]);
    }

    public function update(string $id): void
    {
        $this->requireLogin();
        $this->requireCsrf();

        $postId = (int) $id;
        $post   = $this->ownedPostOrAbort($postId);

        $input  = $this->postInput();
        $errors = $this->validate($input);

        if ($errors === []) {
            $this->posts->update(
                $postId,
                (int) Auth::id(),
                $input['title'],
                $input['content'],
                $input['category'],
                $this->imageOrNull($input['image_url'])
            );

            $this->redirect('posts/' . $postId);
        }

        $this->view('posts/edit', [
            'title'  => 'Edit post',
            'errors' => $errors,
            'post'   => $input + ['id' => $postId],
        ]);
    }

    public function destroy(string $id): void
    {
        $this->requireLogin();
        $this->requireCsrf();

        $postId = (int) $id;

        $this->ownedPostOrAbort($postId);

        $deleted = $this->posts->delete($postId, (int) Auth::id());

        Session::flash(
            $deleted ? 'success' : 'error',
            $deleted ? 'Article deleted.' : 'That article could not be deleted.'
        );

        $this->redirect();
    }

    /**
     * Loads a post and stops the request unless it belongs to the current user.
     *
     * @return array<string, mixed>
     */
    private function ownedPostOrAbort(int $id): array
    {
        $post = $this->posts->find($id);

        if ($post === null) {
            $this->abort(404, 'That article does not exist.');
        }

        if ((int) $post['user_id'] !== Auth::id()) {
            $this->abort(403, 'You do not have permission to change this article.');
        }

        return $post;
    }

    /**
     * @return array{title: string, content: string, category: string}
     */
    private function postInput(): array
    {
        return [
            'title'     => $this->request->input('title'),
            'content'   => $this->request->input('content'),
            'category'  => $this->request->input('category'),
            'image_url' => $this->request->input('image_url'),
        ];
    }

    /**
     * @return list<string>
     */
    private function validate(array $input): array
    {
        $errors = [];

        if ($input['title'] === '') {
            $errors[] = 'Title is required.';
        }

        if ($input['content'] === '') {
            $errors[] = 'Content cannot be empty.';
        }

        if (!Post::isValidCategory($input['category'])) {
            $errors[] = 'Please choose a topic from the list.';
        }

        if ($input['image_url'] !== '' && !$this->isSafeImageUrl($input['image_url'])) {
            $errors[] = 'The cover image must be a full http:// or https:// address.';
        }

        return $errors;
    }

    /**
     * Cover images are rendered into an src attribute, so only ordinary web
     * addresses are accepted — not data: or other schemes.
     */
    private function isSafeImageUrl(string $url): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        return in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }

    /**
     * Empty form field means "no image", which is NULL in the database.
     */
    private function imageOrNull(string $url): ?string
    {
        return $url === '' ? null : $url;
    }
}
