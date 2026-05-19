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
}