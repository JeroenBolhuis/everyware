<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('does not render duplicate in-page admin navigation', function () {
    $admin = User::factory()->admin()->createOne();

    actingAs($admin);

    $pages = [
        route('admin.surveys.index'),
        route('admin.users.index'),
        route('admin.participants.mail'),
    ];

    foreach ($pages as $url) {
        $content = get($url)->assertOk()->getContent();

        expect(substr_count($content, 'data-flux-navlist>'))
            ->toBe(0, "Duplicate admin navlist found on {$url}");
    }
});

it('renders a consistent page heading on admin index pages', function () {
    $admin = User::factory()->admin()->createOne();

    actingAs($admin);

    get(route('admin.surveys.index'))
        ->assertOk()
        ->assertSee('id="admin-surveys-page-title"', false)
        ->assertSee('Bekijk enquetes en open individuele inzendingen', false);

    get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('id="admin-users-page-title"', false)
        ->assertSee('Maak accounts aan en wijs rollen toe', false);

    get(route('admin.participants.index'))
        ->assertOk()
        ->assertSee('id="admin-participants-page-title"', false)
        ->assertSee('Zoek op volgnummer om inzendingen', false);

    get(route('admin.participants.points'))
        ->assertOk()
        ->assertSee('id="admin-participant-points-page-title"', false)
        ->assertSee('Pas punten aan via e-mail', false);
});

it('renders participant section navigation on participant pages', function () {
    $admin = User::factory()->admin()->createOne();

    actingAs($admin);

    get(route('admin.participants.index'))
        ->assertOk()
        ->assertSee('Overzicht')
        ->assertSee('Punten aanpassen');

    get(route('admin.participants.points'))
        ->assertOk()
        ->assertSee('Overzicht')
        ->assertSee('Punten aanpassen');
});
