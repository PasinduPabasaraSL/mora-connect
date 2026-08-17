<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Avatar;
use App\Core\Controller;
use App\Core\Session;
use App\Models\Post;
use App\Models\User;

/**
 * Profile settings.
 *
 * Split into one action per form rather than one that saves everything, so a
 * rejected password does not throw away an unsaved bio, and each form can send
 * the reader back to its own part of the page.
 */
final class SettingsController extends Controller
{
    /** Study years offered, so the value is never free text. */
    private const YEARS = [
        '1st year', '2nd year', '3rd year', '4th year', '5th year',
        'Postgraduate', 'Alumni',
    ];

    public function edit(): void
    {
        $this->requireLogin();

        $this->render();
    }

    /**
     * @param list<string>         $errors
     * @param array<string, mixed> $overrides values to show instead of the
     *                                        stored ones, so a rejected form
     *                                        does not lose what was typed
     */
    private function render(array $errors = [], array $overrides = []): void
    {
        $user = Auth::user();

        if ($user === null) {
            $this->redirect('login');
        }

        $shown = array_merge($user, $overrides);

        $this->view('profile/settings', [
            'title'       => 'Profile settings',
            'user'        => $shown,
            'errors'      => $errors,
            'years'       => self::YEARS,
            'categories'  => Post::categories(),
            'interests'   => User::interestsFor($shown),
            // Whether to ask for the current password, or offer to set a first one
            'hasPassword' => User::hasPassword($user),
        ]);
    }

    public function updateProfile(): void
    {
        $this->requireLogin();
        $this->requireCsrf();

        $fields = [
            'display_name' => $this->request->input('display_name'),
            'headline'     => $this->request->input('headline'),
            'bio'          => $this->request->input('bio'),
            'faculty'      => $this->request->input('faculty'),
            'programme'    => $this->request->input('programme'),
            'study_year'   => $this->request->input('study_year'),
            'website'      => $this->request->input('website'),
            'github'       => $this->request->input('github'),
            'linkedin'     => $this->request->input('linkedin'),
            'interests'    => $this->chosenInterests(),
        ];

        $errors = $this->validateProfile($fields);

        if ($errors !== []) {
            $this->render($errors, $fields);

            return;
        }

        (new User())->updateProfile((int) Auth::id(), $fields);

        Session::flash('success', 'Your profile has been updated.');
        $this->redirect('settings');
    }

