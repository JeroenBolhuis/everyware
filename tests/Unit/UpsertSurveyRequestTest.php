<?php

use App\Http\Requests\Surveys\UpsertSurveyRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeUpsertSurveyRequest(array $data = [], array $files = []): UpsertSurveyRequest
{
    return UpsertSurveyRequest::create('/enquetes', 'POST', $data, [], $files);
}

it('authorizes users who can manage surveys', function (?string $factoryState, bool $expected) {
    $request = makeUpsertSurveyRequest();
    $factory = User::factory();

    if ($factoryState !== null) {
        $factory = $factory->{$factoryState}();
    }

    $user = $factory->createOne();

    $request->setUserResolver(fn () => $user);

    expect($request->authorize())->toBe($expected);
})->with([
    'admin' => ['admin', true],
    'lic employee' => ['licEmployee', true],
    'regular user' => [null, false],
]);

it('returns the survey validation rules and custom messages', function () {
    $request = new UpsertSurveyRequest;

    $rules = $request->rules();
    $messages = $request->messages();

    expect($rules)->toHaveKeys([
        'title',
        'description',
        'is_active',
        'ends_at',
        'reward_points',
        'questions',
        'questions.*.type',
        'questions.*.options.*.image',
    ]);

    expect($messages['title.required'])->toContain('titel');
    expect($messages['ends_at.date_format'])->toContain('einddatum');
    expect($messages['questions.*.type.required'])->toContain('vraagtype');
    expect($messages['questions.*.options.*.image.max'])->toContain('2 MB');
});

it('validates optional survey end dates', function () {
    $request = new UpsertSurveyRequest;
    $rules = $request->rules();

    $valid = Validator::make(['ends_at' => today()->addWeek()->toDateString()], [
        'ends_at' => $rules['ends_at'],
    ]);
    $empty = Validator::make(['ends_at' => null], [
        'ends_at' => $rules['ends_at'],
    ]);
    $invalid = Validator::make(['ends_at' => 'not-a-date'], [
        'ends_at' => $rules['ends_at'],
    ]);
    $past = Validator::make(['ends_at' => today()->subDay()->toDateString()], [
        'ends_at' => $rules['ends_at'],
    ]);

    expect($valid->passes())->toBeTrue()
        ->and($empty->passes())->toBeTrue()
        ->and($invalid->passes())->toBeFalse()
        ->and($past->passes())->toBeFalse();
});

it('adds an error when a radio question has fewer than two filled options', function () {
    $request = makeUpsertSurveyRequest([
        'questions' => [
            [
                'type' => 'radio',
                'options' => [
                    ['label' => 'Ja'],
                    ['label' => '   '],
                ],
            ],
        ],
    ]);

    $validator = Validator::make([], []);
    $request->withValidator($validator);

    expect($validator->passes())->toBeFalse();
    expect($validator->errors()->first('questions.0.options'))
        ->toBe('Een radio-vraag moet minimaal 2 opties hebben.');
});

it('adds an error when a swipe question does not have exactly two filled options', function () {
    $request = makeUpsertSurveyRequest([
        'questions' => [
            [
                'type' => 'swipe',
                'options' => [
                    ['label' => 'Links'],
                ],
            ],
        ],
    ]);

    $validator = Validator::make([], []);
    $request->withValidator($validator);

    expect($validator->passes())->toBeFalse();
    expect($validator->errors()->first('questions.0.options'))
        ->toBe('Een swipe-vraag moet precies 2 opties hebben.');
});

it('accepts a swipe question when two filled options are provided', function () {
    $request = makeUpsertSurveyRequest([
        'questions' => [
            [
                'type' => 'swipe',
                'options' => [
                    ['label' => ' Links '],
                    ['label' => ' Rechts '],
                    ['label' => '   '],
                ],
            ],
        ],
    ]);

    $validator = Validator::make([], []);
    $request->withValidator($validator);

    expect($validator->passes())->toBeTrue();
});

it('adds an error when the combined image upload size exceeds five megabytes', function () {
    $request = makeUpsertSurveyRequest(
        [
            'questions' => [
                [
                    'type' => 'swipe',
                    'options' => [
                        ['label' => 'Links'],
                        ['label' => 'Rechts'],
                    ],
                ],
            ],
        ],
        [
            'questions' => [
                [
                    'options' => [
                        [
                            'image' => UploadedFile::fake()->create('first.jpg', 3000, 'image/jpeg'),
                        ],
                        [
                            'image' => UploadedFile::fake()->create('second.jpg', 2500, 'image/jpeg'),
                        ],
                    ],
                ],
            ],
        ]
    );

    // Deze check leeft buiten de normale rule-set en telt alle uploads samen op.
    // Daarom testen we hem apart via de after-hook van het request.
    $validator = Validator::make([], []);
    $request->withValidator($validator);

    expect($validator->passes())->toBeFalse();
    expect($validator->errors()->first('questions'))->toContain('5 MB');
});
