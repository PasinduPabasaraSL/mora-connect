<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\User;

final class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect();
        }

        $this->view('auth/login', ['title' => 'Sign in — MoraConnect']);
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

            // Deliberately vague: naming which half was wrong would confirm
            // whether an account exists.
            $errors[] = 'Incorrect username/email or password.';
        }

        $this->view('auth/login', [
            'title'      => 'Sign in — MoraConnect',
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

    public function logout(): void
    {
        $this->requireCsrf();

        Auth::logout();
        $this->redirect('login');
    }
}
