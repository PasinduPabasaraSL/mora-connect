<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Html;
use App\Core\Session;
use App\Models\Post;

final class PostController extends Controller
{
    /** Longest a title may be, matching the column width. */
    private const TITLE_MAX = 255;

    private Post $posts;

    public function __construct(\App\Core\Request $request)
    {
        parent::__construct($request);

        $this->posts = new Post();
    }

    /**
     * Articles are reachable by id (every existing link) or by slug. Drafts and
     * previews resolve too, but only for their author.
     */
    public function show(string $key): void
    {
        $post = $this->resolve($key);

        if ($post === null) {
            $this->abort(404, 'That article does not exist.');
        }

        $isOwner = Auth::check() && (int) $post['user_id'] === Auth::id();

        // An unpublished article is not a page anyone else can see
        if (($post['status'] ?? Post::STATUS_PUBLISHED) !== Post::STATUS_PUBLISHED && !$isOwner) {
            $this->abort(404, 'That article does not exist.');
        }

        $this->renderArticle($post, $isOwner, false);
    }

    /**
     * The author's view of an article exactly as it will publish. Shares the
     * article template with show(), so what is previewed cannot drift from
     * what readers get.
     */
    public function preview(string $id): void
    {
        $this->requireLogin();

        $post = $this->ownedPostOrAbort((int) $id);

        $this->renderArticle(
            $this->posts->findWithAuthor((int) $post['id']) ?? $post,
            true,
            true
        );
    }

    /**
     * @param array<string, mixed> $post
     */
    private function renderArticle(array $post, bool $isOwner, bool $isPreview): void
    {
        $this->view('posts/show', [
            'title'     => $post['title'],
            'post'      => $post,
            'isOwner'   => $isOwner,
            'isPreview' => $isPreview,
            'related'   => $this->posts->related((string) $post['category'], (int) $post['id']),
            'counts'    => $this->posts->countsByCategory(),
        ]);
    }

    public function create(): void
    {
        $this->requireLogin();

        $this->editor($this->blankPost(), url('posts'));
    }

    public function edit(string $id): void
    {
        $this->requireLogin();

        $post = $this->ownedPostOrAbort((int) $id);

        $this->editor($post, url('posts/' . (int) $post['id']));
    }

    /**
     * Both editor pages are the same screen; only the form target and the
     * article being loaded differ.
     *
     * @param array<string, mixed> $post
     * @param list<string>         $errors
     */
    private function editor(array $post, string $action, array $errors = []): void
    {
        $isDraft = ($post['status'] ?? Post::STATUS_DRAFT) !== Post::STATUS_PUBLISHED;

        $this->view('posts/editor', [
            'title'    => trim((string) ($post['title'] ?? '')) === ''
                ? 'New article'
                : 'Editing: ' . $post['title'],
            'post'     => $post,
            'action'   => $action,
            'errors'   => $errors,
            'isDraft'  => $isDraft,
            'scripts'  => ['js/editor.js'],
            'bodyClass' => 'is-editing',
        ]);
    }

    public function store(): void
    {
        $this->requireLogin();
        $this->requireCsrf();

        $publishing = $this->request->input('action') === 'publish';
        $input      = $this->editorInput();
        $errors     = $this->validate($input, $publishing);

        if ($errors !== []) {
            $this->editor($input, url('posts'), $errors);

            return;
        }

        $id = $this->posts->create(
            (int) Auth::id(),
            $this->fields($input, $publishing, null, null)
        );

        $this->afterSave($id, $publishing);
    }

    public function update(string $id): void
    {
        $this->requireLogin();
        $this->requireCsrf();

        $postId   = (int) $id;
        $existing = $this->ownedPostOrAbort($postId);

        $publishing = $this->request->input('action') === 'publish';
        $input      = $this->editorInput();
        $errors     = $this->validate($input, $publishing);

        if ($errors !== []) {
            $this->editor(
                $input + ['id' => $postId, 'status' => $existing['status']],
                url('posts/' . $postId),
                $errors
            );

            return;
        }

        $this->posts->update(
            $postId,
            (int) Auth::id(),
            $this->fields($input, $publishing, $postId, $existing)
        );

        $this->afterSave($postId, $publishing);
    }

