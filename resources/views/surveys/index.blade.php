<x-layout>
    <div class="max-w-7xl mx-auto py-10 px-4">
        <div class="bg-white border rounded-2xl shadow-md p-8">
            <h1 class="text-3xl font-bold mb-6">Surveys Dashboard</h1>

            <!-- Filters -->
            <div class="mb-6 flex flex-wrap gap-4">
                <form method="GET" class="flex flex-wrap gap-4 items-end">
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search by Title</label>
                        <input type="text" name="search" id="search" value="{{ request('search') }}" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" onkeyup="this.form.submit()">
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" id="status" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" onchange="this.form.submit()">
                            <option value="">All</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>

                    <a href="{{ route('surveys.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">Clear</a>
                </form>
            </div>

            <!-- Surveys List -->
            <div class="space-y-4">
                @forelse ($surveys as $survey)
                    <div class="border rounded-lg p-6 {{ $survey->is_active ? 'bg-green-50 border-green-200' : 'bg-gray-50 border-gray-200' }}">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <h2 class="text-xl font-semibold text-gray-900">{{ $survey->title }}</h2>
                                <p class="text-gray-600 mt-1">{{ $survey->description }}</p>
                                <div class="mt-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $survey->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $survey->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                    <span class="ml-2 text-sm text-gray-500">
                                        {{ $survey->questions->count() }} questions
                                    </span>
                                </div>
                            </div>
                            <div class="ml-4">
                                @if ($survey->is_active)
                                    <a href="{{ route('survey.show', $survey) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                                        Take Survey
                                    </a>
                                @else
                                    <span class="inline-flex items-center px-4 py-2 bg-gray-400 text-white text-sm font-medium rounded-md cursor-not-allowed">
                                        Inactive
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12">
                        <p class="text-gray-500">No surveys found matching your criteria.</p>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            @if ($surveys->hasPages())
                <div class="mt-6">
                    {{ $surveys->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layout>
