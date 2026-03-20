<x-layout>
    <div class="max-w-2xl mx-auto py-10 px-4">
        <div class="bg-white border rounded-2xl shadow-md p-8">
            <h1 class="text-3xl font-bold mb-4">Bedankt voor je antwoord</h1>
            <p class="text-gray-700 mb-6">
                Je enquête is succesvol verzonden.
            </p>

            <div class="bg-gray-100 rounded-lg p-4">
                <p class="font-semibold mb-2">Rechten intrekken</p>
                <p class="mb-2">Via deze link kun je jouw toestemming of antwoorden laten intrekken:</p>
                <a class="text-blue-600 underline break-all" href="{{ route('survey.withdraw.show', $response->withdrawal_token) }}">
                    {{ route('survey.withdraw.show', $response->withdrawal_token) }}
                </a>
            </div>
        </div>
    </div>
</x-layout>
