<?php

use Illuminate\Support\Facades\Route;

use function Pest\Laravel\get;

it('renders custom http error pages', function (int $status, string $text) {
    Route::get("/_test-error-{$status}", fn () => abort($status));

    get("/_test-error-{$status}")
        ->assertStatus($status)
        ->assertSee("Fout {$status}")
        ->assertSee($text);
})->with([
    [400, 'Ongeldig verzoek'],
    [401, 'Niet ingelogd'],
    [403, 'Geen toegang'],
    [404, 'Pagina niet gevonden'],
    [419, 'Sessie verlopen'],
    [429, 'Te veel pogingen'],
    [503, 'Tijdelijk niet beschikbaar'],
]);

it('renders the custom server error page in production style exception handling', function () {
    config(['app.debug' => false]);

    Route::get('/_test-server-error', function () {
        throw new RuntimeException('Unexpected failure');
    });

    get('/_test-server-error')
        ->assertStatus(500)
        ->assertSee('Fout 500')
        ->assertSee('Er ging iets mis')
        ->assertDontSee('Unexpected failure');
});
