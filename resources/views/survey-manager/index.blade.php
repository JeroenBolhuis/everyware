<x-layouts::app :title="__('Enquetes')">
    <section class="w-full" aria-labelledby="survey-manager-page-title">
        <x-survey-manager.layout
            :heading="__('Enquête-overzicht')"
            :subheading="__('Maak, beheer en sluit enquêtes voor LIC-medewerkers en administrators.')"
        >
            <x-slot:actions>
                <flux:button
                    variant="primary"
                    icon="plus"
                    :href="route('survey-manager.create')"
                    aria-label="{{ __('Nieuwe enquête aanmaken') }}"
                >
                    {{ __('Nieuwe enquête') }}
                </flux:button>
            </x-slot:actions>

            @if (session('status'))
                <div
                    class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800/70 dark:bg-emerald-950/30 dark:text-emerald-200"
                    role="status"
                    aria-live="polite"
                >
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->has('questions'))
                <div
                    class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200"
                    role="alert"
                    aria-live="assertive"
                >
                    {{ $errors->first('questions') }}
                </div>
            @endif

            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-zinc-900">
                    <flux:text class="text-sm text-zinc-500">{{ __('Totaal') }}</flux:text>
                    <flux:heading class="mt-2 text-3xl">{{ $stats['total'] }}</flux:heading>
                </div>
                <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-zinc-900">
                    <flux:text class="text-sm text-zinc-500">{{ __('Actief') }}</flux:text>
                    <flux:heading class="mt-2 text-3xl">{{ $stats['active'] }}</flux:heading>
                </div>
                <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-zinc-900">
                    <flux:text class="text-sm text-zinc-500">{{ __('Gesloten') }}</flux:text>
                    <flux:heading class="mt-2 text-3xl">{{ $stats['closed'] }}</flux:heading>
                </div>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm sm:p-6 dark:border-neutral-700 dark:bg-zinc-900">
                <form
                    id="survey-filter-form"
                    method="GET"
                    action="{{ route('survey-manager.index') }}"
                    class="grid gap-4 md:grid-cols-[1fr_220px_auto] md:items-end"
                >
                    <flux:field>
                        <flux:label for="search">{{ __('Zoek op titel of maker') }}</flux:label>
                        <flux:input
                            id="search"
                            name="search"
                            type="search"
                            :value="request('search')"
                            list="survey-search-suggestions"
                            placeholder="{{ __('Bijvoorbeeld: studentfeedback of naam van maker') }}"
                        />
                    </flux:field>

                    @if ($canFilterByCreator)
                        <datalist id="survey-search-suggestions">
                            @foreach ($licEmployees as $licEmployee)
                                <option value="{{ $licEmployee->name }}"></option>
                            @endforeach
                        </datalist>
                    @endif

                    <flux:field>
                        <flux:label for="status">{{ __('Status') }}</flux:label>
                        <select
                            id="status"
                            name="status"
                            class="block h-10 w-full rounded-lg border border-zinc-200 border-b-zinc-300/80 bg-white px-3 text-sm text-zinc-700 shadow-xs dark:border-white/10 dark:bg-white/10 dark:text-zinc-300"
                        >
                            <option value="" @selected(request('status') === null || request('status') === '')>{{ __('Alles') }}</option>
                            <option value="active" @selected(request('status') === 'active')>{{ __('Actief') }}</option>
                            <option value="closed" @selected(request('status') === 'closed')>{{ __('Gesloten') }}</option>
                        </select>
                    </flux:field>

                    <div class="flex flex-wrap gap-2">
                        <flux:button type="submit" variant="primary" icon="magnifying-glass">
                            {{ __('Zoeken') }}
                        </flux:button>
                        <flux:button
                            variant="ghost"
                            icon="arrow-path"
                            :href="route('survey-manager.index')"
                            aria-label="{{ __('Filters resetten') }}"
                        >
                            {{ __('Reset') }}
                        </flux:button>
                    </div>
                </form>
            </div>

            <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-zinc-900">
                <div class="border-b border-neutral-200 px-4 py-4 sm:px-6 dark:border-neutral-700">
                    <flux:heading size="lg">{{ __('Bestaande enquêtes') }}</flux:heading>
                </div>

                <div class="divide-y divide-neutral-200 dark:divide-neutral-700">
                    @forelse ($surveys as $survey)
                        <article
                            class="flex flex-col gap-5 px-4 py-5 sm:px-6 lg:flex-row lg:items-start lg:justify-between"
                            aria-labelledby="survey-title-{{ $survey->id }}"
                        >
                            <div class="min-w-0 flex-1 space-y-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <flux:heading id="survey-title-{{ $survey->id }}" size="lg">
                                        {{ $survey->title }}
                                    </flux:heading>

                                    @if ($survey->is_active)
                                        @if ($survey->hasEnded())
                                            <flux:badge color="amber" size="sm">{{ __('Verlopen') }}</flux:badge>
                                        @else
                                            <flux:badge color="emerald" size="sm">{{ __('Actief') }}</flux:badge>
                                        @endif
                                    @else
                                        <flux:badge color="zinc" size="sm">{{ __('Gesloten') }}</flux:badge>
                                    @endif
                                </div>

                                <flux:text class="text-sm text-zinc-600 dark:text-zinc-300">
                                    {{ $survey->description ?: __('Geen beschrijving toegevoegd.') }}
                                </flux:text>

                                <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-zinc-500 dark:text-zinc-400">
                                    <flux:text class="text-sm text-zinc-500">{{ $survey->questions_count }} {{ __('vragen') }}</flux:text>
                                    <flux:text class="text-sm text-zinc-500">{{ $survey->responses_count }} {{ __('reactie(s)') }}</flux:text>
                                    <flux:text class="text-sm text-zinc-500">{{ __('Aangemaakt op :date', ['date' => $survey->created_at->format('d-m-Y')]) }}</flux:text>
                                    <flux:text class="text-sm text-zinc-500">{{ __('Einddatum') }}: {{ $survey->ends_at?->format('d-m-Y') ?? __('Geen einddatum') }}</flux:text>
                                    <flux:text class="text-sm text-zinc-500">{{ __('Doelgroep: :target', ['target' => \App\Support\Academies::label($survey->target_academy)]) }}</flux:text>

                                    @if (auth()->user()?->isAdmin() || auth()->user()?->isLicEmployee())
                                        <flux:text class="text-sm text-zinc-500">{{ __('Maker: :name', ['name' => $survey->creator?->name ?? __('Onbekend')]) }}</flux:text>
                                    @endif
                                </div>
                            </div>

                            <div class="flex w-full flex-col gap-3 lg:w-auto lg:min-w-[20rem] lg:items-end">
                                <div class="flex flex-wrap gap-2 lg:justify-end">
                                    <flux:dropdown position="bottom" align="end">
                                        <flux:button variant="ghost" size="sm" icon-trailing="chevron-down">
                                            {{ __('Exporteer') }}
                                        </flux:button>

                                        <flux:menu>
                                            @foreach (['xlsx' => 'Excel (.xlsx)', 'csv' => 'CSV (.csv)'] as $format => $label)
                                                <flux:menu.item href="{{ route('admin.surveys.export', ['survey' => $survey, 'format' => $format]) }}">
                                                    {{ $label }}
                                                </flux:menu.item>
                                            @endforeach
                                        </flux:menu>
                                    </flux:dropdown>
                                </div>

                                @if ($survey->isAcceptingResponses())
                                    <div
                                        x-data="{ copied: false }"
                                        class="flex w-full items-center gap-1 rounded-xl border border-neutral-200 bg-zinc-50 px-3 py-2 dark:border-neutral-700 dark:bg-zinc-800/50 lg:max-w-xl"
                                    >
                                        <input
                                            x-ref="shareLink"
                                            type="text"
                                            readonly
                                            value="{{ route('survey.share.show', $survey->share_token) }}"
                                            class="min-w-0 flex-1 bg-transparent text-xs text-zinc-600 focus:outline-none dark:text-zinc-300"
                                            id="share-link-{{ $survey->id }}"
                                        >
                                        <flux:tooltip :content="__('Kopieer link')">
                                            <flux:button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                aria-label="{{ __('Kopieer openbare enquête-link voor :title', ['title' => $survey->title]) }}"
                                                x-on:click="
                                                    const input = $refs.shareLink;
                                                    const markCopied = () => {
                                                        copied = true;
                                                        setTimeout(() => copied = false, 2000);
                                                    };
                                                    const fallbackCopy = () => {
                                                        input.select();
                                                        input.setSelectionRange(0, input.value.length);
                                                        document.execCommand('copy');
                                                        markCopied();
                                                    };
                                                    if (navigator.clipboard && window.isSecureContext) {
                                                        navigator.clipboard.writeText(input.value).then(markCopied).catch(fallbackCopy);
                                                    } else {
                                                        fallbackCopy();
                                                    }
                                                "
                                            >
                                                <flux:icon.document-duplicate x-show="!copied" variant="mini" />
                                                <flux:icon.check x-cloak x-show="copied" variant="mini" class="text-emerald-600 dark:text-emerald-400" />
                                            </flux:button>
                                        </flux:tooltip>
                                        <flux:modal.trigger name="survey-qr-code-{{ $survey->id }}">
                                            <flux:button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                icon="qr-code"
                                                aria-label="{{ __('Bekijk QR-code voor openbare enquête-link van :title', ['title' => $survey->title]) }}"
                                            />
                                        </flux:modal.trigger>
                                    </div>

                                    <flux:modal name="survey-qr-code-{{ $survey->id }}" class="max-w-md">
                                        <div class="space-y-6">
                                            <div>
                                                <flux:heading size="lg">{{ __('QR-code') }}</flux:heading>
                                                <flux:subheading class="mt-2">{{ $survey->title }}</flux:subheading>
                                            </div>

                                            <div class="flex justify-center">
                                                <div class="aspect-square w-full max-w-72 rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-700">
                                                    <img
                                                        src="{{ route('survey-manager.qr-code', $survey) }}"
                                                        alt="{{ __('QR-code voor openbare enquête-link van :title', ['title' => $survey->title]) }}"
                                                        class="h-full w-full"
                                                    >
                                                </div>
                                            </div>

                                            <flux:text class="text-sm text-zinc-600 dark:text-zinc-300">
                                                {{ __('Je kunt de QR-code kopiëren door met de rechtermuisknop op de afbeelding te klikken en Afbeelding kopiëren te kiezen.') }}
                                            </flux:text>

                                            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                                <flux:modal.close>
                                                    <flux:button variant="ghost" type="button">
                                                        {{ __('Sluiten') }}
                                                    </flux:button>
                                                </flux:modal.close>

                                                <flux:button
                                                    variant="primary"
                                                    icon="arrow-down-tray"
                                                    :href="route('survey-manager.qr-code', ['survey' => $survey, 'download' => 1])"
                                                >
                                                    {{ __('Download') }}
                                                </flux:button>
                                            </div>
                                        </div>
                                    </flux:modal>
                                @endif

                                <div class="flex flex-wrap gap-2 lg:justify-end">
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        icon="pencil-square"
                                        :href="route('survey-manager.edit', $survey)"
                                        aria-label="{{ __('Bewerk enquête :title', ['title' => $survey->title]) }}"
                                    >
                                        {{ __('Bewerken') }}
                                    </flux:button>

                                    @if ($survey->isAcceptingResponses())
                                        <flux:button
                                            variant="ghost"
                                            size="sm"
                                            icon="arrow-top-right-on-square"
                                            :href="route('survey.share.show', $survey->share_token)"
                                            target="_blank"
                                            rel="noopener"
                                            aria-label="{{ __('Open actieve enquête :title in nieuw tabblad', ['title' => $survey->title]) }}"
                                        >
                                            {{ __('Open enquête') }}
                                        </flux:button>

                                        <form method="POST" action="{{ route('survey-manager.close', $survey) }}" onsubmit="return confirm('{{ __('Weet je zeker dat je deze enquête wilt sluiten?') }}');">
                                            @csrf
                                            @method('PATCH')
                                            <flux:button
                                                type="submit"
                                                variant="danger"
                                                size="sm"
                                                icon="lock-closed"
                                                aria-label="{{ __('Sluit enquête :title', ['title' => $survey->title]) }}"
                                            >
                                                {{ __('Sluiten') }}
                                            </flux:button>
                                        </form>
                                    @else
                                        <flux:badge color="zinc" size="sm">{{ __('Niet meer invulbaar') }}</flux:badge>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="px-4 py-10 text-center sm:px-6">
                            <flux:heading size="lg">{{ __('Geen enquêtes gevonden.') }}</flux:heading>

                            <flux:text class="mt-2 text-sm text-zinc-500">
                                @if (request()->hasAny(['search', 'status']))
                                    {{ __('Er zijn geen enquêtes die passen bij de gekozen filters.') }}
                                @else
                                    {{ __('Er zijn nog geen enquêtes aangemaakt.') }}
                                @endif
                            </flux:text>

                            @if (request()->hasAny(['search', 'status']))
                                <flux:button
                                    class="mt-4"
                                    variant="ghost"
                                    icon="arrow-path"
                                    :href="route('survey-manager.index')"
                                >
                                    {{ __('Filters resetten') }}
                                </flux:button>
                            @endif
                        </div>
                    @endforelse
                </div>

                @if ($surveys->hasPages())
                    <div class="border-t border-neutral-200 px-4 py-4 sm:px-6 dark:border-neutral-700">
                        {{ $surveys->links() }}
                    </div>
                @endif
            </div>
        </x-survey-manager.layout>
    </section>
</x-layouts::app>
