<?php

namespace Tests\Feature;

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SurveyManagerTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsSurveyManager(): self
    {
        Role::findOrCreate(RoleEnum::Admin->value, 'web');

        $user = User::factory()->create();
        $user->assignRole(RoleEnum::Admin->value);

        $this->actingAs($user);

        return $this;
    }

    public function test_survey_can_be_created_with_radio_question_and_separate_options(): void
    {
        $this->actingAsSurveyManager();

        $response = $this->post(route('survey-manager.store'), [
            'title' => 'Test enquête',
            'description' => 'Beschrijving',
            'is_active' => '1',
            'questions' => [
                [
                    'question' => 'Wat vind je ervan?',
                    'type' => 'radio',
                    'required' => '1',
                    'options' => [
                        ['label' => 'Ja'],
                        ['label' => 'Nee'],
                        ['label' => 'Misschien'],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect(route('survey-manager.index'));

        $this->assertDatabaseHas('surveys', [
            'title' => 'Test enquête',
        ]);

        $this->assertDatabaseHas('survey_questions', [
            'question' => 'Wat vind je ervan?',
            'type' => 'radio',
        ]);
    }

    public function test_swipe_question_fails_with_more_than_two_options(): void
    {
        $this->actingAsSurveyManager();

        $response = $this->from(route('survey-manager.create'))->post(route('survey-manager.store'), [
            'title' => 'Swipe test',
            'description' => 'Beschrijving',
            'is_active' => '1',
            'questions' => [
                [
                    'question' => 'Kies door te swipen',
                    'type' => 'swipe',
                    'required' => '1',
                    'options' => [
                        ['label' => 'Optie 1'],
                        ['label' => 'Optie 2'],
                        ['label' => 'Optie 3'],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect(route('survey-manager.create'));
        $response->assertSessionHasErrors('questions.0.options');
    }

    public function test_swipe_question_fails_with_less_than_two_options(): void
    {
        $this->actingAsSurveyManager();

        $response = $this->from(route('survey-manager.create'))->post(route('survey-manager.store'), [
            'title' => 'Swipe test',
            'description' => 'Beschrijving',
            'is_active' => '1',
            'questions' => [
                [
                    'question' => 'Kies door te swipen',
                    'type' => 'swipe',
                    'required' => '1',
                    'options' => [
                        ['label' => 'Optie 1'],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect(route('survey-manager.create'));
        $response->assertSessionHasErrors('questions.0.options');
    }

    public function test_textarea_question_can_be_created_without_options(): void
    {
        $this->actingAsSurveyManager();

        $response = $this->post(route('survey-manager.store'), [
            'title' => 'Open vraag enquête',
            'description' => 'Beschrijving',
            'is_active' => '1',
            'questions' => [
                [
                    'question' => 'Vertel je mening',
                    'type' => 'textarea',
                    'required' => '1',
                    'options' => [],
                ],
            ],
        ]);

        $response->assertRedirect(route('survey-manager.index'));

        $this->assertDatabaseHas('survey_questions', [
            'question' => 'Vertel je mening',
            'type' => 'textarea',
        ]);
    }
}