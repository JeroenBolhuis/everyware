<x-layout>
    <div>
        <main class="mx-auto w-full max-w-4xl px-4 py-8 sm:px-6">
            <div class="space-y-6">
                <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-wide text-red-700">Studentpunten</p>
                            <h1 class="mt-1 text-2xl font-bold text-gray-950 sm:text-3xl">Jouw overzicht</h1>
                        </div>

                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-amber-950">
                            <p class="text-sm font-medium">Totaal</p>
                            <p class="mt-1 text-3xl font-bold">{{ $participant->current_points }} punten</p>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-gray-950">Ingevulde enquetes</h2>
                            <p class="text-sm text-gray-600">Alleen niet-anonieme inzendingen leveren punten op.</p>
                        </div>
                    </div>

                    <div class="mt-5 overflow-hidden rounded-xl border border-gray-200">
                        <div class="hidden grid-cols-[1fr_10rem_8rem] gap-4 bg-gray-100 px-4 py-3 text-sm font-semibold text-gray-700 sm:grid">
                            <div>Enquete</div>
                            <div>Ingestuurd</div>
                            <div class="text-right">Punten</div>
                        </div>

                        <div class="divide-y divide-gray-200">
                            @forelse ($responses as $response)
                                <div class="grid gap-2 px-4 py-4 sm:grid-cols-[1fr_10rem_8rem] sm:items-center sm:gap-4">
                                    <div class="min-w-0">
                                        <p class="font-medium text-gray-950">{{ $response->survey?->title ?? 'Enquete verwijderd' }}</p>
                                        @if ($response->withdrawn_at)
                                            <p class="mt-1 text-sm text-gray-500">Ingetrokken</p>
                                        @elseif (! $response->hasSharedContactDetails())
                                            <p class="mt-1 text-sm text-gray-500">Anoniem ingevuld</p>
                                        @endif
                                    </div>

                                    <div class="text-sm text-gray-600">
                                        {{ $response->submitted_at?->format('d-m-Y H:i') ?? '-' }}
                                    </div>

                                    <div class="text-left sm:text-right">
                                        <span class="inline-flex rounded-full px-3 py-1 text-sm font-semibold {{ $response->awardedPoints() > 0 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                                            {{ $response->awardedPoints() }}
                                        </span>
                                    </div>
                                </div>
                            @empty
                                <div class="px-4 py-8 text-center text-sm text-gray-600">
                                    Je hebt nog geen enquetes ingevuld.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>
</x-layout>
