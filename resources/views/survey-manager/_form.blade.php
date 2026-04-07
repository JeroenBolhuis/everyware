@php
    $isEdit = isset($survey);
    $existingQuestions = old('questions', $isEdit
        ? $survey->questions->map(fn ($question) => [
            'id' => $question->id,
            'question' => $question->question,
            'type' => $question->type,
            'required' => $question->required,
            'options' => $question->options ? implode(', ', $question->options) : '',
        ])->values()->all()
        : [[
            'question' => '',
            'type' => 'radio',
            'required' => true,
            'options' => 'Ja, Nee',
        ]]);
@endphp

<x-layouts::app :title="$isEdit ? __('Enquête bewerken') : __('Nieuwe enquête')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-700 dark:bg-zinc-900">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">
                        {{ $isEdit ? 'Enquête bewerken' : 'Nieuwe enquête aanmaken' }}
                    </h1>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        Voeg hier de basisinformatie en vragen van de enquête toe.
                    </p>
                </div>

               <a href="{{ route('survey-manager.index') }}" class="inline-flex items-center justify-center rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">
                    Terug naar overzicht
                </a>
            </div>
        </div>

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200">
                <div class="font-medium">Controleer de invoer:</div>
                <ul class="mt-2 list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ $isEdit ? route('survey-manager.update', $survey) : route('survey-manager.store') }}" class="space-y-4">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-700 dark:bg-zinc-900">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label for="title" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">Titel</label>
                        <input
                            id="title"
                            name="title"
                            type="text"
                            value="{{ old('title', $survey->title ?? '') }}"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                            required
                        >
                    </div>

                    <div class="md:col-span-2">
                        <label for="description" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">Beschrijving</label>
                        <textarea
                            id="description"
                            name="description"
                            rows="3"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                        >{{ old('description', $survey->description ?? '') }}</textarea>
                    </div>

                    <div>
                        <label for="is_active" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">Status</label>
                        <select
                            id="is_active"
                            name="is_active"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                        >
                            <option value="1" @selected((string) old('is_active', isset($survey) ? (int) $survey->is_active : 1) === '1')>Actief</option>
                            <option value="0" @selected((string) old('is_active', isset($survey) ? (int) $survey->is_active : 1) === '0')>Gesloten</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-700 dark:bg-zinc-900">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Vragen</h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Gebruik komma’s tussen opties, bijvoorbeeld: Helemaal eens, Eens, Neutraal, Oneens.</p>
                    </div>

                    <button type="button" id="add-question" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700">
                        Vraag toevoegen
                    </button>
                </div>

                <div id="questions-wrapper" class="space-y-4">
                    @foreach ($existingQuestions as $index => $question)
                        <div class="question-card rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                            <div class="mb-4 flex items-center justify-between gap-3">
                                <h3 class="text-base font-semibold text-zinc-900 dark:text-white">Vraag <span class="question-number">{{ $index + 1 }}</span></h3>
                                <button type="button" class="remove-question rounded-lg border border-red-200 px-3 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50 dark:border-red-900/50 dark:hover:bg-red-950/30">
                                    Verwijderen
                                </button>
                            </div>

                            <input type="hidden" name="questions[{{ $index }}][id]" value="{{ $question['id'] ?? '' }}">

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="md:col-span-2">
                                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">Vraagtekst</label>
                                    <input
                                        type="text"
                                        name="questions[{{ $index }}][question]"
                                        value="{{ $question['question'] ?? '' }}"
                                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                                        required
                                    >
                                </div>

                                <div>
                                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">Type</label>
                                    <select
                                        name="questions[{{ $index }}][type]"
                                        class="question-type w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                                    >
                                        <option value="radio" @selected(($question['type'] ?? 'radio') === 'radio')>Radio</option>
                                        <option value="swipe" @selected(($question['type'] ?? '') === 'swipe')>Swipe</option>
                                        <option value="textarea" @selected(($question['type'] ?? '') === 'textarea')>Textarea</option>
                                    </select>
                                </div>

                                <div class="flex items-end">
                                    <label class="inline-flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-200">
                                        <input
                                            type="hidden"
                                            name="questions[{{ $index }}][required]"
                                            value="0"
                                        >
                                        <input
                                            type="checkbox"
                                            name="questions[{{ $index }}][required]"
                                            value="1"
                                            class="rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500"
                                            @checked(($question['required'] ?? false))
                                        >
                                        Verplicht
                                    </label>
                                </div>

                                <div class="options-field md:col-span-2 {{ ($question['type'] ?? 'radio') === 'textarea' ? 'hidden' : '' }}">
                                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">Opties (gescheiden met komma’s)</label>
                                    <input
                                        type="text"
                                        name="questions[{{ $index }}][options]"
                                        value="{{ $question['options'] ?? '' }}"
                                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                                        placeholder="Ja, Nee"
                                    >
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex flex-wrap justify-end gap-3">
                <a href="{{ route('survey-manager.index') }}" class="inline-flex items-center justify-center rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">
                    Annuleren
                </a>
                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700">
                    {{ $isEdit ? 'Wijzigingen opslaan' : 'Enquête opslaan' }}
                </button>
            </div>
        </form>
    </div>

    <template id="question-template">
        <div class="question-card rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h3 class="text-base font-semibold text-zinc-900 dark:text-white">Vraag <span class="question-number"></span></h3>
                <button type="button" class="remove-question rounded-lg border border-red-200 px-3 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50 dark:border-red-900/50 dark:hover:bg-red-950/30">
                    Verwijderen
                </button>
            </div>

            <input type="hidden" data-field="id" value="">

            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">Vraagtekst</label>
                    <input type="text" data-field="question" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" required>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">Type</label>
                    <select data-field="type" class="question-type w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                        <option value="radio">Radio</option>
                        <option value="swipe">Swipe</option>
                        <option value="textarea">Textarea</option>
                    </select>
                </div>

                <div class="flex items-end">
                    <label class="inline-flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-200">
                        <input type="hidden" data-field="required_hidden" value="0">
                        <input type="checkbox" data-field="required" value="1" class="rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500" checked>
                        Verplicht
                    </label>
                </div>

                <div class="options-field md:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">Opties (gescheiden met komma’s)</label>
                    <input type="text" data-field="options" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" placeholder="Ja, Nee">
                </div>
            </div>
        </div>
    </template>

    <script>
        const wrapper = document.getElementById('questions-wrapper');
        const addButton = document.getElementById('add-question');
        const template = document.getElementById('question-template');

        function renameQuestionFields() {
            const cards = wrapper.querySelectorAll('.question-card');

            cards.forEach((card, index) => {
                const number = card.querySelector('.question-number');
                if (number) number.textContent = index + 1;

                const fieldMap = {
                    id: `questions[${index}][id]`,
                    question: `questions[${index}][question]`,
                    type: `questions[${index}][type]`,
                    required_hidden: `questions[${index}][required]`,
                    required: `questions[${index}][required]`,
                    options: `questions[${index}][options]`,
                };

                Object.entries(fieldMap).forEach(([key, name]) => {
                    const field = card.querySelector(`[data-field="${key}"]`) || card.querySelector(`[name$="[${key}]"]`);
                    if (field) field.setAttribute('name', name);
                });
            });
        }

        function toggleOptionsVisibility(card) {
            const typeField = card.querySelector('.question-type');
            const optionsField = card.querySelector('.options-field');

            if (!typeField || !optionsField) return;

            optionsField.classList.toggle('hidden', typeField.value === 'textarea');
        }

        function attachCardEvents(card) {
            card.querySelector('.remove-question')?.addEventListener('click', () => {
                if (wrapper.querySelectorAll('.question-card').length === 1) {
                    alert('Een enquête moet minimaal 1 vraag hebben.');
                    return;
                }

                card.remove();
                renameQuestionFields();
            });

            card.querySelector('.question-type')?.addEventListener('change', () => toggleOptionsVisibility(card));
            toggleOptionsVisibility(card);
        }

        addButton?.addEventListener('click', () => {
            const clone = template.content.firstElementChild.cloneNode(true);
            wrapper.appendChild(clone);
            renameQuestionFields();
            attachCardEvents(clone);
        });

        wrapper.querySelectorAll('.question-card').forEach((card) => attachCardEvents(card));
        renameQuestionFields();
    </script>
</x-layouts::app>