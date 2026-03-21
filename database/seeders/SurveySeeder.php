<?php

namespace Database\Seeders;

use App\Models\Survey;
use Illuminate\Database\Seeder;

class SurveySeeder extends Seeder
{
    public function run(): void
    {
        $surveys = [
            [
                'title' => 'Studenttevredenheid',
                'description' => 'Geef snel je mening over jouw ervaring.',
                'is_active' => true,
                'questions' => [
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
                            'nee',
                            'ja',
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
                ],
            ],
            [
                'title' => 'Docent Evaluatie',
                'description' => 'Help ons verbeteren door je feedback te geven over de docenten.',
                'is_active' => true,
                'questions' => [
                    [
                        'question' => 'Hoe beoordeel je de kennis van de docent?',
                        'type' => 'radio',
                        'options' => [
                            'Uitstekend',
                            'Goed',
                            'Voldoende',
                            'Onvoldoende',
                            'Slecht',
                        ],
                        'required' => true,
                        'sort_order' => 1,
                    ],
                    [
                        'question' => 'Was de docent duidelijk in zijn uitleg?',
                        'type' => 'swipe',
                        'options' => ['nee', 'ja'],
                        'required' => true,
                        'sort_order' => 2,
                    ],
                    [
                        'question' => 'Wat vond je van de lesmethode?',
                        'type' => 'textarea',
                        'options' => null,
                        'required' => false,
                        'sort_order' => 3,
                    ],
                ],
            ],
            [
                'title' => 'Campus Faciliteiten',
                'description' => 'Deel je ervaringen met de campus faciliteiten.',
                'is_active' => false,
                'questions' => [
                    [
                        'question' => 'Hoe tevreden ben je met de bibliotheek?',
                        'type' => 'radio',
                        'options' => [
                            'Zeer tevreden',
                            'Tevreden',
                            'Neutraal',
                            'Ontevreden',
                            'Zeer ontevreden',
                        ],
                        'required' => true,
                        'sort_order' => 1,
                    ],
                    [
                        'question' => 'Zijn de studieplekken voldoende?',
                        'type' => 'swipe',
                        'options' => ['nee', 'ja'],
                        'required' => true,
                        'sort_order' => 2,
                    ],
                    [
                        'question' => 'Suggesties voor verbetering?',
                        'type' => 'textarea',
                        'options' => null,
                        'required' => false,
                        'sort_order' => 3,
                    ],
                ],
            ],
            [
                'title' => 'Algemene Studie Ervaring',
                'description' => 'Vertel ons over je algehele studie ervaring.',
                'is_active' => true,
                'questions' => [
                    [
                        'question' => 'Hoe zou je je motivatie niveau beschrijven?',
                        'type' => 'radio',
                        'options' => [
                            'Zeer gemotiveerd',
                            'Gemotiveerd',
                            'Neutraal',
                            'Ongemotiveerd',
                            'Zeer ongelikteerd',
                        ],
                        'required' => true,
                        'sort_order' => 1,
                    ],
                    [
                        'question' => 'Voel je je ondersteund door medestudenten?',
                        'type' => 'swipe',
                        'options' => ['nee', 'ja'],
                        'required' => true,
                        'sort_order' => 2,
                    ],
                    [
                        'question' => 'Wat is je favoriete aspect van de studie?',
                        'type' => 'textarea',
                        'options' => null,
                        'required' => false,
                        'sort_order' => 3,
                    ],
                    [
                        'question' => 'Wat zou je willen verbeteren?',
                        'type' => 'textarea',
                        'options' => null,
                        'required' => false,
                        'sort_order' => 4,
                    ],
                ],
            ],
        ];

        foreach ($surveys as $surveyData) {
            $survey = Survey::create([
                'title' => $surveyData['title'],
                'description' => $surveyData['description'],
                'is_active' => $surveyData['is_active'],
            ]);

            $survey->questions()->createMany($surveyData['questions']);
        }
    }
}
