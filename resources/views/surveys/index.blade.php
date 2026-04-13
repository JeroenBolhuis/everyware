<x-layout>
    <div class="max-w-7xl mx-auto py-10 px-4">
        <div class="bg-white border rounded-2xl shadow-md p-8">
            <h1 class="text-3xl font-bold mb-6">Enquete overzicht</h1>

            <!-- Filters -->
            <div class="mb-6 flex flex-wrap gap-4">
                <div class="flex flex-wrap gap-4 items-end">
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Zoeken op titel</label>
                        <input type="text" name="search" id="search" value="{{ request('search') }}"
                               placeholder="Zoeken..."
                               class="rounded-full px-4 py-3 border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" id="status"
                                class="rounded-full px-4 py-3 border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Alles</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actief
                            </option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactief
                            </option>
                        </select>
                    </div>

                    <button id="clear-btn" type="button" class="btn-secondary self-end">
                        Wissen
                    </button>
                </div>
            </div>

            <!-- Surveys List -->
            <div id="surveys-container">
                <div class="space-y-4">
                    @forelse ($surveys as $survey)
                        <div class="border border-white rounded-lg p-6 bg-white">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <h2 class="text-xl font-semibold text-gray-900">{{ $survey->title }}</h2>
                                    <p class="text-gray-600 mt-1">{{ $survey->description }}</p>
                                    <div class="mt-2">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $survey->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $survey->is_active ? 'Actief' : 'Inactief' }}
                                    </span>
                                        <span class="ml-2 text-sm text-gray-500">
                                        {{ $survey->questions->count() }} vragen
                                    </span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    @if ($survey->is_active)
                                        <a href="{{ route('survey.show', $survey) }}" class="btn-primary">
                                            Enquete invullen
                                        </a>
                                    @else
                                        <span class="btn-disabled">
                                            Inactief
                                        </span>
                                    @endif
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
        const statusSelect = document.getElementById('status');
        const clearBtn = document.getElementById('clear-btn');
        const container = document.getElementById('surveys-container');
        let debounceTimer;

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

        attachPaginationListeners();
    </script>
</x-layout>
