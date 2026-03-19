<?php

namespace Database\Seeders;

use App\Models\Survey;
use Illuminate\Database\Seeder;

class SurveySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $survey = Survey::create([
            'title' => 'Studentenenquete',
            'description' => 'Periodieke enquête voor studentfeedback',
            'is_active' => true,
        ]);

        $questions = [
            [
                'question_id' => 'ervaring',
                'prompt' => 'Hoe ervaar je de huidige lessen tot nu toe?',
                'description' => 'Beschrijf kort wat goed gaat en wat volgens jou beter kan.',
                'placeholder' => 'Bijvoorbeeld: de uitleg is duidelijk, maar het tempo ligt hoog...',
                'order' => 1,
            ],
            [
                'question_id' => 'belasting',
                'prompt' => 'Hoe ervaar je de studielast van deze periode?',
                'description' => 'Denk aan opdrachten, lessen en voorbereiding buiten de contacturen.',
                'placeholder' => 'Bijvoorbeeld: goed te doen, soms piekdruk, of juist te licht...',
                'order' => 2,
            ],
            [
                'question_id' => 'begeleiding',
                'prompt' => 'Voel je je voldoende begeleid door docenten en studiebegeleiding?',
                'description' => 'Geef aan wat voor jou helpt of nog ontbreekt.',
                'placeholder' => 'Bijvoorbeeld: snelle feedback helpt, of ik mis vaste contactmomenten...',
                'order' => 3,
            ],
            [
                'question_id' => 'verbetering',
                'prompt' => 'Welke ene verbetering zou voor jou de grootste impact hebben?',
                'description' => 'Noem de belangrijkste verandering die je graag terugziet.',
                'placeholder' => 'Bijvoorbeeld: duidelijkere planning, meer oefenmateriaal of extra uitleg...',
                'order' => 4,
            ],
        ];

        foreach ($questions as $question) {
            $survey->questions()->create($question);
        }
    }
}
