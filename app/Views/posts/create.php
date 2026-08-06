<?php

use App\Core\View;

View::partial('posts/_form', [
    'heading' => 'Write a new post',
    'action'  => url('posts'),
    'submit'  => 'Publish',
    'post'    => $post,
    'errors'  => $errors ?? [],
]);
