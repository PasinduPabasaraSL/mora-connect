<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Google;
use App\Core\Session;
use App\Models\User;

final class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect();
        }

        $this->view('auth/login', ['title' => 'Sign in']);
    }

    public function login(): void
    {
        if (Auth::check()) {
            $this->redirect();
        }

        $this->requireCsrf();

        $identifier = $this->request->input('identifier');
        $password   = $this->request->raw('password');
        $errors     = [];

        if ($identifier === '' || $password === '') {
            $errors[] = 'Please fill in all fields.';
        } else {
            $users = new User();
            $user  = $users->findByLogin($identifier);

            if ($user !== null && $users->verifyPassword($user, $password)) {
                Auth::login($user);
                $this->redirect();
            }

            // Someone who signed up through Google has no password to get
            // wrong, and would otherwise retype it forever. Saying so reveals
            // nothing an attacker could not learn by trying Google sign-in.
            if ($user !== null && !User::hasPassword($user)) {
                $errors[] = 'This account was created with Google. Use the Continue with Google button below.';
            } else {
                // Deliberately vague: naming which half was wrong would confirm
                // whether an account exists.
                $errors[] = 'Incorrect username/email or password.';
            }
        }

        $this->view('auth/login', [
            'title'      => 'Sign in',
            'errors'     => $errors,
            'identifier' => $identifier,
        ]);
    }

    public function showRegister(): void
    {
        if (Auth::check()) {
            $this->redirect();
        }

        $this->view('auth/register', ['title' => 'Register — MoraConnect']);
    }

    public function register(): void
    {
        if (Auth::check()) {
            $this->redirect();
        }

        $this->requireCsrf();

        $username = $this->request->input('username');
        $email    = $this->request->input('email');
        $password = $this->request->raw('password');
        $confirm  = $this->request->raw('confirm_password');

        $errors = $this->validateRegistration($username, $email, $password, $confirm);

        $users = new User();

        if ($errors === [] && $users->existsByUsernameOrEmail($username, $email)) {
            $errors[] = 'That username or email is already registered.';
        }

        if ($errors === []) {
            $id = $users->create($username, $email, $password);

            Auth::login([
                'id'       => $id,
                'username' => $username,
                'role'     => 'student',
            ]);

            $this->redirect();
        }

        $this->view('auth/register', [
            'title'    => 'Register — MoraConnect',
            'errors'   => $errors,
            'username' => $username,
            'email'    => $email,
        ]);
    }

    /**
     * @return list<string>
     */
    private function validateRegistration(string $username, string $email, string $password, string $confirm): array
    {
        $errors = [];

        if (mb_strlen($username) < 3) {
            $errors[] = 'Username must be at least 3 characters.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if (mb_strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }

        if ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }

        return $errors;
    }

    /**
     * Step one of Google sign-in: hand the user over to Google.
     *
     * The random state is remembered in the session so the callback can prove
     * the response belongs to a sign-in this browser actually started.
     */
    public function google(): void
    {
        if (Auth::check()) {
            $this->redirect();
        }

        if (!Google::configured()) {
            Session::flash('error', 'Google sign-in is not configured on this server.');
            $this->redirect('login');
        }

        $state = bin2hex(random_bytes(16));

        Session::put('google_state', $state);

        header('Location: ' . Google::authUrl($state));

        exit;
    }

    /**
     * Step two: Google sends the user back here with a code.
     *
     * Three ways to arrive at an account, in order: an existing Google link, an
     * existing account with the same email (linked on the spot), or a brand new
     * account.
     */
    public function googleCallback(): void
    {
        if (Auth::check()) {
            $this->redirect();
        }

        $expected = (string) Session::get('google_state', '');

        // Single use, whatever happens next
        Session::forget('google_state');

        if (!Google::configured()) {
            $this->failGoogle('Google sign-in is not configured on this server.');
        }

        // Google reports a refusal by sending an error back rather than a code
        if ($this->request->input('error') !== '') {
            $this->failGoogle('Google sign-in was cancelled.');
        }

        $state = $this->request->input('state');

        if ($expected === '' || $state === '' || !hash_equals($expected, $state)) {
            $this->failGoogle('That sign-in link has expired. Please try again.');
        }

        $code = $this->request->input('code');

        if ($code === '') {
            $this->failGoogle('Google did not return a sign-in code.');
        }

        $token = Google::exchangeCode($code);

        if ($token === '') {
            $this->failGoogle('Could not complete sign-in with Google. Please try again.');
        }

        $profile = Google::fetchUser($token);

        if ($profile === null) {
            $this->failGoogle('Google did not confirm a verified email address for that account.');
        }

        $users = new User();
        $user  = $users->findByGoogleId($profile['sub']);

        if ($user === null) {
            $user = $users->findByEmail($profile['email']);

            if ($user !== null) {
                // Same person, arriving a different way: attach the identity so
                // future sign-ins match on it rather than on the address.
                $users->linkGoogle((int) $user['id'], $profile['sub']);
            }
        }

        if ($user !== null) {
            Auth::login($user);
            Session::flash('success', 'Signed in as ' . $user['username'] . '.');
            $this->redirect();
        }

        $username = $users->uniqueUsername($profile['name'], $profile['email']);
        $id       = $users->createFromGoogle($username, $profile['email'], $profile['sub']);

        Auth::login([
            'id'       => $id,
            'username' => $username,
            'role'     => 'student',
        ]);

        Session::flash('success', 'Welcome to MoraConnect. You are signed in as ' . $username . '.');
        $this->redirect('profile');
    }

    /**
     * Sends the user back to the login page with an explanation. Google's own
     * error text is never shown, because it is written for developers.
     */
    private function failGoogle(string $message): never
    {
        Session::flash('error', $message);
        $this->redirect('login');
    }

    public function logout(): void
    {
        $this->requireCsrf();

        Auth::logout();
        $this->redirect('login');
    }
}
