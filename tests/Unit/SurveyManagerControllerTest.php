<?php

use App\Http\Controllers\SurveyManagerController;
use App\Http\Requests\Surveys\UpsertSurveyRequest;
use App\Models\Survey;
use App\Models\SurveyResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function callSurveyManagerPrivateMethod(object $object, string $method, array $arguments = []): mixed
{
    return (fn (...$args) => $this->{$method}(...$args))->call($object, ...$arguments);
}

it('normalizes existing image values', function () {
    $controller = new SurveyManagerController();

    expect(callSurveyManagerPrivateMethod($controller, 'normalizeExistingImage', [null]))->toBeNull();
    expect(callSurveyManagerPrivateMethod($controller, 'normalizeExistingImage', ['   ']))->toBeNull();
    expect(callSurveyManagerPrivateMethod($controller, 'normalizeExistingImage', [42]))->toBeNull();
    expect(callSurveyManagerPrivateMethod($controller, 'normalizeExistingImage', [' survey-options/picture.jpg ']))
        ->toBe('survey-options/picture.jpg');
});

it('uses the configured survey images disk', function () {
    $controller = new SurveyManagerController();

    config(['filesystems.survey_images_disk' => 'survey_images']);
    expect(callSurveyManagerPrivateMethod($controller, 'surveyImagesDisk'))->toBe('survey_images');
});

it('detects absolute urls', function () {
    $controller = new SurveyManagerController();

    expect(callSurveyManagerPrivateMethod($controller, 'isAbsoluteUrl', ['https://example.com/image.jpg']))->toBeTrue();
    expect(callSurveyManagerPrivateMethod($controller, 'isAbsoluteUrl', ['survey-options/image.jpg']))->toBeFalse();
});

it('builds question payload with normalized values and sort order', function () {
    $controller = new SurveyManagerController();

    $request = Mockery::mock(UpsertSurveyRequest::class);
    $request->shouldReceive('hasFile')->andReturnFalse();

    $payload = callSurveyManagerPrivateMethod($controller, 'buildQuestionsPayload', [
        $request,
        [
            [
                'id' => '7',
                'question' => '  Eerste vraag  ',
                'type' => 'radio',
                'required' => '1',
                'options' => [
                    ['label' => ' Ja '],
                    ['label' => ''],
                    ' Nee ',
                ],
            ],
            [
                'id' => '',
                'question' => '  Toelichting  ',
                'type' => 'textarea',
                'options' => ['wordt genegeerd'],
            ],
        ],
    ]);

    expect($payload)->toBe([
        [
            'id' => 7,
            'question' => 'Eerste vraag',
            'type' => 'radio',
            'options' => ['Ja', 'Nee'],
            'required' => true,
            'sort_order' => 1,
        ],
        [
            'id' => null,
            'question' => 'Toelichting',
            'type' => 'textarea',
            'options' => null,
            'required' => false,
            'sort_order' => 2,
        ],
    ]);
});

it('normalizes non swipe options and ignores empty labels', function () {
    $controller = new SurveyManagerController();

    $request = Mockery::mock(UpsertSurveyRequest::class);
    $request->shouldReceive('hasFile')->andReturnFalse();

    $normalized = callSurveyManagerPrivateMethod($controller, 'normalizeOptions', [
        $request,
        0,
        'radio',
        [
            ['label' => ' Ja '],
            '',
            ' Nee ',
            ['label' => '   '],
        ],
    ]);

    expect($normalized)->toBe(['Ja', 'Nee']);
});

it('returns null options for textarea questions', function () {
    $controller = new SurveyManagerController();
    $request = Mockery::mock(UpsertSurveyRequest::class);

    $normalized = callSurveyManagerPrivateMethod($controller, 'normalizeOptions', [
        $request,
        0,
        'textarea',
        ['wordt genegeerd'],
    ]);

    expect($normalized)->toBeNull();
});

it('normalizes swipe options, replaces existing images and stores the new upload', function () {
    $controller = new SurveyManagerController();

    config(['filesystems.survey_images_disk' => 'survey_images']);
    Storage::fake('survey_images');

    $oldImage = 'survey-options/old-image.jpg';
    Storage::disk('survey_images')->put($oldImage, 'old-image');

    $request = UpsertSurveyRequest::create(
        '/surveys',
        'POST',
        [
            'questions' => [
                [
                    'options' => [
                        ['label' => ' Links ', 'existing_image' => $oldImage],
                        ['label' => ' Rechts '],
                    ],
                ],
            ],
        ],
        [],
        [
            'questions' => [
                [
                    'options' => [
                        [
                            'image' => UploadedFile::fake()->create('replacement.jpg', 100, 'image/jpeg'),
                        ],
                    ],
                ],
            ],
        ]
    );

    // Dit scenario pakt meteen de lastigste combinatie mee:
    // labels trimmen, bestaande afbeelding opruimen en een nieuwe upload opslaan
    // op de geconfigureerde disk.
    $normalized = callSurveyManagerPrivateMethod($controller, 'normalizeOptions', [
        $request,
        0,
        'swipe',
        [
            ['label' => ' Links ', 'existing_image' => $oldImage],
            ['label' => ' Rechts '],
        ],
    ]);

    expect($normalized)->toHaveCount(2);
    expect($normalized[0]['label'])->toBe('Links');
    expect($normalized[0]['image'])->toStartWith('survey-options/');
    expect($normalized[0]['image'])->not->toBe($oldImage);
    expect($normalized[1])->toBe([
        'label' => 'Rechts',
        'image' => null,
    ]);

    Storage::disk('survey_images')->assertMissing($oldImage);
    Storage::disk('survey_images')->assertExists($normalized[0]['image']);
});

