<x-layout>
    @php
        $latestMailDeliveryRequest = $response->latestMailDeliveryRequest();
        $confirmationMailStatus = $latestMailDeliveryRequest?->mail_status ?? session('confirmationMailStatus');
    @endphp

    <div class="max-w-2xl mx-auto py-10 px-4 space-y-6">
        <div class="bg-white border rounded-2xl shadow-md p-8">
            <h1 class="text-3xl font-bold mb-4">Bedankt voor je antwoord</h1>
            <p class="text-gray-700 mb-6">
                Je enquete is succesvol verzonden.
            </p>

            @if ($confirmationMailStatus === 'sent' && $response->maskedStudentEmail())
                <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-green-800">
                    Er is een bevestigingsmail verstuurd naar {{ $response->maskedStudentEmail() }}.
                </div>
            @endif

            @if ($confirmationMailStatus === 'failed')
                <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-amber-900">
                    Je enquete is opgeslagen, maar de bevestigingsmail kon niet direct worden verstuurd.
                </div>
            @endif

            @if (session('contactDetailsSaved'))
                <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-green-800">
                    Je contactgegevens zijn opgeslagen.
                </div>
            @endif

            <div class="mb-6 rounded-lg border border-gray-200 bg-gray-50 p-4">
                <p class="font-semibold text-gray-900">Contactgegevens</p>

                @if ($response->hasSharedContactDetails())
                    <p class="mt-2 text-gray-700">Je hebt contactgegevens gedeeld.</p>

                    <ul class="mt-3 space-y-2 text-sm text-gray-700">
                        @foreach ($response->sharedContactFieldLabels() as $fieldLabel)
                            <li class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-green-800 mr-2 mb-2">
                                {{ $fieldLabel }}
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-2 text-gray-700">
                        Je hebt geen contactgegevens meegestuurd tijdens het verzenden van de enquete.
                    </p>
                @endif
            </div>

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
