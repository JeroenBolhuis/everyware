<x-layout>
    @vite(['resources/css/surveys/show.css', 'resources/js/surveys/show.js'])

    <div class="min-h-screen bg-gray-100">
        <x-surveys.page-header />

        <main class="max-w-3xl mx-auto mt-8 px-4 pb-10">
            <x-surveys.validation-notices />

            <form method="POST" action="{{ route('survey.store', $survey) }}" id="surveyForm" novalidate>
                @csrf

                @foreach ($survey->questions as $index => $question)
                    @php
                        $isFirst = $index === 0;
                        $isLast = $index === $survey->questions->count() - 1;
                        $oldAnswer = old("answers.$question->id");
                        $leftOption = $question->options[0] ?? 'nee';
                        $rightOption = $question->options[1] ?? 'ja';
                        $currentQuestionNumber = $index + 1;
                        $totalQuestions = $survey->questions->count();
                        $progressPercentage = (int) round(($currentQuestionNumber / $totalQuestions) * 100);
                    @endphp

                    <x-surveys.question-step
                        :step="$index"
                        :question-id="$question->id"
                        :type="$question->type"
                        :required="$question->required"
                        :is-first="$isFirst"
                        :is-last="$isLast"
                        :question="$question->question"
                        :current-question-number="$currentQuestionNumber"
                        :total-questions="$totalQuestions"
                        :progress-percentage="$progressPercentage"
                    >
                        @if ($question->type === 'radio')
                            <x-surveys.radio-answer :question="$question" :old-answer="$oldAnswer" />
                        @endif

                        @if ($question->type === 'swipe')
                            <x-surveys.swipe-answer
                                :question="$question"
                                :old-answer="$oldAnswer"
                                :left-option="$leftOption"
                                :right-option="$rightOption"
                                :index="$index"
                            />
                        @endif

                        @if ($question->type === 'textarea')
                            <x-surveys.textarea-answer :question="$question" :old-answer="$oldAnswer" />
                        @endif
                    </x-surveys.question-step>
                @endforeach
            </form>
        </main>
    </div>
</x-layout>
