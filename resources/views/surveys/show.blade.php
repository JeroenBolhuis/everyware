<x-layout>
    @vite(['resources/js/surveys/show.js'])

    @php
        $totalQuestions = $survey->questions->count();
        $totalSteps = $totalQuestions;
        $initialStep = 0;
        $surveyImagesDisk = config('filesystems.survey_images_disk', 'public');

        if ($errors->any()) {
            foreach ($survey->questions as $errorIndex => $errorQuestion) {
                if ($errors->has("answers.{$errorQuestion->id}")) {
                    $initialStep = $errorIndex;
                    break;
                }
            }
        }
    @endphp

    <div class="min-h-screen flex flex-col overflow-x-hidden">
        <x-surveys.page-header/>

        <main class="flex-1 overflow-visible max-w-3xl mx-auto w-full px-4 pb-10 pt-0">
            <x-surveys.validation-notices/>

            <section
                class="mb-6 rounded-[1.25rem] border border-red-200 bg-gradient-to-br from-orange-50 to-white p-6 shadow-[0_10px_30px_rgba(215,25,32,0.08)]"
                aria-label="Uitleg over anonimiteit"
            >
                <div class="text-xs font-bold uppercase tracking-[0.08em] text-red-700">Belangrijk</div>
                <h1 class="mt-2 text-2xl font-bold leading-tight text-gray-900">
                    Deze enquête kun je anoniem invullen.
                </h1>
                <p class="mt-3 max-w-2xl text-base leading-relaxed text-gray-700">
                    Je antwoorden worden opgeslagen zonder je naam of e-mailadres. Alleen als je na afloop
                    zelf contactgegevens invult, kunnen we je feedback aan jou koppelen.
                </p>
            </section>

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
                        $isLast = false;
                        $oldAnswer = old("answers.$question->id");

                        $leftRawOption = $question->options[0] ?? ['label' => 'Nee', 'image' => null];
                        $rightRawOption = $question->options[1] ?? ['label' => 'Ja', 'image' => null];

                        $leftOptionLabel = is_array($leftRawOption)
                            ? ($leftRawOption['label'] ?? 'Nee')
                            : $leftRawOption;

                        $rightOptionLabel = is_array($rightRawOption)
                            ? ($rightRawOption['label'] ?? 'Ja')
                            : $rightRawOption;

                        $leftOptionImage = is_array($leftRawOption) && !empty($leftRawOption['image'])
                            ? (filter_var($leftRawOption['image'], FILTER_VALIDATE_URL)
                                ? $leftRawOption['image']
                                : Storage::disk($surveyImagesDisk)->url($leftRawOption['image']))
                            : null;
                        $leftOptionImageAlt = is_array($leftRawOption)
                            ? ($leftRawOption['image_alt'] ?? $leftOptionLabel)
                            : $leftOptionLabel;

                        $rightOptionImage = is_array($rightRawOption) && !empty($rightRawOption['image'])
                            ? (filter_var($rightRawOption['image'], FILTER_VALIDATE_URL)
                                ? $rightRawOption['image']
                                : Storage::disk($surveyImagesDisk)->url($rightRawOption['image']))
                            : null;
                        $rightOptionImageAlt = is_array($rightRawOption)
                            ? ($rightRawOption['image_alt'] ?? $rightOptionLabel)
                            : $rightOptionLabel;

                        $currentQuestionNumber = $index + 1;
                        $progressPercentage = (int) round(($currentQuestionNumber / $totalSteps) * 100);
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
                        :total-questions="$totalSteps"
                        :progress-percentage="$progressPercentage"
                    >
                        @if ($question->type === 'radio')
                            <x-surveys.radio-answer :question="$question" :old-answer="$oldAnswer"/>
                        @endif

                        @if ($question->type === 'swipe')
                            <x-surveys.swipe-answer
                                :question="$question"
                                :old-answer="$oldAnswer"
                                :left-option="$leftOptionLabel"
                                :right-option="$rightOptionLabel"
                                :left-image="$leftOptionImage"
                                :left-image-alt="$leftOptionImageAlt"
                                :right-image="$rightOptionImage"
                                :right-image-alt="$rightOptionImageAlt"
                                :index="$index"
                            />
                        @endif

                        @if ($question->type === 'textarea')
                            <x-surveys.textarea-answer :question="$question" :old-answer="$oldAnswer"/>
                        @endif
                    </x-surveys.question-step>
                @endforeach
            </form>
        </main>
    </div>
</x-layout>
