<?php

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

function laravelStructureRoot(string $path = ''): string
{
    $root = dirname(__DIR__, 2);

    return $path === '' ? $root : $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);
}

function laravelStructureFiles(string $directory): array
{
    $root = laravelStructureRoot($directory);

    if (! is_dir($root)) {
        return [];
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }

    sort($files);

    return $files;
}

function laravelStructureClassFromAppPath(string $path): string
{
    $relativePath = str_replace([laravelStructureRoot('app').DIRECTORY_SEPARATOR, '.php'], '', $path);

    return 'App\\'.str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);
}

it('uses the streamlined Laravel 12 application structure', function () {
    expect(file_exists(laravelStructureRoot('app/Http/Kernel.php')))->toBeFalse()
        ->and(file_exists(laravelStructureRoot('app/Console/Kernel.php')))->toBeFalse()
        ->and(file_exists(laravelStructureRoot('bootstrap/providers.php')))->toBeTrue()
        ->and(file_exists(laravelStructureRoot('bootstrap/app.php')))->toBeTrue();
});

it('keeps environment access inside configuration files', function () {
    $violations = collect([
        ...laravelStructureFiles('app'),
        ...laravelStructureFiles('routes'),
        ...laravelStructureFiles('resources/views'),
        ...laravelStructureFiles('database'),
    ])->filter(function (string $path): bool {
        $contents = file_get_contents($path) ?: '';

        return str_contains($contents, 'env(') || str_contains($contents, 'getenv(');
    })->map(fn (string $path): string => str_replace(laravelStructureRoot().DIRECTORY_SEPARATOR, '', $path))->values();

    expect($violations)->toBeEmpty();
});

it('keeps controllers in the controller namespace and extending the base controller', function () {
    $violations = collect(laravelStructureFiles('app/Http/Controllers'))
        ->reject(fn (string $path): bool => basename($path) === 'Controller.php')
        ->filter(function (string $path): bool {
            $class = laravelStructureClassFromAppPath($path);

            if (! str_ends_with($class, 'Controller') || ! class_exists($class)) {
                return true;
            }

            return ! is_subclass_of($class, Controller::class);
        })
        ->map(fn (string $path): string => str_replace(laravelStructureRoot().DIRECTORY_SEPARATOR, '', $path))
        ->values();

    expect($violations)->toBeEmpty();
});

it('keeps form requests explicit and self-authorizing', function () {
    $violations = collect(laravelStructureFiles('app/Http/Requests'))
        ->filter(function (string $path): bool {
            $class = laravelStructureClassFromAppPath($path);

            if (! str_ends_with($class, 'Request') || ! is_subclass_of($class, FormRequest::class)) {
                return true;
            }

            $reflection = new ReflectionClass($class);

            return ! $reflection->hasMethod('authorize')
                || ! $reflection->hasMethod('rules')
                || (string) $reflection->getMethod('authorize')->getReturnType() !== 'bool'
                || (string) $reflection->getMethod('rules')->getReturnType() !== 'array';
        })
        ->map(fn (string $path): string => str_replace(laravelStructureRoot().DIRECTORY_SEPARATOR, '', $path))
        ->values();

    expect($violations)->toBeEmpty();
});

it('requires application models to define mass assignment boundaries', function () {
    $violations = collect(laravelStructureFiles('app/Models'))
        ->filter(function (string $path): bool {
            $class = laravelStructureClassFromAppPath($path);

            if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
                return true;
            }

            $reflection = new ReflectionClass($class);

            return ! $reflection->hasProperty('fillable') && ! $reflection->hasProperty('guarded');
        })
        ->map(fn (string $path): string => str_replace(laravelStructureRoot().DIRECTORY_SEPARATOR, '', $path))
        ->values();

    expect($violations)->toBeEmpty();
});

it('keeps policy classes boolean-returning and named as policies', function () {
    $violations = collect(laravelStructureFiles('app/Policies'))
        ->filter(function (string $path): bool {
            $class = laravelStructureClassFromAppPath($path);

            if (! str_ends_with($class, 'Policy') || ! class_exists($class)) {
                return true;
            }

            $reflection = new ReflectionClass($class);

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->class === $class && (string) $method->getReturnType() !== 'bool') {
                    return true;
                }
            }

            return false;
        })
        ->map(fn (string $path): string => str_replace(laravelStructureRoot().DIRECTORY_SEPARATOR, '', $path))
        ->values();

    expect($violations)->toBeEmpty();
});
