<?php

use App\Console\Commands\FreshDatabases;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function invokeFreshDatabasesCommandMethod(string $method, mixed ...$arguments): mixed
{
    $reflection = new ReflectionMethod(FreshDatabases::class, $method);
    $reflection->setAccessible(true);

    return $reflection->invokeArgs(new FreshDatabases, $arguments);
}

it('wraps database identifiers for supported personal database drivers', function () {
    expect(invokeFreshDatabasesCommandMethod('wrapIdentifier', 'plain_table', 'sqlite'))->toBe('"plain_table"')
        ->and(invokeFreshDatabasesCommandMethod('wrapIdentifier', 'participant"identities', 'pgsql'))->toBe('"participant""identities"')
        ->and(invokeFreshDatabasesCommandMethod('wrapIdentifier', 'participant`identities', 'mysql'))->toBe('`participant``identities`');
});

it('discovers and wipes personal sqlite tables', function () {
    Schema::connection('personal')->create('temporary_personal_records', function ($table) {
        $table->id();
    });

    expect(invokeFreshDatabasesCommandMethod('personalTableNames', 'sqlite'))
        ->toContain('temporary_personal_records');

    invokeFreshDatabasesCommandMethod('wipePersonalDatabase');

    expect(DB::connection('personal')->select(
        "SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'temporary_personal_records'"
    ))->toBe([]);
});
