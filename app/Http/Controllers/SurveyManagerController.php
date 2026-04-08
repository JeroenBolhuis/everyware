<?php

namespace App\Http\Controllers;

use App\Http\Requests\Surveys\UpsertSurveyRequest;
use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SurveyManagerController extends Controller
{
    public function index(Request $request)
    {
        $query = Survey::query()
            ->withCount(['questions', 'responses'])
            ->latest();

        if ($request->filled('search')) {
            $query->where('title', 'like', '%'.$request->string('search').'%');
        }

        $status = $request->string('status')->toString();

        if ($status === 'active') {
            $query->where('is_active', true);
        }

        if ($status === 'closed') {
            $query->where('is_active', false);
        }

        $surveys = $query->paginate(10)->withQueryString();

        $stats = [
            'total' => Survey::count(),
            'active' => Survey::where('is_active', true)->count(),
            'closed' => Survey::where('is_active', false)->count(),
            'responses' => DB::table('survey_responses')->count(),
        ];

        return view('survey-manager.index', compact('surveys', 'stats'));
    }

    public function create()
    {
        return view('survey-manager.create');
    }

    public function store(UpsertSurveyRequest $request)
    {
        $validated = $request->validated();
        $questions = $this->buildQuestionsPayload($validated['questions']);

        DB::transaction(function () use ($validated, $questions): void {
            $survey = Survey::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'is_active' => (bool) $validated['is_active'],
            ]);

            $survey->questions()->createMany(
                collect($questions)
                    ->map(fn (array $question) => Arr::except($question, ['id']))
                    ->all()
            );
        });

        return to_route('survey-manager.index')->with('status', 'Enquête succesvol aangemaakt.');
    }

    public function edit(Survey $survey)
    {
        $survey->load('questions');

        return view('survey-manager.edit', compact('survey'));
    }

    public function update(UpsertSurveyRequest $request, Survey $survey)
    {
        $validated = $request->validated();
        $questions = $this->buildQuestionsPayload($validated['questions']);

        DB::transaction(function () use ($survey, $validated, $questions): void {
            $survey->update([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'is_active' => (bool) $validated['is_active'],
            ]);

            $existingQuestions = $survey->questions()->get()->keyBy('id');
            $keepIds = [];
            $hasResponses = $survey->responses()->exists();

            foreach ($questions as $question) {
                $questionId = $question['id'] ?? null;
                $attributes = Arr::except($question, ['id']);

                if ($questionId !== null && $existingQuestions->has($questionId)) {
                    $existingQuestion = $existingQuestions->get($questionId);

                    if ($hasResponses && $existingQuestion->type !== $attributes['type']) {
                        throw ValidationException::withMessages([
                            'questions' => 'Je kunt het type van een bestaande vraag niet wijzigen zodra er reacties zijn ontvangen.',
                        ]);
                    }

                    $existingQuestion->update($attributes);
                    $keepIds[] = $existingQuestion->id;

                    continue;
                }

                $createdQuestion = $survey->questions()->create($attributes);
                $keepIds[] = $createdQuestion->id;
            }

            $questionIdsToDelete = $existingQuestions->keys()->diff($keepIds);

            if ($questionIdsToDelete->isNotEmpty()) {
                if ($hasResponses) {
                    throw ValidationException::withMessages([
                        'questions' => 'Je kunt bestaande vragen niet verwijderen zodra er reacties zijn ontvangen.',
                    ]);
                }

                $survey->questions()->whereIn('id', $questionIdsToDelete)->delete();
            }
        });

        return to_route('survey-manager.index')->with('status', 'Enquête succesvol bijgewerkt.');
    }

    public function close(Survey $survey)
    {
        $survey->update(['is_active' => false]);

        return to_route('survey-manager.index')->with('status', 'De enquête is gesloten en kan niet meer worden ingevuld.');
    }

    private function buildQuestionsPayload(array $questions): array
    {
        return collect($questions)
            ->values()
            ->map(function (array $question, int $index): array {
                return [
                    'id' => isset($question['id']) ? (int) $question['id'] : null,
                    'question' => trim($question['question']),
                    'type' => $question['type'],
                    'options' => $this->normalizeOptions($question['type'], $question['options'] ?? null),
                    'required' => (bool) ($question['required'] ?? false),
                    'sort_order' => $index + 1,
                ];
            })
            ->all();
    }

    private function normalizeOptions(string $type, ?string $options): ?array
    {
        if ($type === 'textarea') {
            return null;
        }

        $parsedOptions = collect(explode(',', (string) $options))
            ->map(fn (string $option) => trim($option))
            ->filter()
            ->values()
            ->all();

        if ($type === 'swipe' && count($parsedOptions) < 2) {
            return ['nee', 'ja'];
        }

        return $parsedOptions;
    }
}