    /**
     * Takes a published article back to a draft. The article keeps its
     * published_at, so re-publishing does not jump it to the top of the
     * homepage as though it were new.
     */
    public function unpublish(string $id): void
    {
        $this->requireLogin();
        $this->requireCsrf();

        $postId = (int) $id;

        $this->ownedPostOrAbort($postId);

        $this->posts->update($postId, (int) Auth::id(), ['status' => Post::STATUS_DRAFT]);

        Session::flash('success', 'Moved back to drafts. Only you can see it now.');
        $this->redirect('posts/' . $postId . '/edit');
    }

    /**
     * Background save from the editor. Answers JSON because the page stays
     * where it is.
     *
     * Only drafts are written here: silently rewriting a published article
     * while somebody is mid-sentence would change what readers see, so a live
     * article needs the explicit Update button instead.
     */
    public function autosave(): void
    {
        if (!Auth::check()) {
            $this->json(['saved' => false, 'reason' => 'auth'], 401);
        }

        if (!Csrf::verify($this->request->raw('_token'))) {
            $this->json(['saved' => false, 'reason' => 'token'], 403);
        }

        $input  = $this->editorInput();
        $postId = (int) $this->request->input('id');

        if ($postId > 0) {
            $existing = $this->posts->find($postId);

            if ($existing === null || (int) $existing['user_id'] !== Auth::id()) {
                $this->json(['saved' => false, 'reason' => 'forbidden'], 403);
            }

            if ($existing['status'] === Post::STATUS_PUBLISHED) {
                $this->json(['saved' => false, 'reason' => 'published']);
            }

            $this->posts->update($postId, (int) Auth::id(), $this->fields($input, false, $postId, $existing));

            $this->json($this->savedPayload($postId, $input));
        }

        // Nothing typed yet: no point creating an empty row on every keystroke
        if ($input['title'] === '' && Html::toText($input['content']) === '') {
            $this->json(['saved' => false, 'reason' => 'empty']);
        }

        $newId = $this->posts->create((int) Auth::id(), $this->fields($input, false, null, null));

        $this->json($this->savedPayload($newId, $input) + [
            'created' => true,
            'editUrl' => url('posts/' . $newId . '/edit'),
        ]);
    }

    /**
     * @param  array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function savedPayload(int $id, array $input): array
    {
        $words = Html::wordCount($input['content']);

        return [
            'saved'   => true,
            'id'      => $id,
            'savedAt' => date('c'),
            'words'   => $words,
            'minutes' => Html::readingMinutes($words),
        ];
    }

    private function afterSave(int $id, bool $publishing): void
    {
        Session::flash(
            'success',
            $publishing ? 'Your article is live.' : 'Draft saved. Only you can see it.'
        );

        $this->redirect($publishing ? 'posts/' . $id : 'posts/' . $id . '/edit');
    }

    public function destroy(string $id): void
    {
        $this->requireLogin();
        $this->requireCsrf();

        $postId = (int) $id;
        $post   = $this->ownedPostOrAbort($postId);
        $isDraft = ($post['status'] ?? Post::STATUS_PUBLISHED) !== Post::STATUS_PUBLISHED;

        $deleted = $this->posts->delete($postId, (int) Auth::id());

        Session::flash(
            $deleted ? 'success' : 'error',
            $deleted
                ? ($isDraft ? 'Draft deleted.' : 'Article deleted.')
                : 'That article could not be deleted.'
        );

        $this->redirect($isDraft ? 'profile' : '');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolve(string $key): ?array
    {
        return ctype_digit($key)
            ? $this->posts->findWithAuthor((int) $key)
            : $this->posts->findBySlugWithAuthor($key);
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
     * The blank article the editor opens with. Mirrors the shape of a database
     * row so the editor view never needs to care which it was handed.
     *
     * @return array<string, mixed>
     */
    private function blankPost(): array
    {
        return [
            'id'               => null,
            'title'            => '',
            'subtitle'         => '',
            'content'          => '',
            'content_format'   => 'html',
            'category'         => '',
            'slug'             => '',
            'description'      => '',
            'tags'             => '',
            'status'           => Post::STATUS_DRAFT,
            'visibility'       => Post::VISIBILITY_PUBLIC,
            'comments_enabled' => 1,
            'image_url'        => '',
            'word_count'       => 0,
            'reading_minutes'  => 0,
        ];
    }