it('returns default swipe options when fewer than two usable options remain', function () {
    $controller = new SurveyManagerController();

    $request = Mockery::mock(UpsertSurveyRequest::class);
    $request->shouldReceive('hasFile')->andReturnFalse();

    $normalized = callSurveyManagerPrivateMethod($controller, 'normalizeOptions', [
        $request,
        0,
        'swipe',
        [
            ['label' => ''],
            ['label' => 'Ja'],
        ],
    ]);

    expect($normalized)->toBe([
        ['label' => 'Nee', 'image' => null],
        ['label' => 'Ja', 'image' => null],
    ]);
});

it('throws a validation exception when an uploaded swipe image cannot be stored', function () {
    $controller = new SurveyManagerController();

    config(['filesystems.survey_images_disk' => 'survey_images']);

    $upload = Mockery::mock(UploadedFile::class);
    $upload->shouldReceive('store')
        ->once()
        ->with('survey-options', 'survey_images')
        ->andReturn('');

    $request = Mockery::mock(UpsertSurveyRequest::class);
    $request->shouldReceive('hasFile')
        ->once()
        ->with('questions.0.options.0.image')
        ->andReturnTrue();
    $request->shouldReceive('file')
        ->once()
        ->with('questions.0.options.0.image')
        ->andReturn($upload);

    // Deze fouttak is makkelijk over het hoofd te zien, dus die testen we los:
    // als storage niets teruggeeft moet de controller expliciet valideren en afbreken.
    expect(fn () => callSurveyManagerPrivateMethod($controller, 'normalizeOptions', [
        $request,
        0,
        'swipe',
        [
            ['label' => 'Links'],
        ],
    ]))->toThrow(ValidationException::class, 'De afbeelding kon niet worden opgeslagen. Probeer het opnieuw.');
});

it('deletes only local option images', function () {
    $controller = new SurveyManagerController();

    config(['filesystems.survey_images_disk' => 'survey_images']);
    Storage::fake('survey_images');

    Storage::disk('survey_images')->put('survey-options/local-image.jpg', 'content');

    callSurveyManagerPrivateMethod($controller, 'deleteOptionImages', [[
        ['label' => 'Lokale afbeelding', 'image' => 'survey-options/local-image.jpg'],
        ['label' => 'Externe afbeelding', 'image' => 'https://example.com/image.jpg'],
        ['label' => 'Leeg', 'image' => ''],
        'plain-string-option',
    ]]);

    Storage::disk('survey_images')->assertMissing('survey-options/local-image.jpg');
});

it('returns the survey overview view with filtered surveys and aggregate stats', function () {
    $controller = new SurveyManagerController();

    $matchingSurvey = Survey::factory()->active()->create([
        'title' => 'Matchende actieve enquete',
    ]);

    Survey::factory()->active()->create([
        'title' => 'Andere actieve enquete',
    ]);

    $closedSurvey = Survey::factory()->inactive()->create([
        'title' => 'Matchende gesloten enquete',
    ]);

    SurveyResponse::create([
        'survey_id' => $matchingSurvey->id,
        'withdrawal_token' => (string) str()->uuid(),
        'submitted_at' => now(),
    ]);

    SurveyResponse::create([
        'survey_id' => $closedSurvey->id,
        'withdrawal_token' => (string) str()->uuid(),
        'submitted_at' => now(),
    ]);

    $response = $controller->index(Request::create('/enquetes', 'GET', [
        'search' => 'Matchende',
        'status' => 'active',
    ]));

    $surveys = $response->getData()['surveys'];
    $stats = $response->getData()['stats'];

    expect($response->name())->toBe('survey-manager.index');
    expect($surveys->pluck('title')->all())->toBe([$matchingSurvey->title]);
    expect($stats)->toBe([
        'total' => 3,
        'active' => 2,
        'closed' => 1,
        'responses' => 2,
    ]);
});

it('stores a survey and its normalized questions', function () {
    $controller = new SurveyManagerController();

    $request = Mockery::mock(UpsertSurveyRequest::class);
    $request->shouldReceive('validated')->once()->andReturn([
        'title' => 'Nieuwe enquete',
        'description' => 'Beschrijving',
        'is_active' => '1',
        'reward_points' => 15,
        'questions' => [
            [
                'question' => '  Kies een optie  ',
                'type' => 'radio',
                'required' => '1',
                'options' => [
                    ['label' => ' Ja '],
                    ['label' => ' Nee '],
                ],
            ],
            [
                'question' => '  Licht toe  ',
                'type' => 'textarea',
                'options' => [],
            ],
        ],
    ]);
    $request->shouldReceive('hasFile')->andReturnFalse();

    $response = $controller->store($request);

    $survey = Survey::query()->firstOrFail();

    expect($response->getTargetUrl())->toBe(route('survey-manager.index'));
    expect($survey->title)->toBe('Nieuwe enquete');
    expect($survey->reward_points)->toBe(15);
    expect($survey->questions)->toHaveCount(2);
    expect($survey->questions[0]->options)->toBe(['Ja', 'Nee']);
    expect($survey->questions[1]->options)->toBeNull();
});

