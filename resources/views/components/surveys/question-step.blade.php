@props([
    'step',
    'questionId',
    'type',
    'required' => false,
    'isFirst' => false,
    'isLast' => false,
    'question',
    'currentQuestionNumber',
    'totalQuestions',
    'progressPercentage',
])

<section
    class="question-step {{ $isFirst ? '' : 'hidden' }}"
    data-step="{{ $step }}"
    data-question-id="{{ $questionId }}"
    data-type="{{ $type }}"
    data-required="{{ $required ? '1' : '0' }}"
    aria-hidden="{{ $isFirst ? 'false' : 'true' }}"
>
    <x-surveys.progress-bar
        :current-question-number="$currentQuestionNumber"
        :total-questions="$totalQuestions"
        :progress-percentage="$progressPercentage"
    />

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-3xl font-semibold text-gray-900 mb-3">
            {{ $question }}
        </h2>

        <p class="text-sm text-gray-500 mb-8">
            {{ $required ? 'Verplicht' : 'Optioneel' }}
        </p>

        {{ $slot }}

        <div class="flex justify-between items-center mt-8">
            <div>
                @if (! $isFirst)
                    <button
                        type="button"
                        class="prev-btn inline-flex items-center justify-center px-6 py-3 rounded-xl bg-gray-200 text-gray-800 font-medium hover:bg-gray-300 transition"
                    >
                        Vorige
                    </button>
                @endif
            </div>

            <button
                type="button"
                class="next-btn inline-flex items-center justify-center min-w-[140px] px-8 py-3 rounded-xl bg-red-600 text-white font-semibold shadow-md border border-red-600 hover:bg-red-700 transition"
            >
                {{ $isLast ? 'Verzenden' : 'Volgende' }}
            </button>
        </div>
    </div>
</section>

