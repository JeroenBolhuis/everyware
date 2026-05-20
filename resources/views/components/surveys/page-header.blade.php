<header class="sticky top-0 z-30 border-b border-zinc-200 bg-white/95 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-5xl items-center justify-between gap-4 px-4 sm:px-6">
        <a href="{{ route('surveys.index') }}" class="shrink-0 flex items-center h-10" aria-label="Avans Hogeschool">
            <img src="/images/Avans_Hogeschool_Logo.svg" alt="Avans Hogeschool Logo" class="h-7 w-auto" />
        </a>
   

        <div class="min-w-0 flex-1 text-center">
            <div class="truncate text-sm font-semibold text-zinc-900 sm:text-base">LIC Feedback</div>
        </div>

        <div class="flex shrink-0 items-center justify-end">
        @auth('participant')
            <details class="group relative">
                <summary
                    class="flex h-10 items-center gap-2 rounded-full border border-zinc-200 bg-white px-2 text-sm font-semibold text-zinc-800 shadow-sm transition hover:border-red-200 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-200 [&::-webkit-details-marker]:hidden"
                    aria-label="Studentmenu"
                >
                    <span class="grid size-7 place-items-center rounded-full bg-red-50 text-xs font-bold text-red-700">
                        {{ str(auth('participant')->user()->email)->substr(0, 1)->upper() }}
                    </span>
                    <span class="hidden max-w-36 truncate sm:block">{{ auth('participant')->user()->email }}</span>
                </summary>

                <div class="absolute right-0 z-20 mt-2 w-72 overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-lg">
                    <div class="border-b border-zinc-100 px-4 py-3">
                        <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">Ingelogd als student</p>
                        <p class="mt-1 truncate text-sm text-zinc-700">{{ auth('participant')->user()->email }}</p>
                    </div>

                    <a href="{{ route('student.points') }}" class="block px-4 py-3 text-sm font-medium text-zinc-800 hover:bg-zinc-50">
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
            <span class="rounded-full border border-zinc-200 px-3 py-1.5 text-sm font-medium text-zinc-500">Student</span>
        @endauth
        </div>
    </div>
</header>
