<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('guest is redirected to login from the dashboard', function () {
    $this->get('/')->assertRedirect(route('login'));
});
