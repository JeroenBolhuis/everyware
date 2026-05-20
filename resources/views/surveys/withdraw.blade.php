<x-layout>
    <div class="mx-auto max-w-xl px-4 py-10">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm sm:p-8">
            <h1 class="text-2xl font-bold text-zinc-950">Toegang intrekken</h1>

            @if($response->withdrawn_at)
                <p class="mt-4 text-zinc-700">Deze reactie is al ingetrokken.</p>
            @else
                <p class="mt-4 text-zinc-700">
                    Weet je zeker dat je jouw toestemming of enquête-inzending wilt intrekken?
                </p>

                <form method="POST" action="{{ route('survey.withdraw.destroy', $response->withdrawal_token) }}" class="mt-6">
                    @csrf
                    <button class="btn-primary">
                        Intrekken
                    </button>
                </form>
            @endif
        </div>
    </div>
</x-layout>
