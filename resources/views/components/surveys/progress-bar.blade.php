@props([
    'currentQuestionNumber',
    'totalQuestions',
    'progressPercentage',
])

<div class="mb-4 space-y-2" data-test="survey-progress-bar">
    <div class="flex items-center justify-between gap-3">
        <div class="text-sm font-medium text-red-500">
            Vraag {{ $currentQuestionNumber }} van {{ $totalQuestions }}
        </div>


    </div>

    <div class="h-2 w-full overflow-hidden rounded-full bg-red-100" aria-hidden="true">
        <div
            class="h-2 rounded-full bg-red-400 transition-all duration-300"
            data-test="survey-progress-fill"
            style="width: {{ $progressPercentage }}%"
        ></div>
    </div>
</div>
