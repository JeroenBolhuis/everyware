<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Compiler\CacheManager;
use Livewire\Compiler\Compiler;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->configureLivewireCompilerCache();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureVercelServerless();
        $this->configureDefaults();
    }

    /**
     * When VERCEL=1, avoid database-backed session/cache/queue if dashboard env copies .env.
     */
    protected function configureVercelServerless(): void
    {
        if (! getenv('VERCEL')) {
            return;
        }

        config([
            'cache.default' => 'array',
            'queue.default' => 'sync',
            'session.driver' => 'cookie',
        ]);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    protected function configureLivewireCompilerCache(): void
    {
        if (! $this->app->environment('local')) {
            return;
        }

        $this->app->singleton('livewire.compiler', function () {
            $cacheDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'everyware-livewire-'.md5(base_path());

            return new Compiler(
                new CacheManager($cacheDirectory)
            );
        });
    }
}
