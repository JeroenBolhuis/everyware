@props([
    'question',
    'oldAnswer' => null,
])

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

