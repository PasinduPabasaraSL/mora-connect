<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Google's OAuth 2 authorisation code flow, by hand.
 *
 * The project has no Composer, so rather than pull in a library this implements
 * the three steps directly: send the user to Google, swap the code they come
 * back with for an access token, then ask Google who they are.
 *
 * The identity comes from the userinfo endpoint rather than from decoding the
 * id_token. Both are equally valid, but verifying a JWT signature means
 * fetching and caching Google's signing keys, whereas a server-to-server HTTPS
 * call to Google is trustworthy on its own — TLS already proves who answered.
 */
final class Google
{
    private const AUTH_ENDPOINT     = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_ENDPOINT    = 'https://oauth2.googleapis.com/token';
    private const USERINFO_ENDPOINT = 'https://openidconnect.googleapis.com/v1/userinfo';

    /** Anything slower than this is a dead end, and the user is left waiting. */
    private const TIMEOUT = 10;

    public static function configured(): bool
    {
        return self::clientId() !== '' && self::clientSecret() !== '';
    }

    private static function clientId(): string
    {
        return trim((string) Config::get('google.client_id', ''));
    }

    private static function clientSecret(): string
    {
        return trim((string) Config::get('google.client_secret', ''));
    }

    private static function redirectUri(): string
    {
        return trim((string) Config::get('google.redirect_uri', ''));
    }

    /**
     * Where to send the user to sign in.
     *
     * $state is a random value kept in the session and checked when Google
     * sends the user back; without it, an attacker could feed someone else's
     * authorisation code to our callback.
     */
    public static function authUrl(string $state): string
    {
        return self::AUTH_ENDPOINT . '?' . http_build_query([
            'client_id'     => self::clientId(),
            'redirect_uri'  => self::redirectUri(),
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => $state,
            // Google remembers the last choice otherwise, which makes signing
            // in as somebody else on a shared machine impossible
            'prompt'        => 'select_account',
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Swaps the authorisation code for an access token.
     *
     * @return string the access token, or '' if Google refused
     */
    public static function exchangeCode(string $code): string
    {
        $response = self::post(self::TOKEN_ENDPOINT, [
            'code'          => $code,
            'client_id'     => self::clientId(),
            'client_secret' => self::clientSecret(),
            'redirect_uri'  => self::redirectUri(),
            'grant_type'    => 'authorization_code',
        ]);

        $token = $response['access_token'] ?? null;

        return is_string($token) ? $token : '';
    }

    /**
     * The signed-in person's details.
     *
     * Returns null unless Google confirms the address is verified. An
     * unverified address must not be trusted, because accounts here are matched
     * by email — accepting one would be a way to claim somebody else's account.
     *
     * @return array{sub: string, email: string, name: string, picture: string}|null
     */
    public static function fetchUser(string $accessToken): ?array
    {
        $profile = self::get(self::USERINFO_ENDPOINT, $accessToken);

        $sub   = $profile['sub'] ?? '';
        $email = $profile['email'] ?? '';

        if (!is_string($sub) || $sub === '' || !is_string($email) || $email === '') {
            return null;
        }

        if (($profile['email_verified'] ?? false) !== true) {
            return null;
        }

        return [
            'sub'     => $sub,
            'email'   => strtolower($email),
            'name'    => is_string($profile['name'] ?? null) ? $profile['name'] : '',
            'picture' => self::pictureFrom($profile),
        ];
    }

    /**
     * Google's avatar URL, kept only if it really is one of theirs.
     *
     * The value ends up in an <img src>, and pinning it to Google's own hosts
     * means a changed API response can never turn the profile page into a
     * request to somewhere else.
     *
     * @param array<string, mixed> $profile
     */
    private static function pictureFrom(array $profile): string
    {
        $picture = $profile['picture'] ?? null;

        if (!is_string($picture) || $picture === '') {
            return '';
        }

        $host = parse_url($picture, PHP_URL_HOST);

        if (!is_string($host)) {
            return '';
        }

        $allowed = str_ends_with($host, '.googleusercontent.com')
            || str_ends_with($host, '.google.com');

        return $allowed && str_starts_with($picture, 'https://') ? $picture : '';
    }

    /**
     * @param  array<string, string> $fields
     * @return array<string, mixed>
     */
    private static function post(string $url, array $fields): array
    {
        return self::send($url, [
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => http_build_query($fields),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function get(string $url, string $accessToken): array
    {
        return self::send($url, [
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken],
        ]);
    }

    /**
     * @param  array<int, mixed> $options
     * @return array<string, mixed>
     */
    private static function send(string $url, array $options): array
    {
        $handle = curl_init($url);

        if ($handle === false) {
            return [];
        }

        curl_setopt_array($handle, $options + [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            // Not negotiable: without verification a man in the middle could
            // answer for Google and hand us any identity they liked.
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $body   = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);

        curl_close($handle);

        if (!is_string($body) || $status < 200 || $status > 299) {
            return [];
        }

        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : [];
    }
}
