<div class="space-y-6">
    <div class="space-y-2">
        <flux:heading size="lg">{{ __('Studentenenquete') }}</flux:heading>
        <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
            {{ __('Beantwoord de vragen hieronder. Je ziet direct hoeveel vragen er nog over zijn.') }}
        </flux:text>
    </div>

    @if ($this->totalQuestions > 0)
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
    @endif

    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-950" wire:key="question-{{ $this->currentQuestion?->question_id }}">
        @if ($this->totalQuestions === 0)
            <div class="space-y-3">
                <flux:heading size="lg">{{ __('Er is nog geen actieve enquête') }}</flux:heading>

                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    {{ __('Zodra er een actieve enquête beschikbaar is, verschijnt die hier automatisch.') }}
                </flux:text>
            </div>
        @elseif ($this->isCompleted)
            <div class="space-y-3">
                <flux:heading size="lg">{{ __('Bedankt voor het invullen') }}</flux:heading>

                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    {{ __('Je antwoorden zijn vastgelegd. De voortgang blijft op 100% staan.') }}
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

                    <flux:heading size="lg">{{ $this->currentQuestion?->prompt }}</flux:heading>

                    <flux:text class="text-zinc-600 dark:text-zinc-400">
                        {{ $this->currentQuestion?->description }}
                    </flux:text>
                </div>

                <flux:textarea
                    wire:model="answers.{{ $this->currentQuestion?->question_id }}"
                    rows="5"
                    :placeholder="$this->currentQuestion?->placeholder"
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
