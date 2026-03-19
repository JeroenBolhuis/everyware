<x-layout>
    @vite(['resources/css/surveys/show.css', 'resources/js/surveys/show.js'])

    <div class="min-h-screen bg-gray-100">
        <header class="bg-white border-b px-6 py-4 flex justify-between items-center">
            <div class="text-red-600 font-bold text-xl">avans</div>
            <div class="font-semibold text-red-600">LIC Feedback Demo</div>
            <div class="text-sm text-gray-500">Verlaat demo</div>
        </header>

        <main class="max-w-3xl mx-auto mt-8 px-4 pb-10">
            @if ($errors->any())
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-red-700">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div
                id="surveyValidationMessage"
                class="hidden mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-red-700"
            ></div>

            <form method="POST" action="{{ route('surveys.store', $survey) }}" id="surveyForm" novalidate>
                @csrf

                @foreach ($survey->questions as $index => $question)
                    @php
                        $isFirst = $index === 0;
                        $isLast = $index === $survey->questions->count() - 1;
                        $oldAnswer = old("answers.$question->id");
                        $leftOption = $question->options[0] ?? 'nee';
                        $rightOption = $question->options[1] ?? 'ja';
                    @endphp

                    <section
                        class="question-step {{ $isFirst ? '' : 'hidden' }}"
                        data-step="{{ $index }}"
                        data-question-id="{{ $question->id }}"
                        data-type="{{ $question->type }}"
                        data-required="{{ $question->required ? '1' : '0' }}"
                        aria-hidden="{{ $isFirst ? 'false' : 'true' }}"
                    >
                        <div class="mb-4">
                            <div class="text-sm text-red-500 mb-2">
                                Vraag {{ $index + 1 }} van {{ $survey->questions->count() }}
                            </div>

                            <div class="w-full bg-red-100 h-2 rounded-full overflow-hidden" aria-hidden="true">
                                <div
                                    class="bg-red-400 h-2 rounded-full transition-all duration-300"
                                    style="width: {{ (($index + 1) / $survey->questions->count()) * 100 }}%"
                                ></div>
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                            <h2 class="text-3xl font-semibold text-gray-900 mb-3">
                                {{ $question->question }}
                            </h2>

                            <p class="text-sm text-gray-500 mb-8">
                                {{ $question->required ? 'Verplicht' : 'Optioneel' }}
                            </p>

                            @if ($question->type === 'radio')
                                <div class="space-y-3">
                                    @foreach (($question->options ?? []) as $optionIndex => $option)
                                        <label
                                            for="question-{{ $question->id }}-option-{{ $optionIndex }}"
                                            class="survey-option flex items-center justify-between rounded-xl border border-gray-200 px-5 py-4 cursor-pointer hover:bg-gray-50 transition"
                                        >
                                            <span class="font-medium text-gray-900">
                                                {{ $option }}
                                            </span>

                                            <input
                                                id="question-{{ $question->id }}-option-{{ $optionIndex }}"
                                                type="radio"
                                                name="answers[{{ $question->id }}]"
                                                value="{{ $option }}"
                                                class="h-5 w-5"
                                                {{ $oldAnswer === $option ? 'checked' : '' }}
                                            >
                                        </label>
                                    @endforeach
                                </div>
                            @endif

                            @if ($question->type === 'swipe')
                                <input
                                    type="hidden"
                                    name="answers[{{ $question->id }}]"
                                    id="answer-{{ $question->id }}"
                                    value="{{ $oldAnswer }}"
                                >

                                <div class="mb-4 text-center text-sm text-gray-500">
                                    Swipe naar links of rechts, of gebruik de knoppen
                                </div>

                                <div class="flex justify-center">
                                    <div
                                        class="swipe-card relative w-full max-w-md rounded-2xl border border-gray-200 bg-gray-50 p-10 text-center select-none"
                                        data-question-id="{{ $question->id }}"
                                        data-left="{{ $leftOption }}"
                                        data-right="{{ $rightOption }}"
                                        tabindex="0"
                                        role="button"
                                        aria-label="Swipe keuze voor vraag {{ $index + 1 }}"
                                    >
                                        <div class="absolute inset-0 pointer-events-none">
                                            <div class="swipe-badge-left absolute top-4 left-4 rounded-lg border-2 border-red-500 px-3 py-1 text-red-500 font-bold opacity-0">
                                                {{ strtoupper($leftOption) }}
                                            </div>
                                            <div class="swipe-badge-right absolute top-4 right-4 rounded-lg border-2 border-green-500 px-3 py-1 text-green-500 font-bold opacity-0">
                                                {{ strtoupper($rightOption) }}
                                            </div>
                                        </div>

                                            <div class="flex justify-center gap-4 mb-4">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                                                </svg>

                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                                </svg>
                                            </div>        
                                            <p class="text-lg font-medium text-gray-800">
                                            Geef snel je antwoord
                                        </p>
                                    </div>
                                </div>

                                <div class="flex justify-center gap-4 mt-6">
                                    <button
                                        type="button"
                                        class="swipe-choice px-8 py-3 rounded-xl bg-red-100 text-red-700 font-semibold hover:bg-red-200 transition"
                                        data-question-id="{{ $question->id }}"
                                        data-value="{{ $leftOption }}"
                                    >
                                        {{ ucfirst($leftOption) }}
                                    </button>

                                    <button
                                        type="button"
                                        class="swipe-choice px-8 py-3 rounded-xl bg-green-100 text-green-700 font-semibold hover:bg-green-200 transition"
                                        data-question-id="{{ $question->id }}"
                                        data-value="{{ $rightOption }}"
                                    >
                                        {{ ucfirst($rightOption) }}
                                    </button>
                                </div>
                            @endif

                            @if ($question->type === 'textarea')
                                <textarea
                                    name="answers[{{ $question->id }}]"
                                    rows="6"
                                    class="w-full rounded-xl border border-gray-200 p-4 focus:outline-none focus:ring-2 focus:ring-red-300"
                                    placeholder="Typ hier je opmerking of suggestie..."
                                >{{ $oldAnswer }}</textarea>
                            @endif

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
                @endforeach
            </form>
        </main>
    </div>
</x-layout>