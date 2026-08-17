<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Avatar;
use App\Core\Model;

final class User extends Model
{
    /** Where the profile picture comes from */
    public const AVATAR_UPLOAD   = 'upload';
    public const AVATAR_GOOGLE   = 'google';
    public const AVATAR_INITIALS = 'initials';

    /**
     * Fields a person may edit on their own profile.
     *
     * username is not here and never will be: it identifies the account and
     * appears in author URLs. Nor are email, role or anything to do with
     * authentication, so a crafted form cannot reach them.
     *
     * @var list<string>
     */
    private const EDITABLE = [
        'display_name', 'headline', 'bio',
        'faculty', 'programme', 'study_year',
        'website', 'github', 'linkedin', 'interests',
    ];

    public function findByLogin(string $identifier): ?array
    {
        return $this->selectOne(
            'SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1',
            [$identifier, $identifier]
        );
    }

    public function existsByUsernameOrEmail(string $username, string $email): bool
    {
        return $this->selectOne(
            'SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1',
            [$username, $email]
        ) !== null;
    }

    /**
     * Stores the bcrypt hash of the password, never the password itself.
     */
    public function create(string $username, string $email, string $password): int
    {
        $this->execute(
            "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'student')",
            [$username, $email, password_hash($password, PASSWORD_DEFAULT)]
        );

        return $this->lastInsertId();
    }

    public function verifyPassword(array $user, string $password): bool
    {
        $hash = $user['password'] ?? null;

        // A Google-only account has no hash. password_verify() would simply
        // return false, but being explicit keeps the intent obvious.
        return is_string($hash) && $hash !== '' && password_verify($password, $hash);
    }

    /**
     * Whether this account can be signed into with a password at all.
     *
     * @param array<string, mixed> $user
     */
    public static function hasPassword(array $user): bool
    {
        return is_string($user['password'] ?? null) && $user['password'] !== '';
    }

    public function findByGoogleId(string $googleId): ?array
    {
        return $this->selectOne('SELECT * FROM users WHERE google_id = ? LIMIT 1', [$googleId]);
    }

    public function findByEmail(string $email): ?array
    {
        return $this->selectOne('SELECT * FROM users WHERE email = ? LIMIT 1', [$email]);
    }

    /**
     * Records that an existing account is also reachable through Google, so the
     * next sign-in matches on the subject id instead of the email.
     */
    public function linkGoogle(int $userId, string $googleId): void
    {
        $this->execute('UPDATE users SET google_id = ? WHERE id = ?', [$googleId, $userId]);
    }

    /**
     * Creates an account for someone arriving from Google. There is no password
     * to store, so that column stays null and password sign-in is closed for
     * this account until they set one.
     */
    public function createFromGoogle(string $username, string $email, string $googleId): int
    {
        $this->execute(
            "INSERT INTO users (username, email, google_id, password, role)
             VALUES (?, ?, ?, NULL, 'student')",
            [$username, $email, $googleId]
        );

        return $this->lastInsertId();
    }

    /**
     * Turns a Google display name into a username nobody is using yet.
     *
     * The name is whatever the person put on their Google account, so it may be
     * empty, may collide, and may be nothing but punctuation — hence the
     * fallback to the local part of the email, and then to a plain word.
     */
    public function uniqueUsername(string $preferred, string $email): string
    {
        $base = strtolower(trim($preferred));
        $base = preg_replace('/[^a-z0-9._-]+/', '', str_replace(' ', '', $base)) ?? '';

        if (mb_strlen($base) < 3) {
            $base = preg_replace('/[^a-z0-9._-]+/', '', strtolower(explode('@', $email)[0])) ?? '';
        }

        if (mb_strlen($base) < 3) {
            $base = 'writer';
        }

        $base = mb_substr($base, 0, 90);
        $username = $base;

        for ($suffix = 2; $this->usernameTaken($username); $suffix++) {
            $username = $base . $suffix;
        }

        return $username;
    }

    private function usernameTaken(string $username): bool
    {
        return $this->selectOne('SELECT id FROM users WHERE username = ? LIMIT 1', [$username]) !== null;
    }

    public function find(int $id): ?array
    {
        return $this->selectOne('SELECT * FROM users WHERE id = ? LIMIT 1', [$id]);
    }

    public function findByUsername(string $username): ?array
    {
        return $this->selectOne('SELECT * FROM users WHERE username = ? LIMIT 1', [$username]);
    }

