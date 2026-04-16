<?php

namespace Tests\Feature;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class DatabaseSetupTest extends LaravelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    public function test_all_migrations_and_seeders_can_run_successfully(): void
    {
        $databasePath = database_path('testing-seed.sqlite');

        File::delete($databasePath);
        File::put($databasePath, '');

        $process = new Process(
            [
                PHP_BINARY,
                'artisan',
                'migrate:fresh',
                '--seed',
                '--database=sqlite',
                '--no-interaction',
            ],
            base_path(),
            [
                ...$_ENV,
                'APP_ENV' => 'testing',
                'DB_CONNECTION' => 'sqlite',
                'DB_DATABASE' => $databasePath,
                'DB_URL' => '',
                'CACHE_STORE' => 'array',
                'SESSION_DRIVER' => 'array',
                'QUEUE_CONNECTION' => 'sync',
                'MAIL_MAILER' => 'array',
            ]
        );

        try {
            $process->run();

            $this->assertTrue(
                $process->isSuccessful(),
                $process->getOutput().PHP_EOL.$process->getErrorOutput()
            );
        } finally {
            File::delete($databasePath);
        }
    }
}
