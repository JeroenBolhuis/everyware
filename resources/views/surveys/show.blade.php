<x-layout>
    @vite(['resources/css/surveys/show.css', 'resources/js/surveys/show.js'])

    @php
        $totalQuestions = $survey->questions->count();
        $totalSteps = $totalQuestions + 2;
        $initialStep = 0;

        if ($errors->any()) {
            if ($errors->has('student_email')) {
                $initialStep = 0;
            }

            foreach ($survey->questions as $errorIndex => $errorQuestion) {
                if ($errors->has("answers.{$errorQuestion->id}")) {
                    $initialStep = $errorIndex + 1;
                    break;
                }
            }

            if ($errors->has('contact_name') || $errors->has('contact_email')) {
                $initialStep = $totalSteps - 1;
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

                <section
                    class="question-step"
                    data-step="0"
                    data-type="email"
                    data-required="1"
                    aria-hidden="false"
                >
                    <x-surveys.progress-bar
                        :current-question-number="1"
                        :total-questions="$totalSteps"
                        :progress-percentage="(int) round((1 / $totalSteps) * 100)"
                    />

                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                        <h2 class="text-3xl font-semibold text-gray-900 mb-3">
                            Wat is je e-mailadres?
                        </h2>

                        <p class="text-sm text-gray-500 mb-8">
                            Verplicht - je kunt de enquete maar een keer invullen per e-mailadres.
                        </p>

                        <input
                            type="email"
                            name="student_email"
                            id="student_email"
                            value="{{ old('student_email') }}"
                            placeholder="naam@student.avans.nl"
                            class="w-full rounded-xl border border-gray-200 p-4 focus:outline-none focus:ring-2 focus:ring-red-300"
                            autocomplete="email"
                        >

                        @error('student_email')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror

                        <div class="flex justify-end mt-8">
                            <button
                                type="button"
                                class="next-btn inline-flex items-center justify-center min-w-[140px] px-8 py-3 rounded-xl bg-red-600 text-white font-semibold shadow-md border border-red-600 hover:bg-red-700 transition"
                            >
                                Volgende
                            </button>
                        </div>
                    </div>
                </section>

                @foreach ($survey->questions as $index => $question)
                    @php
                        $stepNumber = $index + 1;
                        $oldAnswer = old("answers.$question->id");
                        $leftOption = $question->options[0] ?? 'nee';
                        $rightOption = $question->options[1] ?? 'ja';
                        $currentQuestionNumber = $stepNumber + 1;
                        $progressPercentage = (int) round(($currentQuestionNumber / $totalSteps) * 100);
                    @endphp

                    <x-surveys.question-step
                        :step="$stepNumber"
                        :question-id="$question->id"
                        :type="$question->type"
                        :required="$question->required"
                        :is-first="false"
                        :is-last="false"
                        :question="$question->question"
                        :current-question-number="$currentQuestionNumber"
                        :total-questions="$totalSteps"
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

                @php
                    $contactStepIndex = $totalSteps - 1;
                @endphp

                <x-surveys.question-step
                    :step="$contactStepIndex"
                    question-id="contact-details"
                    type="contact"
                    :required="false"
                    :is-first="false"
                    :is-last="true"
                    question="Laat optioneel je naam en e-mailadres achter voor een bevestigingsmail"
                    :current-question-number="$totalSteps"
                    :total-questions="$totalSteps"
                    :progress-percentage="100"
                >
                    <div class="space-y-4">
                        <p class="text-gray-600">
                            Als je een e-mailadres invult, sturen we direct na het verzenden een bevestigingsmail.
                        </p>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label for="contact_name" class="mb-1 block text-sm font-medium text-gray-700">
                                    Naam <span class="text-gray-500">(optioneel)</span>
                                </label>
                                <input
                                    id="contact_name"
                                    type="text"
                                    name="contact_name"
                                    value="{{ old('contact_name') }}"
                                    autocomplete="name"
                                    class="w-full rounded-xl border border-gray-200 p-3 focus:outline-none focus:ring-2 focus:ring-red-300"
                                    placeholder="Bijvoorbeeld: Jamie Jansen"
                                >
                                @error('contact_name')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label for="contact_email" class="mb-1 block text-sm font-medium text-gray-700">
                                    E-mailadres <span class="text-gray-500">(optioneel)</span>
                                </label>
                                <input
                                    id="contact_email"
                                    type="email"
                                    name="contact_email"
                                    value="{{ old('contact_email') }}"
                                    autocomplete="email"
                                    class="w-full rounded-xl border border-gray-200 p-3 focus:outline-none focus:ring-2 focus:ring-red-300"
                                    placeholder="naam@voorbeeld.nl"
                                >
                                @error('contact_email')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </x-surveys.question-step>
            </form>
        </main>
    </div>
</x-layout>
