<?php

namespace App\Livewire\PublicEnquetes;

use App\Models\Enquete;
use App\Models\EnqueteAnswer;
use App\Models\EnqueteSubmission;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Fill extends Component
{
    public Enquete $enquete;

    /**
     * @var array<string, mixed>
     */
    public array $answers = [];

    public bool $submitted = false;

    public function mount(Enquete $enquete): void
    {
        abort_unless($enquete->is_published, 404);

        $this->enquete = $enquete->loadMissing(['questions' => fn ($q) => $q->orderBy('sort_order')]);

        $draft = request()->cookie($this->cookieName());

        if (is_string($draft) && $draft !== '') {
            $decoded = json_decode($draft, true);

            if (is_array($decoded)) {
                $this->answers = $decoded;
            }
        }
    }

    public function updated(string $property): void
    {
        if (! str_starts_with($property, 'answers.')) {
            return;
        }

        Cookie::queue(
            Cookie::make(
                name: $this->cookieName(),
                value: json_encode($this->answers, JSON_THROW_ON_ERROR),
                minutes: 60 * 24 * 30,
            ),
        );
    }

    public function submit(): void
    {
        $rules = [];

        foreach ($this->enquete->questions as $question) {
            if (! $question->is_required) {
                continue;
            }

            $rules['answers.'.$question->id] = ['required'];
        }

        $this->validate($rules);

        DB::transaction(function () {
            $submission = EnqueteSubmission::query()->create([
                'enquete_id' => $this->enquete->id,
                'submitted_at' => now(),
                'respondent_key' => null,
            ]);

            foreach ($this->enquete->questions as $question) {
                if (! array_key_exists((string) $question->id, $this->answers) && ! array_key_exists($question->id, $this->answers)) {
                    continue;
                }

                $rawValue = $this->answers[$question->id] ?? $this->answers[(string) $question->id] ?? null;

                EnqueteAnswer::query()->create([
                    'submission_id' => $submission->id,
                    'question_id' => $question->id,
                    'value' => ['value' => $rawValue],
                ]);
            }
        });

        Cookie::queue(Cookie::forget($this->cookieName()));

        $this->submitted = true;
    }

    protected function cookieName(): string
    {
        return 'enquete_draft_'.$this->enquete->id;
    }

    public function render()
    {
        return view('livewire.public-enquetes.fill')
            ->layout('layouts.public', ['title' => $this->enquete->title]);
    }
}