    /**
     * @param array<string, string> $fields
     * @return list<string>
     */
    private function validateProfile(array $fields): array
    {
        $errors = [];

        $lengths = [
            'display_name' => [80, 'Display name'],
            'headline'     => [160, 'Headline'],
            'bio'          => [600, 'Bio'],
            'faculty'      => [120, 'Faculty'],
            'programme'    => [120, 'Programme'],
            'github'       => [64, 'GitHub username'],
            'website'      => [255, 'Website'],
            'linkedin'     => [255, 'LinkedIn'],
        ];

        foreach ($lengths as $field => [$limit, $label]) {
            if (mb_strlen($fields[$field]) > $limit) {
                $errors[] = $label . ' must be ' . $limit . ' characters or fewer.';
            }
        }

        if ($fields['display_name'] !== '' && mb_strlen($fields['display_name']) < 2) {
            $errors[] = 'Display name must be at least 2 characters, or left blank to use your username.';
        }

        if ($fields['study_year'] !== '' && !in_array($fields['study_year'], self::YEARS, true)) {
            $errors[] = 'Please choose a year of study from the list.';
        }

        if ($fields['website'] !== '' && !$this->isHttpUrl($fields['website'])) {
            $errors[] = 'Your website must be a full address starting with http:// or https://';
        }

        if ($fields['linkedin'] !== '' && !$this->isHttpUrl($fields['linkedin'])) {
            $errors[] = 'Your LinkedIn link must be a full address starting with https://';
        }

        // Stored bare and printed into a github.com URL, so anything that is not
        // a GitHub username would build a link somewhere else entirely.
        if ($fields['github'] !== '' && preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9-]{0,38})$/', $fields['github']) !== 1) {
            $errors[] = 'GitHub username may only contain letters, numbers and hyphens.';
        }

        return $errors;
    }

    private function isHttpUrl(string $value): bool
    {
        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));

        // Rejects javascript: and data:, which pass URL validation and would run
        // when somebody clicked the link on a profile.
        return $scheme === 'http' || $scheme === 'https';
    }

    /**
     * The chosen topics, kept to the configured list and stored as one string.
     */
    private function chosenInterests(): string
    {
        $posted = $_POST['interests'] ?? [];

        if (!is_array($posted)) {
            return '';
        }

        $valid = array_values(array_intersect(
            Post::categories(),
            array_filter($posted, 'is_string')
        ));

        return implode(', ', $valid);
    }

    /**
     * Handles all three avatar actions: a new upload, switching to the Google
     * picture or back to an upload, and removing it altogether.
     */
    public function updateAvatar(): void
    {
        $this->requireLogin();
        $this->requireCsrf();

        $users  = new User();
        $userId = (int) Auth::id();
        $user   = Auth::user() ?? [];
        $choice = $this->request->input('avatar_choice');

        // An empty file input still arrives in $_FILES, with an error code
        // saying so, which is why the code is what decides and not isset().
        $sent = isset($_FILES['avatar'])
            && (int) ($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

        if ($choice === 'remove') {
            $users->removeAvatar($userId);
            Session::flash('success', 'Your profile picture has been removed.');
            $this->redirect('settings');
        }

        if ($sent) {
            $result = Avatar::store($_FILES['avatar']);

            if ($result['error'] !== null) {
                $this->render([$result['error']]);

                return;
            }

            $users->setUploadedAvatar($userId, (string) $result['name']);

            Session::flash('success', 'Your profile picture has been updated.');
            $this->redirect('settings');
        }

        // No new file, so this is a switch between pictures already on the
        // account. Both are kept, so switching either way is free.
        if ($choice === User::AVATAR_GOOGLE) {
            if (trim((string) ($user['google_avatar'] ?? '')) === '') {
                $this->render(['There is no Google picture on this account. Sign in with Google to fetch one.']);

                return;
            }

            $users->setAvatarSource($userId, User::AVATAR_GOOGLE);
            Session::flash('success', 'Your Google picture is now your profile picture.');
            $this->redirect('settings');
        }

        if ($choice === User::AVATAR_UPLOAD) {
            if (trim((string) ($user['avatar_path'] ?? '')) === '') {
                $this->render(['Choose a picture to upload first.']);

                return;
            }

            $users->setAvatarSource($userId, User::AVATAR_UPLOAD);
            Session::flash('success', 'Your uploaded picture is now your profile picture.');
            $this->redirect('settings');
        }

        $this->render(['Choose a picture to upload, or pick one of the options.']);
    }

    public function updatePassword(): void
    {
        $this->requireLogin();
        $this->requireCsrf();

        $user = Auth::user();

        if ($user === null) {
            $this->redirect('login');
        }

        $users   = new User();
        $current = $this->request->raw('current_password');
        $new     = $this->request->raw('new_password');
        $confirm = $this->request->raw('confirm_password');
        $errors  = [];

        // An account created through Google has no password yet, so there is
        // nothing to ask for — but only then, or this would be a way to take
        // over a session and change the password without knowing it.
        $needsCurrent = User::hasPassword($user);

        if ($needsCurrent && !$users->verifyPassword($user, $current)) {
            $errors[] = 'Your current password is not correct.';
        }

        if (mb_strlen($new) < 8) {
            $errors[] = 'Your new password must be at least 8 characters.';
        }

        if ($new !== $confirm) {
            $errors[] = 'The new passwords do not match.';
        }

        if ($errors !== []) {
            $this->render($errors);

            return;
        }

        $users->setPassword((int) $user['id'], $new);

        Session::flash(
            'success',
            $needsCurrent ? 'Your password has been changed.' : 'Your password has been set.'
        );
        $this->redirect('settings');
    }

    /**
     * Deletes the account, after the reader has typed their username to confirm.
     */
    public function destroy(): void
    {
        $this->requireLogin();
        $this->requireCsrf();

        $user = Auth::user();

        if ($user === null) {
            $this->redirect('login');
        }

        $typed = $this->request->input('confirm_username');

        if (!hash_equals((string) $user['username'], $typed)) {
            $this->render(['Type your username exactly to confirm you want the account deleted.']);

            return;
        }

        (new User())->deleteAccount((int) $user['id']);

        Auth::logout();

        // logout() destroys the session, so a flash written now would have
        // nowhere to live. A fresh session carries the goodbye message.
        Session::start();
        Session::flash('success', 'Your account and everything on it has been deleted.');

        $this->redirect();
    }
}
