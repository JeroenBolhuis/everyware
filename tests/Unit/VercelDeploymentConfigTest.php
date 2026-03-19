<?php

test('vercel deployment config is present and pinned to the expected runtime', function () {
    $projectPath = dirname(__DIR__, 2);
    $config = json_decode(
        file_get_contents($projectPath.'/vercel.json'),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    expect($config['framework'])->toBeNull();
    expect($config['installCommand'])->toContain('composer install');
    expect($config['installCommand'])->toContain('npm ci');
    expect($config['buildCommand'])->toBe('composer run vercel-build');
    expect($config['functions']['vercel.php']['runtime'])->toBe('vercel-php@0.8.0');
    expect($config['routes'][array_key_last($config['routes'])])->toBe([
        'src' => '/(.*)',
        'dest' => '/vercel.php',
    ]);
});

test('deployment tool versions are pinned for local and vercel parity', function () {
    $projectPath = dirname(__DIR__, 2);
    $composer = json_decode(
        file_get_contents($projectPath.'/composer.json'),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    $package = json_decode(
        file_get_contents($projectPath.'/package.json'),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    expect($composer['require']['php'])->toBe('^8.4');
    expect($composer['extra']['runtime']['node'])->toBe('22');
    expect($package['engines']['node'])->toBe('22.x');
});
