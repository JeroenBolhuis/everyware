<x-layout>
    <div class="flex min-h-screen flex-col overflow-x-hidden">
        <main class="mx-auto w-full max-w-3xl flex-1 px-4 pb-10 pt-6">
            <section
                class="rounded-[1.25rem] border border-red-200 bg-red-50 p-6 text-red-950 shadow-[0_10px_30px_rgba(185,28,28,0.08)]"
                aria-label="Niet geschikt voor deze enquete"
            >
                <div class="text-xs font-bold uppercase tracking-[0.08em] text-red-700">
                    Niet geschikt
                </div>

                <h1 class="mt-2 text-2xl font-bold leading-tight text-gray-900">
                    Je bent niet geschikt om deze enquete in te vullen.
                </h1>

                <p class="mt-3 text-sm leading-6 text-red-900">
                    Deze enquete is gericht op een specifieke school. Je huidige e-mailadres hoort niet bij de doelgroep voor {{ $survey->title }}.
                </p>

                <a href="{{ route('surveys.index') }}" class="btn-primary mt-5 inline-flex">
                    Naar alle enquetes
                </a>
            </section>
        </main>
    </div>
</x-layout>
