<?php

use App\Models\ContactInformationSubmission;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use function Pest\Laravel\from;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

function createSurveyWithQuestion(): Survey
{
    $survey = Survey::factory()->active()->create();

    SurveyQuestion::factory()->for($survey)->create([
        'question' => 'Wat vind je van deze module?',
        'type' => 'textarea',
        'options' => null,
        'required' => true,
        'sort_order' => 1,
    ]);

    return $survey;
}

it('does not show the thank-you contact form on the survey page', function () {
    $survey = createSurveyWithQuestion();

    get(route('survey.show', $survey))
        ->assertOk()
        ->assertSee('Wat vind je van deze module?')
        ->assertDontSee('Contactgegevens opslaan');
});

it('shows optional contact fields on the thank you page', function () {
    $survey = createSurveyWithQuestion();
    $question = $survey->questions()->firstOrFail();

    post(route('survey.store', $survey), [
        'answers' => [
            $question->id => 'Prima module.',
        ],
    ])->assertRedirect();

    $response = SurveyResponse::firstOrFail();

    get(route('survey.thankyou', $response))
        ->assertOk()
        ->assertSee('Contactgegevens')
        ->assertSee('E-mailadres')
        ->assertSee('Je telefoonnummer is optioneel')
        ->assertSee('Contactgegevens opslaan');
});

it('submits survey without contact details', function () {
    $survey = createSurveyWithQuestion();
    $question = $survey->questions()->firstOrFail();

    post(route('survey.store', $survey), [
        'answers' => [
            $question->id => 'Prima module.',
        ],
    ])->assertRedirect();

    $response = SurveyResponse::first();

    expect($response)->not->toBeNull();
    expect(ContactInformationSubmission::count())->toBe(0);

    get(route('survey.thankyou', $response))
        ->assertOk()
        ->assertSee('Wil je dat we contact met je opnemen? Vul hieronder je naam en e-mailadres in. Je telefoonnummer is optioneel.');
});

it('stores encrypted contact details on the thank you page when provided', function () {
    $survey = createSurveyWithQuestion();
    $question = $survey->questions()->firstOrFail();

    post(route('survey.store', $survey), [
        'answers' => [
            $question->id => 'Erg nuttig.',
        ],
    ])->assertRedirect();

    $response = SurveyResponse::firstOrFail();

    from(route('survey.thankyou', $response))
        ->post(route('survey.contact-details.store', $response), [
            'contact_name' => 'Jamie Jansen',
            'contact_email' => 'jamie@example.com',
            'contact_phone' => '+31 6 12345678',
        ])
        ->assertRedirect(route('survey.thankyou', $response));

    $contactSubmission = ContactInformationSubmission::firstOrFail();

    expect($contactSubmission->survey_id)->toBe($survey->id)
        ->and($contactSubmission->survey_response_id)->toBe($response->id)
        ->and($contactSubmission->name)->toBe('Jamie Jansen')
        ->and($contactSubmission->email)->toBe('jamie@example.com')
        ->and($contactSubmission->phone)->toBe('+31612345678');

    expect($contactSubmission->getRawOriginal('name'))->not->toBe('Jamie Jansen');
    expect($contactSubmission->getRawOriginal('email'))->not->toBe('jamie@example.com');
    expect($contactSubmission->getRawOriginal('phone'))->not->toBe('+31612345678');

    get(route('survey.thankyou', $response))
        ->assertOk()
        ->assertSee('Je hebt contactgegevens gedeeld.')
        ->assertSee('Naam opgeslagen')
        ->assertSee('E-mailadres opgeslagen')
        ->assertSee('Telefoonnummer opgeslagen')
        ->assertSee('versleuteld opgeslagen');
});

it('validates optional contact details when provided', function () {
    $survey = createSurveyWithQuestion();
    $question = $survey->questions()->firstOrFail();

    post(route('survey.store', $survey), [
        'answers' => [
            $question->id => 'Goede lesstof.',
        ],
    ])->assertRedirect();

    $response = SurveyResponse::firstOrFail();

    from(route('survey.thankyou', $response))
        ->post(route('survey.contact-details.store', $response), [
            'contact_email' => 'geen-geldig-emailadres',
            'contact_phone' => 'abc',
        ])
        ->assertRedirect(route('survey.thankyou', $response))
        ->assertSessionHasErrors(['contact_email', 'contact_phone']);

    expect(ContactInformationSubmission::count())->toBe(0);
});
