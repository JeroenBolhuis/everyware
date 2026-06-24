<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

arch('controllers follow Laravel naming conventions')
    ->expect('App\Http\Controllers')
    ->classes()
    ->toHaveSuffix('Controller');

arch('form requests follow Laravel conventions')
    ->expect('App\Http\Requests')
    ->classes()
    ->toHaveSuffix('Request')
    ->toExtend(FormRequest::class)
    ->toHaveMethod('authorize')
    ->toHaveMethod('rules');

arch('models stay inside the Eloquent model layer')
    ->expect('App\Models')
    ->classes()
    ->toExtend(Model::class);

arch('policies follow Laravel naming conventions')
    ->expect('App\Policies')
    ->classes()
    ->toHaveSuffix('Policy');

arch('application code contains no debug helpers')
    ->expect(['dd', 'ddd', 'dump', 'ray'])
    ->not->toBeUsed();

it('uses the streamlined Laravel 12 application structure', function () {
    $root = dirname(__DIR__, 2);

    expect($root.'/app/Http/Kernel.php')->not->toBeFile()
        ->and($root.'/app/Console/Kernel.php')->not->toBeFile()
        ->and($root.'/bootstrap/app.php')->toBeFile()
        ->and($root.'/bootstrap/providers.php')->toBeFile();
});
