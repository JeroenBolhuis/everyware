<x-layout>
    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 sm:py-10">
        <div>
            <h1 class="text-2xl font-bold text-zinc-950 sm:text-3xl">Enquêtes</h1>
            <p class="mt-2 text-sm text-zinc-600">Kies een actieve enquête om feedback te geven.</p>

            <div class="mt-6 flex flex-col gap-3 border-y border-zinc-200 py-4 sm:flex-row sm:items-end sm:justify-between sm:gap-4">
                <div class="w-full md:w-64 md:flex-none">
                    <label for="sort" class="mb-1 block text-sm font-medium text-zinc-700">Sorteren op</label>
                    <select id="sort" name="sort" class="w-full rounded-lg border-zinc-300 px-4 py-3 shadow-sm focus:border-red-500 focus:ring-red-500">
                        <option value="latest" @selected(request('sort', 'latest') === 'latest')>Nieuwste eerst</option>
                        <option value="reward_points_desc" @selected(request('sort') === 'reward_points_desc')>Meeste punten eerst</option>
                    </select>
                </div>

                <button id="clear-btn" type="button" class="btn-secondary w-full sm:w-auto sm:flex-none">
                    Wissen
                </button>
            </div>

            <div id="surveys-container" class="mt-6">
                <div class="space-y-4">
                    @forelse ($surveys as $survey)
                        @php
                            $hasCompletedSurvey = in_array($survey->id, $completedSurveyIds, true);
                            $rewardPoints = (int) $survey->reward_points;
                            $rewardUnit = $rewardPoints === 1 ? 'punt' : 'punten';
                        @endphp

                        <div class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm sm:p-5">
                            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                <div class="flex-1 min-w-0">
                                    <h2 class="break-words text-lg font-semibold text-zinc-950 sm:text-xl">{{ $survey->title }}</h2>
                                    <x-truncated-text
                                        :text="$survey->description"
                                        :maxLength="150"
                                        class="mt-1 text-sm text-zinc-600 sm:text-base"
                                    />
                                    <div class="mt-3 flex flex-wrap gap-2 items-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-green-100 text-xs font-medium text-green-800">
                                            Actief
                                        </span>
                                        <span class="text-xs text-zinc-500 sm:text-sm">
                                            Einddatum: {{ $survey->ends_at?->format('d-m-Y') ?? 'Geen einddatum' }}
                                        </span>
                                        <span class="text-xs text-zinc-500 sm:text-sm">
                                            {{ $survey->questions_count }} vragen
                                        </span>
                                    </div>
                                </div>
                                <div class="flex w-full flex-col gap-3 sm:flex-row sm:items-center sm:justify-between md:ml-4 md:w-48 md:flex-none md:flex-col md:items-stretch md:justify-start">
                                    @if ($hasCompletedSurvey)
                                        <span class="btn-disabled w-full text-center sm:w-auto sm:min-w-40 md:w-full md:min-w-0 lg:max-w-none">
                                            Enquête al ingevuld
                                        </span>
                                    @else
                                        <a href="{{ route('survey.show', $survey) }}" class="btn-primary w-full text-center sm:w-auto sm:min-w-40 md:w-full md:min-w-0 lg:max-w-none">
                                            Enquête invullen
                                        </a>
                                    @endif

                                    <div class="flex w-full items-center justify-between gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800 shadow-sm sm:w-auto sm:min-w-40 md:w-full md:min-w-0">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-red-700">Beloning</span>
                                        <span class="whitespace-nowrap text-right text-lg font-bold leading-none">
                                            {{ $rewardPoints }} <span class="text-sm font-semibold">{{ $rewardUnit }}</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-12 text-center">
                            <p class="text-zinc-500">Geen enquêtes gevonden die overeenkomen met je criteria.</p>
                        </div>
                    @endforelse
                </div>

                @if ($surveys->hasPages())
                    <div class="mt-6">
                        {{ $surveys->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        const sortInput = document.getElementById('sort');
        const clearBtn = document.getElementById('clear-btn');
        const container = document.getElementById('surveys-container');

        function initializeTruncatedText() {
            const buttons = document.querySelectorAll('.toggle-more-info');

            buttons.forEach(button => {
                button.removeEventListener('click', handleTruncateClick);
                button.addEventListener('click', handleTruncateClick);
            });
        }

        function handleTruncateClick(e) {
            e.preventDefault();
            const textSpan = this.previousElementSibling;
            const isExpanded = this.getAttribute('aria-expanded') === 'true';

            const fullText = JSON.parse(textSpan.getAttribute('data-full-text'));
            const truncatedText = JSON.parse(textSpan.getAttribute('data-truncated-text'));

            if (isExpanded) {
                textSpan.textContent = truncatedText + '...';
                this.textContent = 'Meer info';
                this.setAttribute('aria-expanded', 'false');
            } else {
                textSpan.textContent = fullText;
                this.textContent = 'Minder info';
                this.setAttribute('aria-expanded', 'true');
            }
        }

        function fetchSurveys(page) {
            const sort = sortInput.value;
            const params = new URLSearchParams({sort, page: page || 1});

            fetch(`{{ route('surveys.index') }}?${params}`, {
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            })
                .then(r => r.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newContainer = doc.getElementById('surveys-container');
                    if (newContainer) container.innerHTML = newContainer.innerHTML;

                    initializeTruncatedText();
                    attachPaginationListeners();

                    const url = new URL(window.location.href);
                    url.searchParams.delete('search');
                    url.searchParams.set('sort', sort);
                    url.searchParams.set('page', page || 1);
                    window.history.replaceState({}, '', url);
                });
        }

        function attachPaginationListeners() {
            container.querySelectorAll('nav a[href]').forEach(link => {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    const url = new URL(this.href);
                    fetchSurveys(url.searchParams.get('page') || 1);
                });
            });
        }

        clearBtn.addEventListener('click', () => {
            sortInput.value = 'latest';
            fetchSurveys(1);
        });

        sortInput.addEventListener('change', () => fetchSurveys(1));

        initializeTruncatedText();
        attachPaginationListeners();
    </script>
</x-layout>
