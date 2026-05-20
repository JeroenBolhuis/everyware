<x-layout>
    <div class="mx-auto max-w-2xl px-4 py-8 sm:py-10">
        <div class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm sm:p-7">
            <p class="text-sm font-semibold uppercase tracking-wide text-red-700">Al ingevuld</p>
            <h1 class="mt-2 text-2xl font-bold text-zinc-950 sm:text-3xl">Je hebt deze enquête al ingevuld</h1>

            <p class="mt-3 text-sm text-zinc-600 sm:text-base">
                Voor {{ $survey->title }} kan elke student maar één inzending doen.
            </p>

            @if ($response->awardedPoints() > 0)
                <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    Je hebt hiervoor {{ $response->awardedPoints() }} {{ $response->awardedPoints() === 1 ? 'punt' : 'punten' }} ontvangen.
                </div>
            @endif

            <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('student.points') }}" class="btn-primary text-center">
                    Mijn punten bekijken
                </a>
                <a href="{{ route('surveys.index') }}" class="btn-secondary text-center">
                    Naar alle enquêtes
                </a>
            </div>
        </div>
    </div>
</x-layout>
