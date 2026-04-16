<?php

namespace Tests\Feature;

use App\Enums\Role as RoleEnum;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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
            'reward_points' => 10,
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
            'reward_points' => 10,
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
            'reward_points' => 10,
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
            'reward_points' => 10,
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

    public function test_new_surveys_receive_ten_reward_points_by_default(): void
    {
        $survey = Survey::factory()->create();

        expect($survey->reward_points)->toBe(10);
    }

    public function test_existing_swipe_images_are_kept_when_question_type_does_not_change(): void
    {
        Storage::fake('public');

        $this->actingAsSurveyManager();

        $oldImage = 'survey-options/old-image.jpg';
        Storage::disk('public')->put($oldImage, 'fake-image');

        $survey = Survey::create([
            'title' => 'Bestaande enquete',
            'description' => 'Beschrijving',
            'is_active' => true,
        ]);

        $question = $survey->questions()->create([
            'question' => 'Kies een optie',
            'type' => 'swipe',
            'required' => true,
            'sort_order' => 1,
            'options' => [
                ['label' => 'Links', 'image' => $oldImage],
                ['label' => 'Rechts', 'image' => null],
            ],
        ]);

        $response = $this->put(route('survey-manager.update', $survey), [
            'title' => 'Bestaande enquete',
            'description' => 'Aangepast',
            'is_active' => '1',
            'reward_points' => 10,
            'questions' => [
                [
                    'id' => $question->id,
                    'question' => 'Kies een optie',
                    'type' => 'swipe',
                    'required' => '1',
                    'options' => [
                        ['label' => 'Links', 'existing_image' => $oldImage],
                        ['label' => 'Rechts'],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect(route('survey-manager.index'));
        Storage::disk('public')->assertExists($oldImage);

        expect($question->fresh()->options)->toBe([
            ['label' => 'Links', 'image' => $oldImage],
            ['label' => 'Rechts', 'image' => null],
        ]);
    }

    public function test_existing_swipe_images_are_deleted_when_question_type_changes(): void
    {
        Storage::fake('public');

        $this->actingAsSurveyManager();

        $oldImage = 'survey-options/old-image.jpg';
        Storage::disk('public')->put($oldImage, 'fake-image');

        $survey = Survey::create([
            'title' => 'Bestaande enquete',
            'description' => 'Beschrijving',
            'is_active' => true,
        ]);

        $question = $survey->questions()->create([
            'question' => 'Kies een optie',
            'type' => 'swipe',
            'required' => true,
            'sort_order' => 1,
            'options' => [
                ['label' => 'Links', 'image' => $oldImage],
                ['label' => 'Rechts', 'image' => null],
            ],
        ]);

        $response = $this->put(route('survey-manager.update', $survey), [
            'title' => 'Bestaande enquete',
            'description' => 'Aangepast',
            'is_active' => '1',
            'reward_points' => 10,
            'questions' => [
                [
                    'id' => $question->id,
                    'question' => 'Kies een optie',
                    'type' => 'radio',
                    'required' => '1',
                    'options' => [
                        ['label' => 'Links', 'existing_image' => $oldImage],
                        ['label' => 'Rechts'],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect(route('survey-manager.index'));
        Storage::disk('public')->assertMissing($oldImage);

        expect($question->fresh()->options)->toBe([
            'Links',
            'Rechts',
        ]);
    }
}
