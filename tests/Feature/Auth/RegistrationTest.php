<?php

it('does not expose public registration', function () {
    $response = $this->get('/register');

    $response->assertNotFound();
});

it('does not accept registration posts', function () {
    $response = $this->post('/register', [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertNotFound();
});
