<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::auth.card'), Title('Studentenenquete')] class extends Component
{
    public array $answers = [];

    public int $currentQuestionIndex = 0;

    public bool $isCompleted = false;

    /**
     * @return array<int, array{id: string, prompt: string, description: string, placeholder: string}>
     */
    #[Computed]
    public function questions(): array
    {
        return [
            [
                'id' => 'ervaring',
                'prompt' => 'Hoe ervaar je de huidige lessen tot nu toe?',
                'description' => 'Beschrijf kort wat goed gaat en wat volgens jou beter kan.',
                'placeholder' => 'Bijvoorbeeld: de uitleg is duidelijk, maar het tempo ligt hoog...',
            ],
            [
                'id' => 'belasting',
                'prompt' => 'Hoe ervaar je de studielast van deze periode?',
                'description' => 'Denk aan opdrachten, lessen en voorbereiding buiten de contacturen.',
                'placeholder' => 'Bijvoorbeeld: goed te doen, soms piekdruk, of juist te licht...',
            ],
            [
                'id' => 'begeleiding',
                'prompt' => 'Voel je je voldoende begeleid door docenten en studiebegeleiding?',
                'description' => 'Geef aan wat voor jou helpt of nog ontbreekt.',
                'placeholder' => 'Bijvoorbeeld: snelle feedback helpt, of ik mis vaste contactmomenten...',
            ],
            [
                'id' => 'verbetering',
                'prompt' => 'Welke ene verbetering zou voor jou de grootste impact hebben?',
                'description' => 'Noem de belangrijkste verandering die je graag terugziet.',
                'placeholder' => 'Bijvoorbeeld: duidelijkere planning, meer oefenmateriaal of extra uitleg...',
            ],
        ];
    }

    #[Computed]
    public function totalQuestions(): int
    {
        return count($this->questions);
    }

    /**
     * @return array{id: string, prompt: string, description: string, placeholder: string}
     */
    #[Computed]
    public function currentQuestion(): array
    {
        return $this->questions[$this->currentQuestionIndex];
    }

    #[Computed]
    public function currentQuestionNumber(): int
    {
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
        return (int) round(($this->completedQuestionCount / $this->totalQuestions) * 100);
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

    public function nextQuestion(): void
    {
        if ($this->isLastQuestion) {
            return;
        }

        $this->currentQuestionIndex++;
    }

    public function previousQuestion(): void
    {
        if (! $this->hasPreviousQuestion) {
            return;
        }

        $this->isCompleted = false;
        $this->currentQuestionIndex--;
    }

    public function completeSurvey(): void
    {
        $this->isCompleted = true;
    }
};
?>

<div class="space-y-6">
    <div class="space-y-2">
        <flux:heading size="lg">{{ __('Studentenenquete') }}</flux:heading>
        <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
            {{ __('Beantwoord de vragen hieronder. Je ziet direct hoeveel vragen er nog over zijn.') }}
        </flux:text>
    </div>

    <section class="space-y-3" data-test="survey-progress-bar">
        <div class="flex items-center justify-between gap-3">
            <flux:text class="font-medium text-zinc-700 dark:text-zinc-300">
                {{ __('Vraag :current van :total', ['current' => $this->completedQuestionCount, 'total' => $this->totalQuestions]) }}
            </flux:text>

            <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                {{ __(':percentage% voltooid', ['percentage' => $this->progressPercentage]) }}
            </flux:text>
        </div>

        <div class="h-2.5 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-800">
            <div
                class="h-full rounded-full bg-accent transition-all duration-300"
                data-test="survey-progress-fill"
                style="width: {{ $this->progressPercentage }}%;"
            ></div>
        </div>
    </section>

    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-950" wire:key="question-{{ $this->currentQuestion['id'] }}">
        @if ($this->isCompleted)
            <div class="space-y-3">
                <flux:heading size="lg">{{ __('Bedankt voor het invullen') }}</flux:heading>

                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    {{ __('Je antwoorden zijn vastgelegd in deze demo-flow. De voortgang blijft op 100% staan.') }}
                </flux:text>

                <div class="flex justify-start">
                    <flux:button variant="primary" type="button" wire:click="$refresh">
                        {{ __('Afgerond') }}
                    </flux:button>
                </div>
            </div>
        @else
            <div class="space-y-6">
                <div class="space-y-2">
                    <flux:text class="text-sm font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                        {{ __('Vraag :number', ['number' => $this->currentQuestionNumber]) }}
                    </flux:text>

                    <flux:heading size="lg">{{ $this->currentQuestion['prompt'] }}</flux:heading>

                    <flux:text class="text-zinc-600 dark:text-zinc-400">
                        {{ $this->currentQuestion['description'] }}
                    </flux:text>
                </div>

                <flux:textarea
                    wire:model="answers.{{ $this->currentQuestion['id'] }}"
                    rows="5"
                    :placeholder="$this->currentQuestion['placeholder']"
                />

                <div class="flex items-center justify-between gap-3">
                    <flux:button
                        type="button"
                        variant="ghost"
                        wire:click="previousQuestion"
                        :disabled="! $this->hasPreviousQuestion"
                    >
                        {{ __('Vorige') }}
                    </flux:button>

                    @if ($this->isLastQuestion)
                        <flux:button type="button" variant="primary" wire:click="completeSurvey">
                            {{ __('Afronden') }}
                        </flux:button>
                    @else
                        <flux:button type="button" variant="primary" wire:click="nextQuestion">
                            {{ __('Volgende') }}
                        </flux:button>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
