<?php

namespace App\Http\Controllers;

use App\Http\Requests\Surveys\UpsertSurveyRequest;
use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SurveyManagerController extends Controller
{
    private function surveyImagesDisk(): string
    {
        return (string) config('filesystems.survey_images_disk', 'public');
    }

    private function normalizeExistingImage(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function isAbsoluteUrl(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    public function index(Request $request)
    {
        $query = Survey::query()
            ->withCount(['questions', 'responses'])
            ->latest();

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->string('search') . '%');
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
        $questions = $this->buildQuestionsPayload($request, $validated['questions']);

        DB::transaction(function () use ($validated, $questions): void {
            $survey = Survey::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'is_active' => (bool)$validated['is_active'],
                'reward_points' => $validated['reward_points'],
            ]);

            $survey->questions()->createMany(
                collect($questions)
                    ->map(fn(array $question) => Arr::except($question, ['id']))
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
        $questions = $this->buildQuestionsPayload($request, $validated['questions']);

        DB::transaction(function () use ($survey, $validated, $questions): void {
            $survey->update([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'is_active' => (bool)$validated['is_active'],
                'reward_points' => $validated['reward_points'],
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

                    if ($existingQuestion->type !== $attributes['type']) {
                        $this->deleteOptionImages($existingQuestion->options ?? []);
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

                foreach ($survey->questions()->whereIn('id', $questionIdsToDelete)->get() as $questionToDelete) {
                    $this->deleteOptionImages($questionToDelete->options ?? []);
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

    private function buildQuestionsPayload(UpsertSurveyRequest $request, array $questions): array
    {
        return collect($questions)
            ->values()
            ->map(function (array $question, int $index) use ($request): array {
                return [
                    'id' => isset($question['id']) && $question['id'] !== '' ? (int)$question['id'] : null,
                    'question' => trim($question['question']),
                    'type' => $question['type'],
                    'options' => $this->normalizeOptions(
                        $request,
                        $index,
                        $question['type'],
                        $question['options'] ?? []
                    ),
                    'required' => (bool)($question['required'] ?? false),
                    'sort_order' => $index + 1,
                ];
            })
            ->all();
    }

    private function normalizeOptions(
        UpsertSurveyRequest $request,
        int                 $questionIndex,
        string              $type,
        array               $options
    ): ?array
    {
        if ($type === 'textarea') {
            return null;
        }

        $normalized = collect($options)
            ->values()
            ->map(function ($option, int $optionIndex) use ($request, $questionIndex, $type) {
                $label = '';
                $existingImage = null;

                if (is_array($option)) {
                    $label = trim((string)($option['label'] ?? ''));
                    $existingImage = $this->normalizeExistingImage($option['existing_image'] ?? null);
                } else {
                    $label = trim((string)$option);
                }

                if ($label === '') {
                    return null;
                }

                $imagePath = $existingImage;

                if ($type === 'swipe' && $request->hasFile("questions.$questionIndex.options.$optionIndex.image")) {
                    if ($existingImage) {
                        Storage::disk($this->surveyImagesDisk())->delete($existingImage);
                    }

                    $imagePath = $request->file("questions.$questionIndex.options.$optionIndex.image")
                        ->store('survey-options', $this->surveyImagesDisk());

                    if (! is_string($imagePath) || $imagePath === '') {
                        throw ValidationException::withMessages([
                            'questions' => 'De afbeelding kon niet worden opgeslagen. Probeer het opnieuw.',
                        ]);
                    }
                }

                if ($type === 'swipe') {
                    return [
                        'label' => $label,
                        'image' => $imagePath,
                    ];
                }

                return $label;
            })
            ->filter()
            ->values()
            ->all();

        if ($type === 'swipe' && count($normalized) < 2) {
            return [
                ['label' => 'Nee', 'image' => null],
                ['label' => 'Ja', 'image' => null],
            ];
        }

        return $normalized;
    }

    private function deleteOptionImages(array $options): void
    {
        foreach ($options as $option) {
            if (is_array($option) && ! empty($option['image']) && ! $this->isAbsoluteUrl((string) $option['image'])) {
                Storage::disk($this->surveyImagesDisk())->delete($option['image']);
            }
        }
    }
}
