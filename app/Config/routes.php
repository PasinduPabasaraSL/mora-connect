<?php

declare(strict_types=1);

use App\Core\Router;

return static function (Router $router): void {
    $router->get('/', 'HomeController@index');
    $router->get('/about', 'PageController@about');
    $router->get('/search', 'SearchController@index');
    $router->get('/radar', 'RadarController@index');

    // Auth
    $router->get('/login', 'AuthController@showLogin');
    $router->post('/login', 'AuthController@login');
    $router->get('/register', 'AuthController@showRegister');
    $router->post('/register', 'AuthController@register');
    $router->post('/logout', 'AuthController@logout');

    $router->get('/profile', 'ProfileController@index');

    $router->get('/topics/{slug}', 'TopicController@show');

    // Posts
    $router->get('/posts/create', 'PostController@create');
    $router->post('/posts/autosave', 'PostController@autosave');
    $router->post('/posts', 'PostController@store');
    $router->get('/posts/{key}', 'PostController@show');
    $router->get('/posts/{id}/edit', 'PostController@edit');
    $router->get('/posts/{id}/preview', 'PostController@preview');
    $router->post('/posts/{id}', 'PostController@update');
    $router->post('/posts/{id}/delete', 'PostController@destroy');
    $router->post('/posts/{id}/unpublish', 'PostController@unpublish');
};
