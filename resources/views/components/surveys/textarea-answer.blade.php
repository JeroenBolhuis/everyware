@props([
    'question',
    'oldAnswer' => null,
])

<textarea
    name="answers[{{ $question->id }}]"
    rows="6"
    class="w-full rounded-xl border border-gray-200 p-4 focus:outline-none focus:ring-2 focus:ring-red-300"
    placeholder="Typ hier je opmerking of suggestie..."
>{{ $oldAnswer }}</textarea>

