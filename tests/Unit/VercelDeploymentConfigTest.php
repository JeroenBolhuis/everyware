<?php

test('vercel uses the server entrypoint to serve built assets and laravel', function () {
    $projectPath = dirname(__DIR__, 2);

    $config = json_decode(
        file_get_contents($projectPath.'/vercel.json'),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    expect($config['functions']['api/index.php']['runtime'])->toBe('vercel-php@0.8.0')
        ->and($config['routes'])->toBe([
            [
                'src' => '/(.*)',
                'dest' => '/api/index.php',
            ],
        ])
        ->and($config['env']['ASSET_URL'])->toBe('/');
});

test('server entrypoint exists and can serve files from public', function () {
    $projectPath = dirname(__DIR__, 2);
    $server = file_get_contents($projectPath.'/api/index.php');

    expect($server)->toContain("realpath(__DIR__.'/../public')")
        ->and($server)->toContain('readfile($requestedFile);')
        ->and($server)->toContain("require \$publicPath.'/index.php';");
});
