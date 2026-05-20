<header class="flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 gap-4">
    <div class="text-red-600 font-bold text-xl shrink-0">avans</div>
    <div class="font-semibold text-red-600 text-center text-sm sm:text-base flex-1 min-w-0">LIC Feedback Demo</div>
    <div class="text-sm text-gray-500 shrink-0 flex items-center gap-3">
        @auth('participant')
            <a href="{{ route('student.points') }}" class="hidden sm:inline text-red-700 hover:text-red-900 font-medium whitespace-nowrap">
                Mijn punten
            </a>
            <span class="hidden sm:inline max-w-[12rem] truncate text-gray-600" title="{{ auth('participant')->user()->email }}">
                {{ auth('participant')->user()->email }}
            </span>
            <form method="POST" action="{{ route('survey.participant.logout') }}" class="inline">
                @csrf
                <button type="submit" class="text-red-700 hover:text-red-900 font-medium whitespace-nowrap">
                    Uitloggen
                </button>
            </form>
        @else
            <span class="text-gray-400">Verlaat demo</span>
        @endauth
    </div>
</header>
