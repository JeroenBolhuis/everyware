<?php

namespace Database\Seeders;

use App\Models\Survey;
use Illuminate\Database\Seeder;

class SurveySeeder extends Seeder
{
    public function run(): void
    {
        $survey = Survey::create([
            'title' => 'Studenttevredenheid',
            'description' => 'Geef snel je mening over jouw ervaring.',
            'is_active' => true,
        ]);

        $survey->questions()->createMany([
            [
                'question' => 'Hoe duidelijk was deze module?',
                'type' => 'radio',
                'options' => [
                    'Helemaal duidelijk',
                    'Redelijk duidelijk',
                    'Neutraal',
                    'Onvoldoende duidelijk',
                    'Heel onduidelijk',
                ],
                'required' => true,
                'sort_order' => 1,
            ],
            [
                'question' => 'Hoe duidelijk waren de opdrachten?',
                'type' => 'radio',
                'options' => [
                    'Helemaal duidelijk',
                    'Redelijk duidelijk',
                    'Neutraal',
                    'Onvoldoende duidelijk',
                    'Heel onduidelijk',
                ],
                'required' => true,
                'sort_order' => 2,
            ],
            [
                'question' => 'Hoe duidelijk waren de uitlegmomenten?',
                'type' => 'radio',
                'options' => [
                    'Helemaal duidelijk',
                    'Redelijk duidelijk',
                    'Neutraal',
                    'Onvoldoende duidelijk',
                    'Heel onduidelijk',
                ],
                'required' => true,
                'sort_order' => 3,
            ],
            [
                'question' => 'Zou je deze opleiding aanraden aan andere studenten?',
                'type' => 'swipe',
                'options' => [
                    'ja',
                    'nee',
                ],
                'required' => true,
                'sort_order' => 4,
            ],
            [
                'question' => 'Heb je nog een opmerking of suggestie?',
                'type' => 'textarea',
                'options' => null,
                'required' => false,
                'sort_order' => 5,
            ],
        ]);
    }
}