<x-layouts::app :title="__('Enquetes')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div
            class="flex flex-col gap-3 rounded-xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-700 dark:bg-zinc-900 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Enquête-overzicht</h1>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Maak, beheer en sluit enquêtes voor LIC-medewerkers en administrators.
                </p>
            </div>

            <a
                href="{{ route('survey-manager.create') }}"
                class="btn-primary"
            >
                Nieuwe enquête
            </a>
        </div>

        @if (session('status'))
            <div
                class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-900/50 dark:bg-green-950/40 dark:text-green-200">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->has('questions'))
            <div
                class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200">
                {{ $errors->first('questions') }}
            </div>
        @endif

        <div class="grid gap-4 md:grid-cols-3">
            <div
                class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-zinc-900">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Totaal</p>
                <p class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">{{ $stats['total'] }}</p>
            </div>
            <div
                class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-zinc-900">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Actief</p>
                <p class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">{{ $stats['active'] }}</p>
            </div>
            <div
                class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-zinc-900">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Gesloten</p>
                <p class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">{{ $stats['closed'] }}</p>
            </div>
        </div>

        <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-700 dark:bg-zinc-900">
            <form method="GET" action="{{ route('survey-manager.index') }}" class="grid gap-4 md:grid-cols-[1fr_220px_auto] md:items-end">
                <div>
                    <label for="search" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">Zoek op
                        titel</label>
                    <input
                        id="search"
                        name="search"
                        type="text"
                        value="{{ request('search') }}"
                        placeholder="Bijvoorbeeld: studentfeedback"
                        class="w-full rounded-full px-4 py-3 border border-zinc-300 bg-white text-sm text-zinc-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                    >
                </div>

                <div>
                    <label for="status"
                           class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">Status</label>
                    <select
                        id="status"
                        name="status"
                        class="w-full rounded-full px-4 py-3 border border-zinc-300 bg-white text-sm text-zinc-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                    >
                        <option value="">Alles</option>
                        <option value="active" @selected(request('status') === 'active')>Actief</option>
                        <option value="closed" @selected(request('status') === 'closed')>Gesloten</option>
                    </select>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="btn-secondary">
                        Filteren
                    </button>
                    <a href="{{ route('survey-manager.index') }}" class="btn-secondary">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-zinc-900">
            <div class="border-b border-neutral-200 px-6 py-4 dark:border-neutral-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Bestaande enquêtes</h2>
            </div>

            <div class="divide-y divide-neutral-200 dark:divide-neutral-700">
                @forelse ($surveys as $survey)
                    <div class="flex flex-col gap-4 px-6 py-5 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $survey->title }}</h3>

                                @if ($survey->is_active)
                                    <span
                                        class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-800 dark:bg-green-950/50 dark:text-green-200">Actief</span>
                                @else
                                    <span
                                        class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-800 dark:bg-red-950/50 dark:text-red-200">Gesloten</span>
                                @endif
                            </div>

                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ $survey->description ?: 'Geen beschrijving toegevoegd.' }}</p>

                            <div class="mt-3 flex flex-wrap gap-4 text-sm text-zinc-500 dark:text-zinc-400">
                                <span>{{ $survey->questions_count }} vragen</span>
                                <span>{{ $survey->responses_count }} Reactie(s)</span>
                                <span>Aangemaakt op {{ $survey->created_at->format('d-m-Y') }}</span>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            @if ($survey->is_active)
                                <div class="flex w-full items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 dark:border-neutral-700 dark:bg-zinc-800">
                                    <input
                                        type="text"
                                        readonly
                                        value="{{ route('survey.share.show', $survey->share_token) }}"
                                        class="min-w-0 flex-1 bg-transparent text-xs text-zinc-600 focus:outline-none dark:text-zinc-300"
                                        id="share-link-{{ $survey->id }}"
                                    >
                                    <button
                                        type="button"
                                        onclick="navigator.clipboard.writeText('{{ route('survey.share.show', $survey->share_token) }}').then(() => { this.textContent = 'Gekopieerd!'; setTimeout(() => this.textContent = 'Kopieer', 2000); })"
                                        class="shrink-0 text-xs text-indigo-600 hover:underline dark:text-indigo-400"
                                    >Kopieer</button>
                                </div>
                            @endif

                            <a
                                href="{{ route('survey-manager.edit', $survey) }}"
                                class="btn-secondary"
                            >
                                Bewerken
                            </a>

                            @if ($survey->is_active)
                                <a href="{{ route('survey.share.show', $survey->share_token) }}" target="_blank" class="btn-secondary">
                                    Open enquête
                                </a>

                               <form method="POST" action="{{ route('survey-manager.close', $survey) }}"  onsubmit="return confirm('Weet je zeker dat je deze enquête wilt sluiten?');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn-primary">
                                        Sluiten
                                    </button>
                                </form>
                            @else
                                <span class="btn-disabled">
                                    Niet meer invulbaar
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">
                        Er zijn nog geen enquêtes gevonden.
                    </div>
                @endforelse
            </div>

            @if ($surveys->hasPages())
                <div class="border-t border-neutral-200 px-6 py-4 dark:border-neutral-700">
                    {{ $surveys->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts::app>




