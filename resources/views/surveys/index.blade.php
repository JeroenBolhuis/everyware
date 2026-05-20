<x-layout>
    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 sm:py-10">
        <div>
            <h1 class="text-2xl font-bold text-zinc-950 sm:text-3xl">Enquêtes</h1>
            <p class="mt-2 text-sm text-zinc-600">Kies een actieve enquête om feedback te geven.</p>

            <div class="mt-6 flex flex-col gap-3 border-y border-zinc-200 py-4 sm:flex-row sm:items-end sm:gap-4">
                <div class="w-full sm:flex-1">
                    <label for="search" class="mb-1 block text-sm font-medium text-zinc-700">Zoeken op titel</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}"
                           placeholder="Zoeken..."
                           class="w-full rounded-lg border-zinc-300 px-4 py-3 shadow-sm focus:border-red-500 focus:ring-red-500">
                </div>

                <div class="w-full sm:flex-1">
                    <label for="status" class="mb-1 block text-sm font-medium text-zinc-700">Status</label>
                    <select name="status" id="status"
                            class="w-full rounded-lg border-zinc-300 px-4 py-3 shadow-sm focus:border-red-500 focus:ring-red-500">
                        <option value="">Alles</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actief
                        </option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactief
                        </option>
                    </select>
                </div>

                <button id="clear-btn" type="button" class="btn-secondary w-full sm:w-auto">
                    Wissen
                </button>
            </div>

            <div id="surveys-container" class="mt-6">
                <div class="space-y-4">
                    @forelse ($surveys as $survey)
                        <div class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm sm:p-5">
                            <div class="flex flex-col sm:flex-row justify-between items-start gap-4">
                                <div class="flex-1 min-w-0">
                                    <h2 class="break-words text-lg font-semibold text-zinc-950 sm:text-xl">{{ $survey->title }}</h2>
                                    <x-truncated-text
                                        :text="$survey->description"
                                        :maxLength="150"
                                        class="mt-1 text-sm text-zinc-600 sm:text-base"
                                    />
                                    <div class="mt-3 flex flex-wrap gap-2 items-center">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $survey->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $survey->is_active ? 'Actief' : 'Inactief' }}
                                    </span>
                                        <span class="text-xs text-zinc-500 sm:text-sm">
                                        {{ $survey->questions->count() }} vragen
                                    </span>
                                    </div>
                                </div>
                                <div class="w-full sm:w-auto ml-0 sm:ml-4">
                                    @if ($survey->is_active)
                                        <a href="{{ route('survey.show', $survey) }}" class="btn-primary block w-full text-center sm:w-auto">
                                            Enquete invullen
                                        </a>
                                    @else
                                        <span class="btn-disabled w-full sm:w-auto block text-center">
                                            Inactief
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-12 text-center">
                            <p class="text-zinc-500">Geen enquetes gevonden die overeenkomen met je criteria.</p>
                        </div>
                    @endforelse
                </div>

                @if ($surveys->hasPages())
                    <div class="mt-6">
                        {{ $surveys->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>{{-- end #surveys-container --}}
        </div>
    </div>

    <script>
        const searchInput = document.getElementById('search');
        const statusSelect = document.getElementById('status');
        const clearBtn = document.getElementById('clear-btn');
        const container = document.getElementById('surveys-container');
        let debounceTimer;

        function initializeTruncatedText() {
            const buttons = document.querySelectorAll('.toggle-more-info');

            buttons.forEach(button => {
                // Remove old listener if exists
                button.removeEventListener('click', handleTruncateClick);
                // Add new listener
                button.addEventListener('click', handleTruncateClick);
            });
        }

        function handleTruncateClick(e) {
            e.preventDefault();
            const textSpan = this.previousElementSibling; // Get the span before the button
            const isExpanded = this.getAttribute('aria-expanded') === 'true';

            const fullText = JSON.parse(textSpan.getAttribute('data-full-text'));
            const truncatedText = JSON.parse(textSpan.getAttribute('data-truncated-text'));

            if (isExpanded) {
                // Collapse
                textSpan.textContent = truncatedText + '...';
                this.textContent = 'Meer info';
                this.setAttribute('aria-expanded', 'false');
            } else {
                // Expand
                textSpan.textContent = fullText;
                this.textContent = 'Minder info';
                this.setAttribute('aria-expanded', 'true');
            }
        }

        function fetchSurveys(page) {
            const search = searchInput.value;
            const status = statusSelect.value;
            const params = new URLSearchParams({search, status, page: page || 1});

            fetch(`{{ route('surveys.index') }}?${params}`, {
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            })
                .then(r => r.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newContainer = doc.getElementById('surveys-container');
                    if (newContainer) container.innerHTML = newContainer.innerHTML;

                    // Re-initialize truncated text handlers
                    initializeTruncatedText();

                    // Re-attach pagination link listeners
                    attachPaginationListeners();

                    // Update browser URL without reload
                    const url = new URL(window.location.href);
                    url.searchParams.set('search', search);
                    url.searchParams.set('status', status);
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

        searchInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => fetchSurveys(1), 300);
        });

        statusSelect.addEventListener('change', () => fetchSurveys(1));

        clearBtn.addEventListener('click', () => {
            searchInput.value = '';
            statusSelect.value = '';
            fetchSurveys(1);
        });

        // Initialize on page load
        initializeTruncatedText();
        attachPaginationListeners();
    </script>
</x-layout>
