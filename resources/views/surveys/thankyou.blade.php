<x-layout>
    <div class="mx-auto max-w-2xl space-y-5 px-4 py-8 sm:py-10">
        <div class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm sm:p-7">
            <h1 class="text-2xl font-bold text-zinc-950 sm:text-3xl">Bedankt voor je antwoord</h1>
            <p class="mt-2 text-sm text-zinc-600 sm:text-base">
                Je enquête is succesvol verzonden.
            </p>

            @if (session('confirmationMailStatus') === 'sent')
                <div class="mt-5 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800 sm:p-4">
                    Er is een bevestigingsmail verstuurd.
                </div>
            @endif

            @if (session('confirmationMailStatus') === 'failed')
                <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 sm:p-4">
                    Je enquête is opgeslagen, maar de bevestigingsmail kon niet direct worden verstuurd.
                </div>
            @endif

            @if (session('contactAllowed'))
                <div class="mt-5 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800 sm:p-4">
                    LIC-medewerkers mogen je via je e-mailadres benaderen.
                </div>
            @endif

            @if ($response?->participant && $response->awardedPoints() > 0)
                <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 sm:p-4">
                    <p class="font-semibold">Je hebt {{ $response->awardedPoints() }} punten gekregen.</p>
                    <p class="mt-1">Je totaal staat nu op {{ $response->totalPoints() }} punten.</p>
                </div>
            @endif

            <div class="mt-6 rounded-lg border border-zinc-200 bg-zinc-50 p-4 sm:p-5">
                <p class="font-semibold text-zinc-950">Punten en contact</p>

                @if ($response?->hasSharedContactDetails())
                    <p class="mt-2 text-sm text-zinc-700 sm:text-base">Je inzending is niet anoniem. LIC-medewerkers kunnen je e-mailadres zien.</p>

                    <ul class="mt-3 flex flex-wrap gap-2 text-xs text-zinc-700 sm:text-sm">
                        @foreach ($response->sharedContactFieldLabels() as $fieldLabel)
                            <li class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-green-800">
                                {{ $fieldLabel }}
                            </li>
                        @endforeach
                    </ul>
                @else
                    @php
                        $rewardPoints = (int) ($response?->survey?->reward_points ?? 0);
                    @endphp

                    <div class="mt-4 rounded-lg border border-red-100 bg-white p-4">
                        <p class="text-lg font-bold text-zinc-950">
                            Ontvang {{ $rewardPoints }} {{ $rewardPoints === 1 ? 'punt' : 'punten' }} voor deze enquête.
                        </p>
                        <p class="mt-1 text-sm text-zinc-600">
                            Hiervoor wordt alleen je e-mailadres zichtbaar bij deze inzending.
                        </p>
                    </div>

                    @if ($response)
                    <form method="POST" action="{{ route('survey.contact-details.store', $response) }}"
                          class="mt-4">
                        @csrf

                        <div class="flex flex-col-reverse sm:flex-row items-center justify-end gap-2 sm:gap-3">
                            <button type="submit" class="btn-primary w-full sm:w-auto">
                                Ontvang mijn punten
                            </button>
                        </div>
                    </form>
                    @endif
                @endif
            </div>

            @if ($response)
            <div class="mt-6 rounded-lg bg-zinc-100 p-4 sm:p-5">
                <p class="font-semibold text-sm sm:text-base">Rechten intrekken</p>
                <p class="mt-1 text-sm sm:text-base">Via deze link kun je jouw toestemming of antwoorden laten intrekken:</p>
                <a class="mt-3 block break-all text-xs text-red-700 underline sm:text-sm"
                   href="{{ route('survey.withdraw.show', $response->withdrawal_token) }}">
                    {{ route('survey.withdraw.show', $response->withdrawal_token) }}
                </a>
            </div>
            @endif
        </div>
    </div>
</x-layout>
