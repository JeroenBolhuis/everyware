<?php

use function Pest\Laravel\get;

it('returns a successful response', function () {
    $response = get(route('home'));

    $response->assertOk();
});
