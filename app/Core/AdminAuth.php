<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Sign-in for the admin panel.
 *
 * Kept entirely separate from Auth, because the two are different kinds of
 * thing. Auth identifies a student who writes articles and owns a profile;
 * this identifies whoever operates the site. The admin is not a row in `users`,
 * so it needs no email address, never appears in a byline or a writer count,
 * and cannot be granted by editing a profile.
 *
 * The session key is separate too, so being signed in as a student is never
 * halfway to being signed in here, and signing out of one leaves the other be.
 */
final class AdminAuth
{
    private const KEY = 'admin_user';

    private const ATTEMPTS = 'admin_attempts';
    private const LOCKED   = 'admin_locked_until';

    /** Failures allowed before the form stops accepting guesses. */
    private const MAX_ATTEMPTS = 5;

    /** How long a lockout lasts, in seconds. */
    private const LOCKOUT = 900;

    /**
     * Whether an admin has been configured at all. With either setting missing
     * the panel behaves as though it does not exist, so a deployment that never
     * filled in .env is closed rather than open with a guessable password.
     */
    public static function configured(): bool
    {
        return self::username() !== '' && self::hash() !== '';
    }

    private static function username(): string
    {
        return trim((string) Config::get('admin.username', ''));
    }

    private static function hash(): string
    {
        return trim((string) Config::get('admin.password_hash', ''));
    }

    public static function check(): bool
    {
        return Session::has(self::KEY);
    }

    public static function user(): string
    {
        return (string) Session::get(self::KEY, '');
    }

    /**
     * Seconds left on a lockout, or 0 when guesses are being accepted.
     *
     * Held in the session, which means clearing cookies resets it. That makes
     * this a brake on somebody working through a password list in one browser,
     * not a defence against a determined attacker — the length of the password
     * is what does that job.
     */
    public static function lockedFor(): int
    {
        return max(0, (int) Session::get(self::LOCKED, 0) - time());
    }

    public static function attempt(string $username, string $password): bool
    {
        if (!self::configured() || self::lockedFor() > 0) {
            return false;
        }

        // Both are always checked before answering, so a wrong username costs
        // the same work as a wrong password and cannot be probed for on its own.
        $nameMatches = hash_equals(self::username(), trim($username));
        $passMatches = password_verify($password, self::hash());

        if (!$nameMatches || !$passMatches) {
            self::recordFailure();

            return false;
        }

        // A fresh session id, so an id fixed before sign-in cannot become an
        // admin one afterwards.
        Session::regenerate();
        Session::put(self::KEY, self::username());
        Session::forget(self::ATTEMPTS);
        Session::forget(self::LOCKED);

        return true;
    }

    private static function recordFailure(): void
    {
        $attempts = (int) Session::get(self::ATTEMPTS, 0) + 1;

        if ($attempts < self::MAX_ATTEMPTS) {
            Session::put(self::ATTEMPTS, $attempts);

            return;
        }

        // The counter restarts with the lockout, so the next run of guesses has
        // to earn its own five before waiting again.
        Session::put(self::LOCKED, time() + self::LOCKOUT);
        Session::forget(self::ATTEMPTS);
    }

    /**
     * Leaves any student session alone; only the admin key goes.
     */
    public static function logout(): void
    {
        Session::forget(self::KEY);
        Session::regenerate();
    }
}