    /**
     * Raw values straight off the form, sanitised but not yet derived.
     *
     * @return array<string, mixed>
     */
    private function editorInput(): array
    {
        return [
            'title'            => mb_substr($this->request->input('title'), 0, self::TITLE_MAX),
            'subtitle'         => mb_substr($this->request->input('subtitle'), 0, 300),
            'content'          => Html::sanitize($this->request->raw('content')),
            'content_format'   => 'html',
            'category'         => $this->request->input('category'),
            'slug'             => Post::slugify($this->request->input('slug')),
            'description'      => mb_substr($this->request->input('description'), 0, 500),
            'tags'             => implode(', ', Post::parseTags($this->request->input('tags'))),
            'image_url'        => $this->request->input('image_url'),
            'visibility'       => $this->request->input('visibility') === Post::VISIBILITY_UNLISTED
                ? Post::VISIBILITY_UNLISTED
                : Post::VISIBILITY_PUBLIC,
            'comments_enabled' => $this->request->input('comments_enabled') === '0' ? 0 : 1,
        ];
    }

    /**
     * Everything that actually gets written, including the values derived from
     * what the author typed: slug, summary, counts and publication state.
     *
     * @param  array<string, mixed>      $input
     * @param  array<string, mixed>|null $existing
     * @return array<string, mixed>
     */
    private function fields(array $input, bool $publishing, ?int $id, ?array $existing): array
    {
        $words = Html::wordCount((string) $input['content']);
        $text  = Html::toText((string) $input['content']);

        $title = $input['title'] === '' ? 'Untitled draft' : $input['title'];

        // An author-chosen slug wins; otherwise it follows the title. Either
        // way it is made unique before it reaches the unique index.
        $slug = $input['slug'] !== '' ? $input['slug'] : Post::slugify($title);

        $fields = [
            'title'            => $title,
            'subtitle'         => $this->nullIfEmpty((string) $input['subtitle']),
            'content'          => $input['content'],
            'content_format'   => 'html',
            'category'         => Post::isValidCategory((string) $input['category']) ? $input['category'] : 'Other',
            'slug'             => $this->posts->uniqueSlug($slug, $id),
            'description'      => $this->nullIfEmpty(
                $input['description'] !== '' ? (string) $input['description'] : excerpt($text, 180)
            ),
            'tags'             => $this->nullIfEmpty((string) $input['tags']),
            'visibility'       => $input['visibility'],
            'comments_enabled' => $input['comments_enabled'],
            'word_count'       => $words,
            'reading_minutes'  => Html::readingMinutes($words),
            'image_url'        => $this->imageOrNull((string) $input['image_url']),
            'status'           => $publishing ? Post::STATUS_PUBLISHED : Post::STATUS_DRAFT,
        ];

        // published_at is stamped once, the first time an article goes live
        if ($publishing && ($existing === null || $existing['published_at'] === null)) {
            $fields['published_at'] = date('Y-m-d H:i:s');
        }

        return $fields;
    }

    /**
     * Drafts are deliberately lenient: autosave has to be able to store a
     * half-finished page. The full rules only apply when publishing.
     *
     * @param  array<string, mixed> $input
     * @return list<string>
     */
    private function validate(array $input, bool $publishing): array
    {
        $errors = [];

        if ($input['image_url'] !== '' && !$this->isSafeImageUrl((string) $input['image_url'])) {
            $errors[] = 'The cover image must be a full http:// or https:// address.';
        }

        if (!$publishing) {
            return $errors;
        }

        if (trim((string) $input['title']) === '') {
            $errors[] = 'Give your article a title before publishing.';
        }

        if (Html::toText((string) $input['content']) === '') {
            $errors[] = 'Write something before publishing.';
        }

        if (!Post::isValidCategory((string) $input['category'])) {
            $errors[] = 'Choose a topic before publishing.';
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

    private function imageOrNull(string $url): ?string
    {
        return $url === '' || !$this->isSafeImageUrl($url) ? null : $url;
    }

    private function nullIfEmpty(string $value): ?string
    {
        return trim($value) === '' ? null : $value;
    }
}
