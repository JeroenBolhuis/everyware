<?php

use App\Models\ContactInformationSubmission;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use Illuminate\Support\Facades\Hash;

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

it('hides optional contact fields on the survey page', function () {
    $survey = createSurveyWithQuestion();

    $this->get(route('survey.show', $survey))
        ->assertOk()
        ->assertDontSee('E-mailadres')
        ->assertDontSee('Telefoonnummer')
        ->assertDontSee('Contactgegevens');
});

it('shows optional contact fields on the thank you page', function () {
    $survey = createSurveyWithQuestion();
    $question = $survey->questions()->firstOrFail();

    $this->post(route('survey.store', $survey), [
        'answers' => [
            $question->id => 'Prima module.',
        ],
    ])->assertRedirect();

    $response = SurveyResponse::firstOrFail();

    $this->get(route('survey.thankyou', $response))
        ->assertOk()
        ->assertSee('Contactgegevens')
        ->assertSee('(optioneel)')
        ->assertSee('E-mailadres')
        ->assertSee('Telefoonnummer')
        ->assertSee('Contactgegevens opslaan');
});

it('submits survey without contact details', function () {
    $survey = createSurveyWithQuestion();
    $question = $survey->questions()->firstOrFail();

    $this->post(route('survey.store', $survey), [
        'answers' => [
            $question->id => 'Prima module.',
        ],
    ])->assertRedirect();

    $response = SurveyResponse::first();

    expect($response)->not->toBeNull();
    expect(ContactInformationSubmission::count())->toBe(0);

    $this->get(route('survey.thankyou', $response))
        ->assertOk()
        ->assertSee('Wil je dat we contact met je opnemen? Laat hieronder optioneel je contactgegevens achter.');
});

it('stores hashed contact details on the thank you page when provided', function () {
    $survey = createSurveyWithQuestion();
    $question = $survey->questions()->firstOrFail();

    $this->post(route('survey.store', $survey), [
        'answers' => [
            $question->id => 'Erg nuttig.',
        ],
    ])->assertRedirect();

    $response = SurveyResponse::firstOrFail();

    $this->from(route('survey.thankyou', $response))
        ->post(route('survey.contact-details.store', $response), [
            'contact_name' => 'Jamie Jansen',
            'contact_email' => 'jamie@example.com',
            'contact_phone' => '+31 6 12345678',
        ])
        ->assertRedirect(route('survey.thankyou', $response));

    $contactSubmission = ContactInformationSubmission::firstOrFail();

    expect($contactSubmission->survey_id)->toBe($survey->id)
        ->and($contactSubmission->survey_response_id)->toBe($response->id)
        ->and($contactSubmission->name)->not->toBe('Jamie Jansen')
        ->and($contactSubmission->email)->not->toBe('jamie@example.com')
        ->and($contactSubmission->phone)->not->toBe('+31 6 12345678');

    expect(Hash::check('Jamie Jansen', $contactSubmission->name))->toBeTrue();
    expect(Hash::check('jamie@example.com', $contactSubmission->email))->toBeTrue();
    expect(Hash::check('+31612345678', $contactSubmission->phone))->toBeTrue();

    $this->get(route('survey.thankyou', $response))
        ->assertOk()
        ->assertSee('Je hebt contactgegevens gedeeld.')
        ->assertSee('Naam opgeslagen')
        ->assertSee('E-mailadres opgeslagen')
        ->assertSee('Telefoonnummer opgeslagen')
        ->assertSee('gehasht opgeslagen');
});

it('validates optional contact details when provided', function () {
    $survey = createSurveyWithQuestion();
    $question = $survey->questions()->firstOrFail();

    $this->post(route('survey.store', $survey), [
        'answers' => [
            $question->id => 'Goede lesstof.',
        ],
    ])->assertRedirect();

    $response = SurveyResponse::firstOrFail();

    $this->from(route('survey.thankyou', $response))
        ->post(route('survey.contact-details.store', $response), [
            'contact_email' => 'geen-geldig-emailadres',
            'contact_phone' => 'abc',
        ])
        ->assertRedirect(route('survey.thankyou', $response))
        ->assertSessionHasErrors(['contact_email', 'contact_phone']);

    expect(ContactInformationSubmission::count())->toBe(0);
});
