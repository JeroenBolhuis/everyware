<?php

namespace Tests\Feature;

use App\Enums\Role as RoleEnum;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SurveyManagerTest extends TestCase
{
    use RefreshDatabase;

    private function fakePngUpload(string $name = 'test.png'): UploadedFile
    {
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a2uoAAAAASUVORK5CYII=',
            true
        );

        return UploadedFile::fake()->createWithContent($name, $png ?: '');
    }

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

    public function test_swipe_question_with_image_can_be_created_without_alt_text(): void
    {
        config(['filesystems.survey_images_disk' => 'survey_images']);
        Storage::fake('survey_images');

        $this->actingAsSurveyManager();

        $response = $this->post(route('survey-manager.store'), [
            'title' => 'Swipe met afbeelding',
            'description' => 'Beschrijving',
            'is_active' => '1',
            'reward_points' => 10,
            'questions' => [
                [
                    'question' => 'Kies een afbeelding',
                    'type' => 'swipe',
                    'required' => '1',
                    'options' => [
                        [
                            'label' => 'Links',
                            'image' => $this->fakePngUpload('links.png'),
                            'image_alt' => '',
                        ],
                        [
                            'label' => 'Rechts',
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect(route('survey-manager.index'));

        $question = Survey::where('title', 'Swipe met afbeelding')
            ->firstOrFail()
            ->questions()
            ->firstOrFail();

        expect($question->options[0]['image'])->not->toBeNull()
            ->and($question->options[0]['image_alt'])->toBeNull();
    }

    public function test_swipe_question_stores_image_alt_text(): void
    {
        config(['filesystems.survey_images_disk' => 'survey_images']);
        Storage::fake('survey_images');

        $this->actingAsSurveyManager();

        $response = $this->post(route('survey-manager.store'), [
            'title' => 'Swipe met alt tekst',
            'description' => 'Beschrijving',
            'is_active' => '1',
            'reward_points' => 10,
            'questions' => [
                [
                    'question' => 'Kies een afbeelding',
                    'type' => 'swipe',
                    'required' => '1',
                    'options' => [
                        [
                            'label' => 'Links',
                            'image' => $this->fakePngUpload('links.png'),
                            'image_alt' => 'Student kijkt glimlachend naar links',
                        ],
                        [
                            'label' => 'Rechts',
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect(route('survey-manager.index'));

        $question = Survey::where('title', 'Swipe met alt tekst')
            ->firstOrFail()
            ->questions()
            ->firstOrFail();

        expect($question->options[0]['image_alt'])->toBe('Student kijkt glimlachend naar links')
            ->and($question->options[0]['image'])->not->toBeNull();
    }

    public function test_survey_creation_uses_ten_reward_points_when_field_is_left_empty(): void
    {
        $this->actingAsSurveyManager();

        $response = $this->post(route('survey-manager.store'), [
            'title' => 'Standaard punten enquête',
            'description' => 'Beschrijving',
            'is_active' => '1',
            'reward_points' => '',
            'questions' => [
                [
                    'question' => 'Wat vind je ervan?',
                    'type' => 'radio',
                    'required' => '1',
                    'options' => [
                        ['label' => 'Ja'],
                        ['label' => 'Nee'],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect(route('survey-manager.index'));

        $this->assertDatabaseHas('surveys', [
            'title' => 'Standaard punten enquête',
            'reward_points' => 10,
        ]);
    }

    public function test_new_surveys_receive_ten_reward_points_by_default(): void
    {
        $survey = Survey::factory()->create();

        expect($survey->reward_points)->toBe(10);
    }

    public function test_survey_overview_shows_export_link_per_survey(): void
    {
        $this->actingAsSurveyManager();

        $survey = Survey::factory()->create([
            'title' => 'Exporteerbare enquete',
            'is_active' => true,
        ]);

        $response = $this->get(route('survey-manager.index'));

        $response
            ->assertOk()
            ->assertSee('Exporteer')
            ->assertSee('Excel (.xlsx)')
            ->assertSee('CSV (.csv)')
            ->assertSee(route('admin.surveys.export', ['survey' => $survey, 'format' => 'xlsx']), false)
            ->assertSee(route('admin.surveys.export', ['survey' => $survey, 'format' => 'csv']), false);
    }

    public function test_existing_swipe_images_are_kept_when_question_type_does_not_change(): void
    {
        config(['filesystems.survey_images_disk' => 'survey_images']);
        Storage::fake('survey_images');

        $this->actingAsSurveyManager();

        $oldImage = 'survey-options/old-image.jpg';
        Storage::disk('survey_images')->put($oldImage, 'fake-image');

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
                ['label' => 'Links', 'image' => $oldImage, 'image_alt' => 'Bestaande linker afbeelding'],
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
                        ['label' => 'Links', 'existing_image' => $oldImage, 'image_alt' => 'Bestaande linker afbeelding'],
                        ['label' => 'Rechts'],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect(route('survey-manager.index'));
        Storage::disk('survey_images')->assertExists($oldImage);

        expect($question->fresh()->options)->toBe([
            ['label' => 'Links', 'image' => $oldImage, 'image_alt' => 'Bestaande linker afbeelding'],
            ['label' => 'Rechts', 'image' => null, 'image_alt' => null],
        ]);
    }

    public function test_existing_swipe_images_are_deleted_when_question_type_changes(): void
    {
        config(['filesystems.survey_images_disk' => 'survey_images']);
        Storage::fake('survey_images');

        $this->actingAsSurveyManager();

        $oldImage = 'survey-options/old-image.jpg';
        Storage::disk('survey_images')->put($oldImage, 'fake-image');

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
                ['label' => 'Links', 'image' => $oldImage, 'image_alt' => 'Bestaande linker afbeelding'],
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
                        ['label' => 'Links', 'existing_image' => $oldImage, 'image_alt' => 'Bestaande linker afbeelding'],
                        ['label' => 'Rechts'],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect(route('survey-manager.index'));
        Storage::disk('survey_images')->assertMissing($oldImage);

        expect($question->fresh()->options)->toBe([
            'Links',
            'Rechts',
        ]);
    }
}