it('updates a survey by keeping, creating and deleting questions', function () {
    $controller = new SurveyManagerController();

    $survey = Survey::create([
        'title' => 'Oude titel',
        'description' => 'Oude beschrijving',
        'is_active' => true,
        'reward_points' => 10,
    ]);

    $existingQuestion = $survey->questions()->create([
        'question' => 'Oude vraag',
        'type' => 'radio',
        'required' => true,
        'sort_order' => 1,
        'options' => ['Ja', 'Nee'],
    ]);

    $removedQuestion = $survey->questions()->create([
        'question' => 'Te verwijderen',
        'type' => 'textarea',
        'required' => false,
        'sort_order' => 2,
        'options' => null,
    ]);

    $request = Mockery::mock(UpsertSurveyRequest::class);
    $request->shouldReceive('validated')->once()->andReturn([
        'title' => 'Nieuwe titel',
        'description' => null,
        'is_active' => '0',
        'reward_points' => 25,
        'questions' => [
            [
                'id' => $existingQuestion->id,
                'question' => '  Bijgewerkte vraag  ',
                'type' => 'radio',
                'required' => '1',
                'options' => [
                    ['label' => ' Eerste keuze '],
                    ['label' => ' Tweede keuze '],
                ],
            ],
            [
                'question' => ' Nieuwe vraag ',
                'type' => 'textarea',
                'options' => [],
            ],
        ],
    ]);
    $request->shouldReceive('hasFile')->andReturnFalse();

    $response = $controller->update($request, $survey);
    $updatedSurvey = $survey->fresh('questions');

    expect($response->getTargetUrl())->toBe(route('survey-manager.index'));
    expect($updatedSurvey->title)->toBe('Nieuwe titel');
    expect($updatedSurvey->is_active)->toBeFalse();
    expect($updatedSurvey->reward_points)->toBe(25);
    expect($updatedSurvey->questions)->toHaveCount(2);
    expect($updatedSurvey->questions[0]->id)->toBe($existingQuestion->id);
    expect($updatedSurvey->questions[0]->options)->toBe(['Eerste keuze', 'Tweede keuze']);
    expect($updatedSurvey->questions[1]->question)->toBe('Nieuwe vraag');
    expect($updatedSurvey->questions()->whereKey($removedQuestion->id)->exists())->toBeFalse();
});

it('rejects changing the type of an existing question once responses exist', function () {
    $controller = new SurveyManagerController();

    $survey = Survey::create([
        'title' => 'Bestaande enquete',
        'description' => 'Beschrijving',
        'is_active' => true,
        'reward_points' => 10,
    ]);

    $question = $survey->questions()->create([
        'question' => 'Bestaande vraag',
        'type' => 'radio',
        'required' => true,
        'sort_order' => 1,
        'options' => ['Ja', 'Nee'],
    ]);

    SurveyResponse::create([
        'survey_id' => $survey->id,
        'withdrawal_token' => (string) str()->uuid(),
        'submitted_at' => now(),
    ]);

    $request = Mockery::mock(UpsertSurveyRequest::class);
    $request->shouldReceive('validated')->once()->andReturn([
        'title' => 'Bestaande enquete',
        'description' => 'Beschrijving',
        'is_active' => '1',
        'reward_points' => 10,
        'questions' => [
            [
                'id' => $question->id,
                'question' => 'Bestaande vraag',
                'type' => 'textarea',
                'options' => [],
            ],
        ],
    ]);
    $request->shouldReceive('hasFile')->andReturnFalse();

    expect(fn () => $controller->update($request, $survey))
        ->toThrow(ValidationException::class, 'Je kunt het type van een bestaande vraag niet wijzigen zodra er reacties zijn ontvangen.');
});

it('returns the create view', function () {
    $response = (new SurveyManagerController())->create();

    expect($response->name())->toBe('survey-manager.create');
});

it('loads survey questions into the edit view', function () {
    $controller = new SurveyManagerController();

    $survey = Mockery::mock(Survey::class);
    $survey->shouldReceive('load')->once()->with('questions')->andReturnSelf();

    $response = $controller->edit($survey);

    expect($response->name())->toBe('survey-manager.edit');
    expect($response->getData()['survey'])->toBe($survey);
});

it('closes the survey and redirects back to the overview', function () {
    $controller = new SurveyManagerController();

    $survey = Mockery::mock(Survey::class);
    $survey->shouldReceive('update')->once()->with(['is_active' => false]);

    $response = $controller->close($survey);

    expect($response->getTargetUrl())->toBe(route('survey-manager.index'));
    expect(session('status'))->toContain('gesloten');
});
