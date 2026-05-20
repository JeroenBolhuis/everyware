<x-layout>
    <div class="max-w-7xl mx-auto py-6 sm:py-10 px-3 sm:px-4 md:px-6 lg:px-8">
        <div class="bg-white border rounded-lg sm:rounded-2xl shadow-md p-4 sm:p-6 md:p-8">
            <h1 class="text-2xl sm:text-3xl font-bold mb-6">Enquete overzicht</h1>

            <!-- Filters -->
            <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:gap-4 sm:items-end">
                <div class="w-full sm:flex-1">
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Zoeken op titel</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}"
                           placeholder="Zoeken..."
                           class="w-full rounded-full px-4 py-3 border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <button id="clear-btn" type="button" class="btn-secondary w-full sm:w-auto">
                    Wissen
                </button>
            </div>

            <!-- Surveys List -->
            <div id="surveys-container">
                <div class="space-y-4">
                    @forelse ($surveys as $survey)
                        <div class="border border-white rounded-lg p-4 sm:p-6 bg-white">
                            <div class="flex flex-col sm:flex-row justify-between items-start gap-4">
                                <div class="flex-1 min-w-0">
                                    <h2 class="text-lg sm:text-xl font-semibold text-gray-900 break-words">{{ $survey->title }}</h2>
                                    <x-truncated-text
                                        :text="$survey->description"
                                        :maxLength="150"
                                        class="text-sm sm:text-base text-gray-600 mt-1"
                                    />
                                    <div class="mt-2 flex flex-wrap gap-2 items-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-green-100 text-xs font-medium text-green-800">
                                            Actief
                                        </span>
                                        <span class="text-xs sm:text-sm text-gray-500">
                                            Einddatum: {{ $survey->ends_at?->format('d-m-Y') ?? 'Geen einddatum' }}
                                        </span>
                                        <span class="text-xs sm:text-sm text-gray-500">
                                            {{ $survey->questions_count }} vragen
                                        </span>
                                    </div>
                                </div>
                                <div class="w-full sm:w-auto ml-0 sm:ml-4">
                                    <a href="{{ route('survey.show', $survey) }}" class="btn-primary w-full sm:w-auto block text-center">
                                        Enquete invullen
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12">
                            <p class="text-gray-500">Geen enquetes gevonden die overeenkomen met je criteria.</p>
                        </div>
                    @endforelse
                </div>

                <!-- Pagination -->
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
            const params = new URLSearchParams({search, page: page || 1});

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

        clearBtn.addEventListener('click', () => {
            searchInput.value = '';
            fetchSurveys(1);
        });

        // Initialize on page load
        initializeTruncatedText();
        attachPaginationListeners();
    </script>
</x-layout>
