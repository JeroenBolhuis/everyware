<x-layout>
    <div class="max-w-2xl mx-auto py-6 sm:py-10 px-3 sm:px-4 md:px-6 space-y-6">
        <div class="bg-white border rounded-lg sm:rounded-2xl shadow-md p-4 sm:p-6 md:p-8">
            <h1 class="text-2xl sm:text-3xl font-bold mb-4">Bedankt voor je antwoord</h1>
            <p class="text-sm sm:text-base text-gray-700 mb-6">
                Je enquête is succesvol verzonden.
            </p>

            @if (session('confirmationMailStatus') === 'sent')
                <div class="mb-4 sm:mb-6 rounded-lg border border-green-200 bg-green-50 p-3 sm:p-4 text-sm sm:text-base text-green-800">
                    Er is een bevestigingsmail verstuurd.
                </div>
            @endif

            @if (session('confirmationMailStatus') === 'failed')
                <div class="mb-4 sm:mb-6 rounded-lg border border-amber-200 bg-amber-50 p-3 sm:p-4 text-sm sm:text-base text-amber-900">
                    Je enquête is opgeslagen, maar de bevestigingsmail kon niet direct worden verstuurd.
                </div>
            @endif

            @if (session('contactDetailsSaved'))
                <div class="mb-4 sm:mb-6 rounded-lg border border-green-200 bg-green-50 p-3 sm:p-4 text-sm sm:text-base text-green-800">
                    Je contactgegevens zijn opgeslagen.
                </div>
            @endif

            @if ($response?->participant && $response->awardedPoints() > 0)
                <div class="mb-4 sm:mb-6 rounded-lg border border-amber-200 bg-amber-50 p-3 sm:p-4 text-sm sm:text-base text-amber-900">
                    <p class="font-semibold">Je hebt {{ $response->awardedPoints() }} punten gekregen.</p>
                    <p class="mt-1">Je totaal staat nu op {{ $response->totalPoints() }} punten.</p>
                </div>
            @endif

            <div class="mb-4 sm:mb-6 rounded-lg border border-gray-200 bg-gray-50 p-4 sm:p-4 md:p-6">
                <p class="font-semibold text-sm sm:text-base text-gray-900">Contactgegevens</p>

                @if ($response?->hasSharedContactDetails())
                    <p class="mt-2 text-sm sm:text-base text-gray-700">Je hebt contactgegevens gedeeld. Deze gegevens zijn versleuteld opgeslagen.</p>

                    <ul class="mt-3 flex flex-wrap gap-2 text-xs sm:text-sm text-gray-700">
                        @foreach ($response->sharedContactFieldLabels() as $fieldLabel)
                            <li class="inline-flex items-center rounded-full bg-green-100 px-2.5 sm:px-3 py-1 text-green-800">
                                {{ $fieldLabel }}
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-2 text-sm sm:text-base text-gray-700">
                        Wil je dat we contact met je opnemen? Vul hieronder je naam in.
                    </p>
                    <div class="mt-4 rounded-lg border border-blue-200 bg-blue-50 p-4 text-blue-900">
                        Je enquête is al anoniem opgeslagen. Alleen als je hieronder zelf je naam deelt,
                        kunnen we je feedback aan jou koppelen.
                    </div>
                    @if ($response)
                    <form method="POST" action="{{ route('survey.contact-details.store', $response) }}"
                          class="mt-4 space-y-4">
                        @csrf
                        <x-surveys.contact-details/>

                        <div class="flex flex-col-reverse sm:flex-row items-center justify-end gap-2 sm:gap-3">
                            <button type="submit" class="btn-secondary w-full sm:w-auto">
                                Contactgegevens opslaan
                            </button>
                        </div>
                    </form>
                    @endif
                @endif
            </div>

            @if ($response)
            <div class="bg-gray-100 rounded-lg p-4 sm:p-4 md:p-6">
                <p class="font-semibold mb-2 text-sm sm:text-base">Rechten intrekken</p>
                <p class="mb-3 text-sm sm:text-base">Via deze link kun je jouw toestemming of antwoorden laten intrekken:</p>
                <a class="text-blue-600 underline break-all text-xs sm:text-sm"
                   href="{{ route('survey.withdraw.show', $response->withdrawal_token) }}">
                    {{ route('survey.withdraw.show', $response->withdrawal_token) }}
                </a>
            </div>
            @endif
        </div>
    </div>
</x-layout>
