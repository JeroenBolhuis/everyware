@props([
    'question',
    'oldAnswer' => null,
    'leftOption' => 'nee',
    'rightOption' => 'ja',
    'index' => 0,
])

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
            <div
                class="swipe-badge-left absolute top-4 left-4 rounded-lg border-2 border-gray-400 px-3 py-1 text-gray-700 font-bold opacity-0">
                {{ strtoupper($leftOption) }}
            </div>
            <div
                class="swipe-badge-right absolute top-4 right-4 rounded-lg border-2 border-red-700 px-3 py-1 text-red-700 font-bold opacity-0">
                {{ strtoupper($rightOption) }}
            </div>
        </div>

        <div class="flex justify-center gap-4 mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                 stroke="currentColor" class="w-12 h-12">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
            </svg>

            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                 stroke="currentColor" class="w-12 h-12">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
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
        class="swipe-choice px-8 py-3 rounded-full border border-gray-300 bg-white text-gray-800 font-semibold hover:border-gray-400 hover:bg-gray-100 transition"
        data-question-id="{{ $question->id }}"
        data-value="{{ $leftOption }}"
    >
        {{ ucfirst($leftOption) }}
    </button>

    <button
        type="button"
        class="swipe-choice px-8 py-3 rounded-full border border-red-700 bg-red-700 text-white font-semibold hover:bg-red-800 transition"
        data-question-id="{{ $question->id }}"
        data-value="{{ $rightOption }}"
    >
        {{ ucfirst($rightOption) }}
    </button>
</div>
