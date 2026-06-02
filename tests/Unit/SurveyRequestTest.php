<?php

use App\Http\Requests\Surveys\RequestParticipantMagicLinkRequest;
use App\Http\Requests\Surveys\StoreSurveyResponseRequest;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\HttpResponseException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function callSurveyRequestProtectedMethod(object $object, string $method): mixed
{
    return (fn () => $this->{$method}())->call($object);
}

function requestRouteWithParameter(string $name, mixed $value): object
{
    return new class($name, $value)
    {
        public function __construct(
            private readonly string $name,
            private readonly mixed $value,
        ) {}

        public function parameter(string $name, mixed $default = null): mixed
        {
            return $name === $this->name ? $this->value : $default;
        }
    };
}

it('normalizes participant magic link emails before validation', function () {
    $request = RequestParticipantMagicLinkRequest::create('/survey/deelnemer/inloggen', 'POST', [
        'email' => '  JAMIE@EXAMPLE.COM  ',
        'redirect' => '/student/punten',
    ]);

    callSurveyRequestProtectedMethod($request, 'prepareForValidation');

    expect($request->authorize())->toBeTrue()
        ->and($request->input('email'))->toBe('jamie@example.com')
        ->and($request->rules())->toHaveKeys(['email', 'redirect'])
        ->and($request->messages()['email.email'])->toBe('Vul een geldig e-mailadres in.');
});

it('authorizes survey responses while a survey is open and builds answer rules', function () {
    $survey = Survey::factory()->active()->createOne();
    $requiredQuestion = SurveyQuestion::factory()->createOne([
        'survey_id' => $survey->id,
        'required' => true,
    ]);
    $optionalQuestion = SurveyQuestion::factory()->createOne([
        'survey_id' => $survey->id,
        'required' => false,
    ]);
    $request = StoreSurveyResponseRequest::create('/survey/'.$survey->id, 'POST');
    $request->setRouteResolver(fn () => requestRouteWithParameter('survey', $survey));

    expect($request->authorize())->toBeTrue()
        ->and($request->rules())->toBe([
            'answers' => ['required', 'array'],
            "answers.{$requiredQuestion->id}" => ['required'],
            "answers.{$optionalQuestion->id}" => ['nullable'],
        ]);
});

it('finds surveys by token when building survey response rules', function () {
    $survey = Survey::factory()->active()->createOne([
        'share_token' => 'share-token',
    ]);
    $question = SurveyQuestion::factory()->createOne([
        'survey_id' => $survey->id,
        'required' => true,
    ]);
    $request = StoreSurveyResponseRequest::create('/s/share-token', 'POST');
    $request->setRouteResolver(fn () => requestRouteWithParameter('token', 'share-token'));

    expect($request->authorize())->toBeTrue()
        ->and($request->rules())->toHaveKey("answers.{$question->id}");
});

it('rejects ended surveys with an expired survey response', function () {
    $survey = Survey::factory()->active()->createOne([
        'ends_at' => today()->subDay(),
    ]);
    $request = StoreSurveyResponseRequest::create('/survey/'.$survey->id, 'POST');
    $request->setRouteResolver(fn () => requestRouteWithParameter('survey', $survey));

    expect($request->authorize())->toBeFalse();
    expect(fn () => callSurveyRequestProtectedMethod($request, 'failedAuthorization'))
        ->toThrow(HttpResponseException::class);
});
