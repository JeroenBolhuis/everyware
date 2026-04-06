<x-layout>
    @vite(['resources/css/surveys/show.css', 'resources/js/surveys/show.js'])

    @php
        $totalQuestions = $survey->questions->count();
        $initialStep = 0;

        if ($errors->any()) {
            foreach ($survey->questions as $errorIndex => $errorQuestion) {
                if ($errors->has("answers.{$errorQuestion->id}")) {
                    $initialStep = $errorIndex;
                    break;
                }
            }
        }
    @endphp

    <div class="survey-page">
        <div class="avans-header">
            <x-surveys.page-header />
        </div>

        <main class="survey-main max-w-3xl mx-auto w-full px-4 pb-10 pt-0">
            <x-surveys.validation-notices />

            <form
                method="POST"
                action="{{ route('survey.store', $survey) }}"
                id="surveyForm"
                data-initial-step="{{ $initialStep }}"
                novalidate
            >
                @csrf

                @foreach ($survey->questions as $index => $question)
                    @php
                        $isFirst = $index === 0;
                        $isLast = $index === $totalQuestions - 1;
                        $oldAnswer = old("answers.$question->id");
                        $leftOption = $question->options[0] ?? 'nee';
                        $rightOption = $question->options[1] ?? 'ja';
                        $currentQuestionNumber = $index + 1;
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