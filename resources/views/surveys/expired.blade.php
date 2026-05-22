<x-layout>
    <div class="min-h-screen flex flex-col overflow-x-hidden">
        <x-surveys.page-header/>

        <main class="flex-1 max-w-3xl mx-auto w-full px-4 pb-10 pt-0">
            <section
                class="rounded-[1.25rem] border border-amber-200 bg-amber-50 p-6 text-amber-950 shadow-[0_10px_30px_rgba(180,83,9,0.08)]"
                aria-label="Enquête verlopen"
            >
                <div class="text-xs font-bold uppercase tracking-[0.08em] text-amber-700">
                    Enquête verlopen
                </div>

                <h1 class="mt-2 text-2xl font-bold leading-tight text-gray-900">
                    Deze enquête kan niet meer worden ingevuld.
                </h1>

                <p class="mt-3 text-sm leading-6 text-amber-900">
                    De einddatum van {{ $survey->title }} is verstreken. Feedback inzenden is daarom automatisch gestopt.
                </p>
            </section>
        </main>
    </div>
</x-layout>
