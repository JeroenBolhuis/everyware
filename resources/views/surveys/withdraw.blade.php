<x-layout>
    <div class="max-w-xl mx-auto py-10 px-4">
        <div class="bg-white border rounded-2xl shadow-md p-8">
            <h1 class="text-2xl font-bold mb-4">Toegang intrekken</h1>

            @if($response->withdrawn_at)
                <p class="text-gray-700">Deze reactie is al ingetrokken.</p>
            @else
                <p class="text-gray-700 mb-6">
                    Weet je zeker dat je jouw toestemming of enquête-inzending wilt intrekken?
                </p>

                <form method="POST" action="{{ route('surveys.withdraw.destroy', $response->withdrawal_token) }}">
                    @csrf
                    <button class="px-6 py-3 rounded-xl bg-red-600 text-white font-semibold">
                        Intrekken
                    </button>
                </form>
            @endif
        </div>
    </div>
</x-layout>