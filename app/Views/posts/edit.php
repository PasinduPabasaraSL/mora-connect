<?php

use App\Core\View;

View::partial('posts/_form', [
    'heading' => 'Edit post',
    'action'  => url('posts/' . (int) $post['id']),
    'submit'  => 'Save Changes',
    'post'    => $post,
    'errors'  => $errors ?? [],
]);
