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
                                'image_alt' => $option['image_alt'] ?? '',
                            ];
                        }

                        return [
                            'label' => (string) $option,
                            'existing_image' => null,
                            'image_alt' => '',
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
    @vite('resources/js/surveys/manager-form.js')

    <main class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <header class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-700 dark:bg-zinc-900">
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
        </header>

        @if ($errors->any())
            <section class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200">
                <p class="font-medium">Controleer de invoer:</p>
                <ul class="mt-2 list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </section>
        @endif

        <form
            method="POST"
            action="{{ $isEdit ? route('survey-manager.update', $survey) : route('survey-manager.store') }}"
            class="space-y-4"
            enctype="multipart/form-data"
            data-blob-upload-url="{{ env('VERCEL') && filled(env('BLOB_READ_WRITE_TOKEN')) ? '/api/blob-upload' : '' }}"
        >
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <section class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-700 dark:bg-zinc-900">
                <header class="mb-4">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Basisinformatie</h2>
                </header>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label for="title" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            Titel
                        </label>
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
                        <label for="description" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            Beschrijving
                        </label>
                        <textarea
                            id="description"
                            name="description"
                            rows="3"
                            class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                        >{{ old('description', $survey->description ?? '') }}</textarea>
                    </div>

                    <div class="grid gap-4 md:col-span-2 md:grid-cols-2">
                        <div>
                            <label for="is_active" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                Status
                            </label>
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

                        <div>
                            <label for="ends_at" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                Einddatum
                            </label>
                            <input
                                id="ends_at"
                                name="ends_at"
                                type="date"
                                value="{{ old('ends_at', isset($survey) ? $survey->ends_at?->toDateString() : '') }}"
                                class="w-full rounded-full border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                            >
                            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                                Laat leeg om de enquête actief te houden totdat je deze handmatig sluit.
                            </p>
                        </div>

                        <div>
                            <label for="reward_points" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                Beloningspunten
                            </label>
                            <input
                                id="reward_points"
                                name="reward_points"
                                type="number"
                                min="0"
                                step="1"
                                value="{{ old('reward_points', $isEdit ? ($survey->reward_points ?? '') : '') }}"
                                placeholder="Standaard: 10 punten"
                                class="w-full rounded-full border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                            >
                            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                                Laat dit veld leeg om standaard 10 punten toe te kennen.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-700 dark:bg-zinc-900">
                <header class="mb-4">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Vragen</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        Voeg antwoordopties toe als losse velden. Bij swipe kun je optioneel alt-tekst toevoegen voor extra toegankelijkheid.
                    </p>
                </header>

                <div id="questions-wrapper" class="space-y-4">
                    @foreach ($existingQuestions as $index => $question)
                        <article class="question-card rounded-xl border border-neutral-200 p-4 dark:border-neutral-700" tabindex="-1">
                            <header class="mb-4 flex items-center justify-between gap-3">
                                <h3 class="text-base font-semibold text-zinc-900 dark:text-white">
                                    Vraag <span class="question-number">{{ $index + 1 }}</span>
                                </h3>

                                <button type="button" class="remove-question btn-secondary">
                                    Verwijderen
                                </button>
                            </header>

                            <input
                                type="hidden"
                                name="questions[{{ $index }}][id]"
                                value="{{ $question['id'] ?? '' }}"
                                data-field="id"
                            >

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                        <span class="mb-1 block">Vraagtekst</span>
                                        <input
                                            type="text"
                                            name="questions[{{ $index }}][question]"
                                            value="{{ $question['question'] ?? '' }}"
                                            class="w-full rounded-full border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                                            required
                                            data-field="question"
                                        >
                                    </label>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                        <span class="mb-1 block">Type</span>
                                        <select
                                            name="questions[{{ $index }}][type]"
                                            class="question-type w-full rounded-full border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                                            data-field="type"
                                        >
                                            <option value="radio" @selected(($question['type'] ?? 'radio') === 'radio')>Radio</option>
                                            <option value="swipe" @selected(($question['type'] ?? '') === 'swipe')>Swipe</option>
                                            <option value="textarea" @selected(($question['type'] ?? '') === 'textarea')>Textarea</option>
                                        </select>
                                    </label>
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

                                <fieldset class="options-field md:col-span-2 {{ ($question['type'] ?? 'radio') === 'textarea' ? 'hidden' : '' }}">
                                    <legend class="block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                        Antwoordopties
                                    </legend>

                                    <div class="mb-2 flex items-center justify-between gap-3">
                                        <button type="button" class="add-option btn-secondary">
                                            Optie toevoegen
                                        </button>
                                    </div>

                                    <p class="mb-3 text-xs text-zinc-500 dark:text-zinc-400">
                                        Voeg bij swipe-afbeeldingen eventueel een korte, beschrijvende alt-tekst toe.
                                    </p>

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
                                                $imageAlt = is_array($option) ? ($option['image_alt'] ?? '') : '';
                                                $isSwipe = ($question['type'] ?? 'radio') === 'swipe';
                                                $existingImageUrl = $existingImage
                                                    ? (filter_var($existingImage, FILTER_VALIDATE_URL)
                                                        ? $existingImage
                                                        : Storage::disk(config('filesystems.survey_images_disk', 'public'))->url($existingImage))
                                                    : null;
                                            @endphp

                                            <article class="option-row rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                                                <div class="grid gap-3 md:grid-cols-12">
                                                    <div class="option-label-col {{ $isSwipe ? 'md:col-span-6' : 'md:col-span-11' }}">
                                                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                                            <span class="mb-1 block">Antwoordoptie</span>
                                                            <input
                                                                type="text"
                                                                name="questions[{{ $index }}][options][{{ $optionIndex }}][label]"
                                                                value="{{ $label }}"
                                                                class="w-full rounded-full border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                                                                placeholder="Voer een antwoordoptie in"
                                                                data-option-label
                                                            >
                                                        </label>
                                                    </div>

                                                    <div class="swipe-image-field {{ $isSwipe ? 'md:col-span-4' : 'hidden' }}">
                                                        <input
                                                            type="hidden"
                                                            name="questions[{{ $index }}][options][{{ $optionIndex }}][existing_image]"
                                                            value="{{ $existingImage }}"
                                                            data-option-existing-image
                                                        >

                                                        <div class="mb-2 {{ $existingImageUrl ? '' : 'hidden' }}" data-image-preview-wrapper>
                                                            <img
                                                                src="{{ $existingImageUrl ?? '' }}"
                                                                alt="{{ $imageAlt ?: ($label ?: 'Voorbeeld van antwoordoptie') }}"
                                                                class="h-20 w-full rounded-xl border border-neutral-200 object-cover dark:border-neutral-700"
                                                                data-image-preview
                                                            >
                                                        </div>

                                                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                                            <span class="mb-1 block">Afbeelding</span>
                                                            <input
                                                                type="file"
                                                                name="questions[{{ $index }}][options][{{ $optionIndex }}][image]"
                                                                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                                                class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                                                                data-option-image
                                                            >
                                                        </label>

                                                        <label class="mt-3 block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                                            <span class="mb-1 flex items-center gap-2">
                                                                <span>Alt-tekst</span>
                                                                <span class="group relative inline-flex">
                                                                    <span
                                                                        tabindex="0"
                                                                        role="note"
                                                                        aria-label="Uitleg over alt-tekst"
                                                                        class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-zinc-300 text-xs font-semibold text-zinc-600 dark:border-zinc-600 dark:text-zinc-200"
                                                                    >
                                                                        i
                                                                    </span>
                                                                    <span class="pointer-events-none invisible absolute bottom-full left-1/2 z-10 mb-2 w-56 -translate-x-1/2 rounded-xl bg-zinc-900 px-3 py-2 text-xs font-normal text-white opacity-0 shadow-lg transition-opacity duration-150 group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100 dark:bg-zinc-700">
                                                                        Dit is de opgelezen beschrijving voor mensen die deze afbeelding niet kunnen zien.
                                                                    </span>
                                                                </span>
                                                            </span>
                                                            <input
                                                                type="text"
                                                                name="questions[{{ $index }}][options][{{ $optionIndex }}][image_alt]"
                                                                value="{{ $imageAlt }}"
                                                                maxlength="255"
                                                                class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                                                                placeholder="Beschrijf kort wat er op de afbeelding staat"
                                                                data-option-image-alt
                                                            >
                                                        </label>
                                                    </div>

                                                    <div class="{{ $isSwipe ? 'md:col-span-2' : 'md:col-span-1' }} option-remove-col flex items-start md:items-center">
                                                        <button type="button" class="remove-option btn-secondary w-full">
                                                            Verwijderen
                                                        </button>
                                                    </div>
                                                </div>
                                            </article>
                                        @endforeach
                                    </div>
                                </fieldset>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="mt-6 flex justify-center">
                    <button type="button" id="add-question" class="btn-primary">
                        Vraag toevoegen
                    </button>
                </div>
            </section>

            <div class="flex flex-wrap justify-end gap-3">
                <a href="{{ route('survey-manager.index') }}" class="btn-secondary">
                    Annuleren
                </a>
                <button type="submit" class="btn-primary">
                    {{ $isEdit ? 'Wijzigingen opslaan' : 'Enquête opslaan' }}
                </button>
            </div>
        </form>
    </main>

    <template id="question-template">
        <article class="question-card rounded-xl border border-neutral-200 p-4 dark:border-neutral-700" tabindex="-1">
            <header class="mb-4 flex items-center justify-between gap-3">
                <h3 class="text-base font-semibold text-zinc-900 dark:text-white">
                    Vraag <span class="question-number"></span>
                </h3>

                <button type="button" class="remove-question btn-secondary">
                    Verwijderen
                </button>
            </header>

            <input type="hidden" data-field="id" value="">

            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                        <span class="mb-1 block">Vraagtekst</span>
                        <input
                            type="text"
                            data-field="question"
                            class="w-full rounded-full border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                            required
                        >
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                        <span class="mb-1 block">Type</span>
                        <select
                            data-field="type"
                            class="question-type w-full rounded-full border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                        >
                            <option value="radio">Radio</option>
                            <option value="swipe">Swipe</option>
                            <option value="textarea">Textarea</option>
                        </select>
                    </label>
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

                <fieldset class="options-field md:col-span-2">
                    <legend class="block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                        Antwoordopties
                    </legend>

                    <div class="mb-2 flex items-center justify-between gap-3">
                        <button type="button" class="add-option btn-secondary">
                            Optie toevoegen
                        </button>
                    </div>

                    <p class="mb-3 text-xs text-zinc-500 dark:text-zinc-400">
                        Voeg bij swipe-afbeeldingen eventueel een korte, beschrijvende alt-tekst toe.
                    </p>

                    <div class="options-wrapper space-y-3"></div>
                </fieldset>
            </div>
        </article>
    </template>

    <template id="option-template">
        <article class="option-row rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
            <div class="grid gap-3 md:grid-cols-12">
                <div class="option-label-col md:col-span-11">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                        <span class="mb-1 block">Antwoordoptie</span>
                        <input
                            type="text"
                            data-option-label
                            class="w-full rounded-full border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                            placeholder="Voer een antwoordoptie in"
                        >
                    </label>
                </div>

                <div class="swipe-image-field hidden md:col-span-4">
                    <input type="hidden" data-option-existing-image value="">

                    <div class="mb-2 hidden" data-image-preview-wrapper>
                        <img
                            src=""
                            alt="Voorbeeld van antwoordoptie"
                            class="h-20 w-full rounded-xl border border-neutral-200 object-cover dark:border-neutral-700"
                            data-image-preview
                        >
                    </div>

                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                        <span class="mb-1 block">Afbeelding</span>
                        <input
                            type="file"
                            data-option-image
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                        >
                    </label>

                    <label class="mt-3 block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                        <span class="mb-1 flex items-center gap-2">
                            <span>Alt-tekst</span>
                            <span class="group relative inline-flex">
                                <span
                                    tabindex="0"
                                    role="note"
                                    aria-label="Uitleg over alt-tekst"
                                    class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-zinc-300 text-xs font-semibold text-zinc-600 dark:border-zinc-600 dark:text-zinc-200"
                                >
                                    i
                                </span>
                                <span class="pointer-events-none invisible absolute bottom-full left-1/2 z-10 mb-2 w-56 -translate-x-1/2 rounded-xl bg-zinc-900 px-3 py-2 text-xs font-normal text-white opacity-0 shadow-lg transition-opacity duration-150 group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100 dark:bg-zinc-700">
                                    Dit is de opgelezen beschrijving voor mensen die deze afbeelding niet kunnen zien.
                                </span>
                            </span>
                        </span>
                        <input
                            type="text"
                            data-option-image-alt
                            maxlength="255"
                            class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                            placeholder="Beschrijf kort wat er op de afbeelding staat"
                        >
                    </label>
                </div>

                <div class="option-remove-col flex items-start md:col-span-1 md:items-center">
                    <button type="button" class="remove-option btn-secondary w-full">
                        Verwijderen
                    </button>
                </div>
            </div>
        </article>
    </template>
</x-layouts::app>
