<?php

namespace App\Livewire\Student;

use App\Models\Survey as SurveyModel;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::auth.card'), Title('Studentenenquete')]
class Survey extends Component
{
    private const DEFAULT_QUESTION_INDEX = 0;

    private const PROGRESS_MULTIPLIER = 100;

    public array $answers = [];

    public int $currentQuestionIndex = 0;

    public bool $isCompleted = false;

    /**
     * @return array<string, string>
     */
    protected function rules(): array
    {
        return [
            'answers.*' => 'nullable|string|max:5000',
        ];
    }

    #[Computed]
    public function survey(): ?SurveyModel
    {
        return SurveyModel::query()
            ->with('questions')
            ->where('is_active', true)
            ->first();
    }

    /**
     * @return Collection<int, SurveyQuestion>
     */
    #[Computed]
    public function questions(): Collection
    {
        if (! $this->survey) {
            return new Collection;
        }

        return $this->survey->questions;
    }

    #[Computed]
    public function totalQuestions(): int
    {
        return $this->questions->count();
    }

    #[Computed]
    public function currentQuestion(): ?SurveyQuestion
    {
        if ($this->currentQuestionIndex < 0 || $this->currentQuestionIndex >= $this->questions->count()) {
            return $this->questions->get(self::DEFAULT_QUESTION_INDEX);
        }

        return $this->questions->get($this->currentQuestionIndex);
    }

    #[Computed]
    public function currentQuestionNumber(): int
    {
        if ($this->totalQuestions === 0) {
            return 0;
        }

        return $this->currentQuestionIndex + 1;
    }

    #[Computed]
    public function completedQuestionCount(): int
    {
        if ($this->isCompleted) {
            return $this->totalQuestions;
        }

        return min($this->currentQuestionNumber, $this->totalQuestions);
    }

    #[Computed]
    public function progressPercentage(): int
    {
        if ($this->totalQuestions === 0) {
            return 0;
        }

        return (int) round(($this->completedQuestionCount / $this->totalQuestions) * self::PROGRESS_MULTIPLIER);
    }

    #[Computed]
    public function hasPreviousQuestion(): bool
    {
        return $this->currentQuestionIndex > 0;
    }

    #[Computed]
    public function isLastQuestion(): bool
    {
        return $this->currentQuestionIndex === $this->totalQuestions - 1;
    }

    /**
     * Move to the next question after validating the current answer.
     */
    public function nextQuestion(): void
    {
        if (! $this->currentQuestion || $this->isLastQuestion) {
            return;
        }

        $this->validateOnly('answers.'.$this->currentQuestion->question_id);
        $this->currentQuestionIndex++;
    }

    /**
     * Move to the previous question.
     */
    public function previousQuestion(): void
    {
        if (! $this->hasPreviousQuestion) {
            return;
        }

        $this->isCompleted = false;
        $this->currentQuestionIndex--;
    }

    /**
     * Complete the survey after validating all answers.
     */
    public function completeSurvey(): void
    {
        if ($this->totalQuestions === 0) {
            $this->isCompleted = true;

            return;
        }

        $this->validate();

        if (! $this->survey || ! auth()->check()) {
            $this->isCompleted = true;

            return;
        }

        foreach ($this->answers as $questionId => $answer) {
            $question = $this->questions->firstWhere('question_id', $questionId);

            if ($question) {
                SurveyResponse::updateOrCreate(
                    [
                        'user_id' => auth()->id(),
                        'survey_id' => $this->survey->id,
                        'survey_question_id' => $question->id,
                    ],
                    [
                        'answer' => $answer,
                    ]
                );
            }
        }

        $this->isCompleted = true;
    }

    public function render(): View
    {
        return view('livewire.student.survey');
    }
}
