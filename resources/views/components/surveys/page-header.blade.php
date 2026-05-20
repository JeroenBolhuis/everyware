<header class="flex items-center justify-between gap-3 border-b border-gray-200 bg-white px-4 py-3 sm:px-6">
    <a href="{{ route('surveys.index') }}" class="shrink-0 text-xl font-bold tracking-tight text-red-600">
        avans
    </a>

    <div class="min-w-0 flex-1 text-center">
        <div class="truncate text-sm font-semibold text-gray-900 sm:text-base">LIC Feedback Demo</div>
    </div>

    <div class="flex shrink-0 items-center justify-end">
        @auth('participant')
            <details class="group relative">
                <summary
                    class="flex h-10 w-10 cursor-pointer list-none items-center justify-center rounded-full border border-gray-200 bg-gray-50 text-sm font-semibold text-red-700 transition hover:border-red-200 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-200 [&::-webkit-details-marker]:hidden"
                    aria-label="Studentmenu"
                >
                    {{ str(auth('participant')->user()->email)->substr(0, 1)->upper() }}
                </summary>

                <div class="absolute right-0 z-20 mt-2 w-64 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg">
                    <div class="border-b border-gray-100 px-4 py-3">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Ingelogd</p>
                        <p class="mt-1 truncate text-sm text-gray-700">{{ auth('participant')->user()->email }}</p>
                    </div>

                    <a href="{{ route('student.points') }}" class="block px-4 py-3 text-sm font-medium text-gray-800 hover:bg-gray-50">
                        Mijn punten
                    </a>

                    <form method="POST" action="{{ route('survey.participant.logout') }}">
                        @csrf
                        <button type="submit" class="block w-full px-4 py-3 text-left text-sm font-medium text-red-700 hover:bg-red-50">
                            Uitloggen
                        </button>
                    </form>
                </div>
            </details>
        @else
            <span class="text-sm text-gray-400">Demo</span>
        @endauth
    </div>
</header>
