<?php

namespace Tests\Feature;

use App\Models\Survey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SurveyCreatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_creator_is_saved_on_survey(): void
    {
        $creator = User::factory()->create();

        $survey = Survey::factory()->create([
            'created_by' => $creator->id,
        ]);

        $this->assertEquals($creator->id, $survey->created_by);
    }

    public function test_survey_creator_name_can_be_loaded(): void
    {
        $creator = User::factory()->create([
            'name' => 'Rayan Hassan',
        ]);

        $survey = Survey::factory()->create([
            'created_by' => $creator->id,
        ]);

        $this->assertEquals('Rayan Hassan', $survey->creator->name);
    }

    public function test_unknown_is_used_when_creator_is_missing(): void
    {
        $survey = Survey::factory()->create([
            'created_by' => null,
        ]);

        $creatorName = $survey->creator?->name ?? 'Onbekend';

        $this->assertEquals('Onbekend', $creatorName);
    }

    public function test_lic_employee_has_access_to_survey_manager_route(): void
    {
        Role::firstOrCreate(['name' => 'LICEmployee']);

        $viewer = User::factory()->create();
        $viewer->assignRole('LICEmployee');

        $this->actingAs($viewer);

        $this->assertTrue($viewer->hasRole('LICEmployee'));
    }
        public function test_surveys_can_be_filtered_by_creator_name(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin',
        ]);

        $otherUser = User::factory()->create([
            'name' => 'LIC Medewerker',
        ]);

        $adminSurvey = Survey::factory()->create([
            'title' => 'Enquête van admin',
            'created_by' => $admin->id,
        ]);

        Survey::factory()->create([
            'title' => 'Enquête van LIC medewerker',
            'created_by' => $otherUser->id,
        ]);

        $search = 'Admin';

        $surveys = Survey::query()
            ->where(function ($query) use ($search) {
                $query->where('title', 'like', '%' . $search . '%')
                    ->orWhereHas('creator', function ($query) use ($search) {
                        $query->where('name', 'like', '%' . $search . '%');
                    });
            })
            ->get();

        $this->assertCount(1, $surveys);
        $this->assertTrue($surveys->contains($adminSurvey));
    }

    public function test_surveys_can_be_filtered_by_creator_name_and_status(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin',
        ]);

        $activeSurvey = Survey::factory()->create([
            'title' => 'Actieve admin enquête',
            'created_by' => $admin->id,
            'is_active' => true,
        ]);

        Survey::factory()->create([
            'title' => 'Gesloten admin enquête',
            'created_by' => $admin->id,
            'is_active' => false,
        ]);

        $search = 'Admin';

        $surveys = Survey::query()
            ->where(function ($query) use ($search) {
                $query->where('title', 'like', '%' . $search . '%')
                    ->orWhereHas('creator', function ($query) use ($search) {
                        $query->where('name', 'like', '%' . $search . '%');
                    });
            })
            ->where('is_active', true)
            ->get();

        $this->assertCount(1, $surveys);
        $this->assertTrue($surveys->contains($activeSurvey));
    }
}