    /**
     * Saves the editable half of a profile.
     *
     * Only keys in EDITABLE are written, so an extra field posted by hand is
     * dropped rather than trusted, and blank values are stored as null so
     * "unset" is one state in the database rather than two.
     *
     * @param array<string, string|null> $fields
     */
    public function updateProfile(int $id, array $fields): void
    {
        $columns  = [];
        $bindings = [];

        foreach (self::EDITABLE as $column) {
            if (!array_key_exists($column, $fields)) {
                continue;
            }

            $value = $fields[$column];
            $value = $value === null ? null : trim($value);

            $columns[]  = $column . ' = ?';
            $bindings[] = ($value === null || $value === '') ? null : $value;
        }

        if ($columns === []) {
            return;
        }

        $bindings[] = $id;

        $this->execute('UPDATE users SET ' . implode(', ', $columns) . ' WHERE id = ?', $bindings);
    }

    /**
     * Points the avatar at an uploaded file, deleting whichever file it replaces
     * so the directory does not fill up with orphans.
     */
    public function setUploadedAvatar(int $id, string $fileName): void
    {
        $current = $this->find($id);

        $this->execute(
            'UPDATE users SET avatar_path = ?, avatar_source = ? WHERE id = ?',
            [$fileName, self::AVATAR_UPLOAD, $id]
        );

        if ($current !== null && ($current['avatar_path'] ?? null) !== null) {
            Avatar::delete((string) $current['avatar_path']);
        }
    }

    /**
     * Switches between an already-uploaded picture, the Google one and initials
     * without discarding the other. Someone who tries Google and changes their
     * mind should get their upload back rather than have to find the file again.
     */
    public function setAvatarSource(int $id, string $source): void
    {
        $this->execute('UPDATE users SET avatar_source = ? WHERE id = ?', [$source, $id]);
    }

    public function removeAvatar(int $id): void
    {
        $current = $this->find($id);

        $this->execute(
            'UPDATE users SET avatar_path = NULL, avatar_source = ? WHERE id = ?',
            [self::AVATAR_INITIALS, $id]
        );

        if ($current !== null && ($current['avatar_path'] ?? null) !== null) {
            Avatar::delete((string) $current['avatar_path']);
        }
    }

    /** Remembers the picture Google supplies, so it can be chosen later. */
    public function setGoogleAvatar(int $id, string $url): void
    {
        $this->execute('UPDATE users SET google_avatar = ? WHERE id = ?', [$url, $id]);
    }

    public function setPassword(int $id, string $password): void
    {
        $this->execute(
            'UPDATE users SET password = ? WHERE id = ?',
            [password_hash($password, PASSWORD_DEFAULT), $id]
        );
    }

    /**
     * Deletes the account and everything on it.
     *
     * Articles go first: blogPost.user_id has no cascade behind it, so leaving
     * them would leave rows pointing at an author who no longer exists, and
     * every listing joins users to render a byline.
     */
    public function deleteAccount(int $id): void
    {
        $user = $this->find($id);

        $this->execute('DELETE FROM blogPost WHERE user_id = ?', [$id]);
        $this->execute('DELETE FROM users WHERE id = ?', [$id]);

        if ($user !== null && ($user['avatar_path'] ?? null) !== null) {
            Avatar::delete((string) $user['avatar_path']);
        }
    }

    /**
     * The name to show for a user, falling back to the username.
     *
     * @param array<string, mixed> $user
     */
    public static function nameFor(array $user): string
    {
        $display = trim((string) ($user['display_name'] ?? ''));

        return $display === '' ? (string) ($user['username'] ?? '') : $display;
    }

    /**
     * The picture to show, or null when initials should be drawn instead.
     *
     * @param array<string, mixed> $user
     */
    public static function avatarFor(array $user): ?string
    {
        $source = (string) ($user['avatar_source'] ?? self::AVATAR_INITIALS);

        if ($source === self::AVATAR_UPLOAD) {
            $path = trim((string) ($user['avatar_path'] ?? ''));

            return $path === '' ? null : Avatar::url($path);
        }

        if ($source === self::AVATAR_GOOGLE) {
            $remote = trim((string) ($user['google_avatar'] ?? ''));

            return $remote === '' ? null : $remote;
        }

        return null;
    }

    /**
     * The topics a writer says they cover, filtered against the configured
     * categories so a stale or hand-posted value cannot render.
     *
     * @param  array<string, mixed> $user
     * @return list<string>
     */
    public static function interestsFor(array $user): array
    {
        $raw = trim((string) ($user['interests'] ?? ''));

        if ($raw === '') {
            return [];
        }

        $chosen = array_map('trim', explode(',', $raw));

        return array_values(array_intersect(Post::categories(), $chosen));
    }
}
