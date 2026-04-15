@php
    $isEdit = isset($survey);

    $existingQuestions = old('questions', $isEdit
        ? $survey->questions->map(function ($question) {
            $normalizedOptions = collect($question->options ?? [])
                ->map(function ($option) use ($question) {
                    if ($question->type === 'swipe') {
                        if (is_array($option)) {
                            return [
                                'label' => $option['label'] ?? '',
                                'existing_image' => $option['image'] ?? null,
                            ];
                        }

                        return [
                            'label' => (string) $option,
                            'existing_image' => null,
                        ];
                    }

                    return [
                        'label' => is_array($option) ? ($option['label'] ?? '') : $option,
                    ];
                })
                ->values()
                ->all();

            return [
                'id' => $question->id,
                'question' => $question->question,
                'type' => $question->type,
                'required' => $question->required,
                'options' => $normalizedOptions,
            ];
        })->values()->all()
        : [[
            'question' => '',
            'type' => 'radio',
            'required' => true,
            'options' => [
                ['label' => 'Ja'],
                ['label' => 'Nee'],
            ],
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

                <a href="{{ route('survey-manager.index') }}" class="btn-primary">
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

        <form
            method="POST"
            action="{{ $isEdit ? route('survey-manager.update', $survey) : route('survey-manager.store') }}"
            class="space-y-4"
            enctype="multipart/form-data"
        >
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
                            class="w-full rounded-full border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                            required
                        >
                    </div>

                    <div class="md:col-span-2">
                        <label for="description" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">Beschrijving</label>
                        <textarea
                            id="description"
                            name="description"
                            rows="3"
                            class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                        >{{ old('description', $survey->description ?? '') }}</textarea>
                    </div>

                    <div>
                        <label for="is_active" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">Status</label>
                        <select
                            id="is_active"
                            name="is_active"
                            class="w-full rounded-full border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                        >
                            <option value="1" @selected((string) old('is_active', isset($survey) ? (int) $survey->is_active : 1) === '1')>
                                Actief
                            </option>
                            <option value="0" @selected((string) old('is_active', isset($survey) ? (int) $survey->is_active : 1) === '0')>
                                Gesloten
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-700 dark:bg-zinc-900">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Vragen</h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            Voeg antwoordopties toe als losse velden. Bij swipe kun je ook afbeeldingen uploaden.
                        </p>
                    </div>
                </div>

                <div id="questions-wrapper" class="space-y-4">
                    @foreach ($existingQuestions as $index => $question)
                        <div class="question-card rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                            <div class="mb-4 flex items-center justify-between gap-3">
                                <h3 class="text-base font-semibold text-zinc-900 dark:text-white">
                                    Vraag <span class="question-number">{{ $index + 1 }}</span>
                                </h3>

                                <button type="button" class="remove-question btn-secondary">
                                    Verwijderen
                                </button>
                            </div>

                            <input
                                type="hidden"
                                name="questions[{{ $index }}][id]"
                                value="{{ $question['id'] ?? '' }}"
                                data-field="id"
                            >

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="md:col-span-2">
                                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">Vraagtekst</label>
                                    <input
                                        type="text"
                                        name="questions[{{ $index }}][question]"
                                        value="{{ $question['question'] ?? '' }}"
                                        class="w-full rounded-full border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                                        required
                                        data-field="question"
                                    >
                                </div>

                                <div>
                                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">Type</label>
                                    <select
                                        name="questions[{{ $index }}][type]"
                                        class="question-type w-full rounded-full border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                                        data-field="type"
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
                                            data-field="required_hidden"
                                        >
                                        <input
                                            type="checkbox"
                                            name="questions[{{ $index }}][required]"
                                            value="1"
                                            class="rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500"
                                            @checked(($question['required'] ?? false))
                                            data-field="required"
                                        >
                                        Verplicht
                                    </label>
                                </div>

                                <div class="options-field md:col-span-2 {{ ($question['type'] ?? 'radio') === 'textarea' ? 'hidden' : '' }}">
                                    <div class="mb-2 flex items-center justify-between gap-3">
                                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                            Antwoordopties
                                        </label>

                                        <button type="button" class="add-option btn-secondary">
                                            Optie toevoegen
                                        </button>
                                    </div>

                                    <div class="options-wrapper space-y-3">
                                        @php
                                            $options = $question['options'] ?? [];
                                            if (empty($options)) {
                                                $options = [
                                                    ['label' => ''],
                                                    ['label' => ''],
                                                ];
                                            }
                                        @endphp

                                        @foreach ($options as $optionIndex => $option)
                                            @php
                                                $label = is_array($option) ? ($option['label'] ?? '') : $option;
                                                $existingImage = is_array($option) ? ($option['existing_image'] ?? null) : null;
                                                $isSwipe = ($question['type'] ?? 'radio') === 'swipe';
                                            @endphp

                                            <div class="option-row rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                                                <div class="grid gap-3 md:grid-cols-12">
                                                    <div class="{{ $isSwipe ? 'md:col-span-6' : 'md:col-span-11' }}">
                                                        <input
                                                            type="text"
                                                            name="questions[{{ $index }}][options][{{ $optionIndex }}][label]"
                                                            value="{{ $label }}"
                                                            class="w-full rounded-full border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                                                            placeholder="Voer een antwoordoptie in"
                                                            data-option-label
                                                        >
                                                    </div>

                                                    <div class="swipe-image-field {{ $isSwipe ? 'md:col-span-4' : 'hidden' }}">
                                                        <input
                                                            type="hidden"
                                                            name="questions[{{ $index }}][options][{{ $optionIndex }}][existing_image]"
                                                            value="{{ $existingImage }}"
                                                            data-option-existing-image
                                                        >

                                                        @if ($existingImage)
                                                            <div class="mb-2">
                                                                <img
                                                                    src="{{ asset('storage/' . $existingImage) }}"
                                                                    alt="Bestaande optie afbeelding"
                                                                    class="h-20 w-full rounded-xl object-cover border border-neutral-200 dark:border-neutral-700"
                                                                >
                                                            </div>
                                                        @endif

                                                        <input
                                                            type="file"
                                                            name="questions[{{ $index }}][options][{{ $optionIndex }}][image]"
                                                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                                            class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                                                            data-option-image
                                                        >
                                                    </div>

                                                    <div class="{{ $isSwipe ? 'md:col-span-2' : 'md:col-span-1' }} flex items-start md:items-center">
                                                        <button type="button" class="remove-option btn-secondary w-full">
                                                            Verwijderen
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 flex justify-center">
                    <button type="button" id="add-question" class="btn-primary">
                        Vraag toevoegen
                    </button>
                </div>
            </div>

            <div class="flex flex-wrap justify-end gap-3">
                <a href="{{ route('survey-manager.index') }}" class="btn-secondary">
                    Annuleren
                </a>
                <button type="submit" class="btn-primary">
                    {{ $isEdit ? 'Wijzigingen opslaan' : 'Enquête opslaan' }}
                </button>
            </div>
        </form>
    </div>

    <template id="question-template">
        <div class="question-card rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h3 class="text-base font-semibold text-zinc-900 dark:text-white">
                    Vraag <span class="question-number"></span>
                </h3>

                <button type="button" class="remove-question btn-secondary">
                    Verwijderen
                </button>
            </div>

            <input type="hidden" data-field="id" value="">

            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">Vraagtekst</label>
                    <input
                        type="text"
                        data-field="question"
                        class="w-full rounded-full border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                        required
                    >
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">Type</label>
                    <select
                        data-field="type"
                        class="question-type w-full rounded-full border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                    >
                        <option value="radio">Radio</option>
                        <option value="swipe">Swipe</option>
                        <option value="textarea">Textarea</option>
                    </select>
                </div>

                <div class="flex items-end">
                    <label class="inline-flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-200">
                        <input type="hidden" data-field="required_hidden" value="0">
                        <input
                            type="checkbox"
                            data-field="required"
                            value="1"
                            class="rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500"
                            checked
                        >
                        Verplicht
                    </label>
                </div>

                <div class="options-field md:col-span-2">
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            Antwoordopties
                        </label>

                        <button type="button" class="add-option btn-secondary">
                            Optie toevoegen
                        </button>
                    </div>

                    <div class="options-wrapper space-y-3"></div>
                </div>
            </div>
        </div>
    </template>

    <template id="option-template">
        <div class="option-row rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
            <div class="grid gap-3 md:grid-cols-12">
                <div class="option-label-col md:col-span-11">
                    <input
                        type="text"
                        data-option-label
                        class="w-full rounded-full border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                        placeholder="Voer een antwoordoptie in"
                    >
                </div>

                <div class="swipe-image-field hidden md:col-span-4">
                    <input type="hidden" data-option-existing-image value="">
                    <input
                        type="file"
                        data-option-image
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                    >
                </div>

                <div class="option-remove-col md:col-span-1 flex items-start md:items-center">
                    <button type="button" class="remove-option btn-secondary w-full">
                        Verwijderen
                    </button>
                </div>
            </div>
        </div>
    </template>

<script>
    const wrapper = document.getElementById('questions-wrapper');
    const addButton = document.getElementById('add-question');
    const questionTemplate = document.getElementById('question-template');
    const optionTemplate = document.getElementById('option-template');
    const surveyForm = document.querySelector('form[enctype="multipart/form-data"]');

    const MAX_IMAGE_SIZE = 2 * 1024 * 1024;
    const MAX_TOTAL_IMAGE_SIZE = 5 * 1024 * 1024;

    function createOptionRow() {
        return optionTemplate.content.firstElementChild.cloneNode(true);
    }

    function getSelectedFileSize(input) {
        return input?.files?.[0]?.size ?? 0;
    }

    function calculateTotalSelectedImageSize() {
        let total = 0;

        document.querySelectorAll('[data-option-image]').forEach((input) => {
            total += getSelectedFileSize(input);
        });

        return total;
    }

    function validateImageInput(input) {
        const file = input.files?.[0];

        if (!file) {
            return true;
        }

        if (file.size > MAX_IMAGE_SIZE) {
            alert('Een afbeelding mag maximaal 2 MB groot zijn.');
            input.value = '';
            return false;
        }

        return true;
    }

    function validateTotalImageSize(changedInput = null) {
        const totalSize = calculateTotalSelectedImageSize();

        if (totalSize > MAX_TOTAL_IMAGE_SIZE) {
            if (changedInput) {
                changedInput.value = '';
            }

            alert('De totale grootte van alle afbeeldingen mag maximaal 5 MB zijn.');
            return false;
        }

        return true;
    }

    function bindImageValidation(scope = document) {
        scope.querySelectorAll('[data-option-image]').forEach((input) => {
            if (input.dataset.validationBound === 'true') {
                return;
            }

            input.dataset.validationBound = 'true';

            input.addEventListener('change', () => {
                if (!validateImageInput(input)) {
                    return;
                }

                validateTotalImageSize(input);
            });
        });
    }

    function updateOptionLayout(card) {
    const typeField = card.querySelector('.question-type');
    const addOptionButton = card.querySelector('.add-option');
    const optionsWrapper = card.querySelector('.options-wrapper');
    const isSwipe = typeField?.value === 'swipe';
    const optionCount = optionsWrapper?.querySelectorAll('.option-row').length ?? 0;

    card.querySelectorAll('.option-row').forEach((row) => {
        const labelCol = row.querySelector('.option-label-col');
        const imageField = row.querySelector('.swipe-image-field');
        const removeCol = row.querySelector('.option-remove-col');

        if (isSwipe) {
            labelCol?.classList.remove('md:col-span-11');
            labelCol?.classList.add('md:col-span-6');

            imageField?.classList.remove('hidden');
            imageField?.classList.add('md:col-span-4');

            removeCol?.classList.remove('md:col-span-1');
            removeCol?.classList.add('md:col-span-2');
        } else {
            labelCol?.classList.remove('md:col-span-6');
            labelCol?.classList.add('md:col-span-11');

            imageField?.classList.add('hidden');

            const imageInput = row.querySelector('[data-option-image]');
            if (imageInput) {
                imageInput.value = '';
            }

            removeCol?.classList.remove('md:col-span-2');
            removeCol?.classList.add('md:col-span-1');
        }
    });

        if (addOptionButton) {
            if (isSwipe && optionCount >= 2) {
                addOptionButton.classList.add('hidden');
            } else {
                addOptionButton.classList.remove('hidden');
            }
        }
    }

    function ensureMinimumOptions(card) 
    {
        const typeField = card.querySelector('.question-type');
        const optionsWrapper = card.querySelector('.options-wrapper');

        if (!typeField || !optionsWrapper) {
            return;
        }

        if (typeField.value === 'textarea') {
            updateOptionLayout(card);
            return;
        }

        while (optionsWrapper.querySelectorAll('.option-row').length < 2) {
            optionsWrapper.appendChild(createOptionRow());
        }

        if (typeField.value === 'swipe') {
            while (optionsWrapper.querySelectorAll('.option-row').length > 2) {
                optionsWrapper.querySelector('.option-row:last-child')?.remove();
            }
        }

        updateOptionLayout(card);
        bindImageValidation(card);
    }

    function renameOptionFields(card, questionIndex) {
        const optionRows = card.querySelectorAll('.option-row');

        optionRows.forEach((row, optionIndex) => {
            const labelInput = row.querySelector('[data-option-label]');
            const existingImageInput = row.querySelector('[data-option-existing-image]');
            const imageInput = row.querySelector('[data-option-image]');

            if (labelInput) {
                labelInput.setAttribute('name', `questions[${questionIndex}][options][${optionIndex}][label]`);
            }

            if (existingImageInput) {
                existingImageInput.setAttribute('name', `questions[${questionIndex}][options][${optionIndex}][existing_image]`);
            }

            if (imageInput) {
                imageInput.setAttribute('name', `questions[${questionIndex}][options][${optionIndex}][image]`);
            }
        });
    }

    function renameQuestionFields() {
        const cards = wrapper.querySelectorAll('.question-card');

        cards.forEach((card, index) => {
            const number = card.querySelector('.question-number');
            if (number) {
                number.textContent = index + 1;
            }

            const fieldMap = {
                id: `questions[${index}][id]`,
                question: `questions[${index}][question]`,
                type: `questions[${index}][type]`,
                required_hidden: `questions[${index}][required]`,
                required: `questions[${index}][required]`,
            };

            Object.entries(fieldMap).forEach(([key, name]) => {
                const field = card.querySelector(`[data-field="${key}"]`) || card.querySelector(`[name$="[${key}]"]`);
                if (field) {
                    field.setAttribute('name', name);
                }
            });

            renameOptionFields(card, index);
            updateOptionLayout(card);
        });
    }

    function toggleOptionsVisibility(card) 
    {
        const typeField = card.querySelector('.question-type');
        const optionsField = card.querySelector('.options-field');

        if (!typeField || !optionsField) {
            return;
        }

        const shouldHide = typeField.value === 'textarea';
        optionsField.classList.toggle('hidden', shouldHide);

        if (!shouldHide) {
            ensureMinimumOptions(card);
            renameQuestionFields();
            updateOptionLayout(card);
        } else {
            updateOptionLayout(card);
        }
    }

    function attachOptionEvents(card) 
    {
        card.querySelector('.add-option')?.addEventListener('click', () => {
            const optionsWrapper = card.querySelector('.options-wrapper');
            const typeField = card.querySelector('.question-type');

            if (!optionsWrapper || !typeField) {
                return;
            }

            const optionCount = optionsWrapper.querySelectorAll('.option-row').length;

            if (typeField.value === 'swipe' && optionCount >= 2) {
                alert('Een swipe-vraag mag precies 2 opties hebben.');
                updateOptionLayout(card);
                return;
            }

            const newRow = createOptionRow();

            optionsWrapper.appendChild(newRow);
            renameQuestionFields();
            attachOptionRowEvents(card);
            bindImageValidation(card);
            updateOptionLayout(card);
        });

        attachOptionRowEvents(card);
        bindImageValidation(card);
    }

    function attachOptionEvents(card) {
        card.querySelector('.add-option')?.addEventListener('click', () => {
            const optionsWrapper = card.querySelector('.options-wrapper');
            const newRow = createOptionRow();

            optionsWrapper.appendChild(newRow);
            renameQuestionFields();
            attachOptionRowEvents(card);
            bindImageValidation(card);
            updateOptionLayout(card);
        });

        attachOptionRowEvents(card);
        bindImageValidation(card);
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

        card.querySelector('.question-type')?.addEventListener('change', () => {
            toggleOptionsVisibility(card);
        });

        attachOptionEvents(card);
        toggleOptionsVisibility(card);
    }

    addButton?.addEventListener('click', () => {
        const clone = questionTemplate.content.firstElementChild.cloneNode(true);
        const optionsWrapper = clone.querySelector('.options-wrapper');

        optionsWrapper.appendChild(createOptionRow());
        optionsWrapper.appendChild(createOptionRow());

        wrapper.appendChild(clone);
        renameQuestionFields();
        attachCardEvents(clone);
        bindImageValidation(clone);
    });

    surveyForm?.addEventListener('submit', (event) => {
        const imageInputs = document.querySelectorAll('[data-option-image]');
        let hasTooLargeSingleFile = false;

        imageInputs.forEach((input) => {
            if (getSelectedFileSize(input) > MAX_IMAGE_SIZE) {
                hasTooLargeSingleFile = true;
            }
        });

        if (hasTooLargeSingleFile) {
            event.preventDefault();
            alert('Een afbeelding mag maximaal 2 MB groot zijn.');
            return;
        }

        if (!validateTotalImageSize()) {
            event.preventDefault();
        }
    });

    wrapper.querySelectorAll('.question-card').forEach((card) => attachCardEvents(card));
    renameQuestionFields();
    bindImageValidation(document);
</script>
</x-layouts::app>

    