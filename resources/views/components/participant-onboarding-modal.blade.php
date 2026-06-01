@php
    $steps = [
        [
            'label' => 'Stap 1 van 4',
            'title' => 'Welkom bij LIC Feedback',
            'body' => 'Via dit systeem vul je enquêtes in voor het LIC. Je reacties helpen om onderwijs, begeleiding en activiteiten beter aan te laten sluiten op studenten.',
        ],
        [
            'label' => 'Stap 2 van 4',
            'title' => 'Zo verdien je punten',
            'body' => 'Bij enquêtes met beloningspunten krijg je punten nadat je de enquête hebt afgerond en kiest om je punten te ontvangen. Anoniem invullen kan ook, maar levert geen punten op.',
        ],
        [
            'label' => 'Stap 3 van 4',
            'title' => 'Je punten bekijken',
            'body' => 'Je totaal staat altijd op de pagina Mijn punten. Daar zie je ook welke enquêtes je hebt ingevuld en hoeveel punten daarbij horen.',
        ],
        [
            'label' => 'Stap 4 van 4',
            'title' => 'Punten verzilveren',
            'body' => 'Rewards regel je via LIC-medewerkers. Neem contact met hen op voor het actuele aanbod; zij kunnen punten van je saldo afhalen wanneer je een beloning kiest.',
        ],
    ];
@endphp

<div
    id="participant-onboarding"
    class="fixed inset-0 z-50 flex items-end justify-center bg-zinc-950/70 px-4 py-4 backdrop-blur-sm sm:items-center sm:py-8"
    role="dialog"
    aria-modal="true"
    aria-labelledby="participant-onboarding-title"
    data-step="0"
>
    <div class="w-full max-w-xl overflow-hidden rounded-2xl border border-red-100 bg-white shadow-2xl">
        <div class="border-b border-zinc-100 bg-red-50 px-5 py-4 sm:px-7">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-red-700" data-onboarding-label>
                        {{ $steps[0]['label'] }}
                    </p>
                    <h2 id="participant-onboarding-title" class="mt-1 text-2xl font-bold text-zinc-950">
                        {{ $steps[0]['title'] }}
                    </h2>
                </div>

                <div class="grid size-12 shrink-0 place-items-center rounded-full bg-red-700 text-lg font-bold text-white">
                    LIC
                </div>
            </div>

            <div class="mt-4 grid grid-cols-4 gap-2" aria-hidden="true">
                @foreach ($steps as $index => $step)
                    <div
                        class="h-1.5 rounded-full {{ $index === 0 ? 'bg-red-700' : 'bg-red-200' }}"
                        data-onboarding-progress="{{ $index }}"
                    ></div>
                @endforeach
            </div>
        </div>

        <div class="px-5 py-6 sm:px-7">
            @foreach ($steps as $index => $step)
                <section class="{{ $index === 0 ? '' : 'hidden' }}" data-onboarding-step="{{ $index }}">
                    <p class="text-base leading-7 text-zinc-700">
                        {{ $step['body'] }}
                    </p>
                </section>
            @endforeach

            <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                <p class="text-sm font-semibold text-amber-950">Goed om te weten</p>
                <p class="mt-1 text-sm leading-6 text-amber-900">
                    Deze uitleg moet je eenmalig afronden voordat je de site kunt gebruiken.
                </p>
            </div>
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-zinc-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-7">
            <button type="button" class="btn-secondary sm:min-w-32" data-onboarding-back disabled>
                Vorige
            </button>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <button type="button" class="btn-primary sm:min-w-32" data-onboarding-next>
                    Volgende
                </button>

                <form method="POST" action="{{ route('student.onboarding.complete') }}" class="hidden" data-onboarding-complete-form>
                    @csrf
                    <button type="submit" class="btn-primary w-full sm:min-w-32">
                        Doorgaan
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    (() => {
        const modal = document.getElementById('participant-onboarding');

        if (!modal) {
            return;
        }

        const steps = @json($steps);
        const title = document.getElementById('participant-onboarding-title');
        const label = modal.querySelector('[data-onboarding-label]');
        const backButton = modal.querySelector('[data-onboarding-back]');
        const nextButton = modal.querySelector('[data-onboarding-next]');
        const completeForm = modal.querySelector('[data-onboarding-complete-form]');
        let currentStep = 0;

        document.body.classList.add('overflow-hidden');

        document.body.querySelectorAll(':scope > *').forEach((element) => {
            if (element !== modal && !element.contains(modal)) {
                element.inert = true;
                element.setAttribute('aria-hidden', 'true');
            }
        });

        const render = () => {
            modal.dataset.step = currentStep;
            title.textContent = steps[currentStep].title;
            label.textContent = steps[currentStep].label;

            modal.querySelectorAll('[data-onboarding-step]').forEach((step) => {
                step.classList.toggle('hidden', Number(step.dataset.onboardingStep) !== currentStep);
            });

            modal.querySelectorAll('[data-onboarding-progress]').forEach((progress) => {
                progress.classList.toggle('bg-red-700', Number(progress.dataset.onboardingProgress) <= currentStep);
                progress.classList.toggle('bg-red-200', Number(progress.dataset.onboardingProgress) > currentStep);
            });

            backButton.disabled = currentStep === 0;
            nextButton.classList.toggle('hidden', currentStep === steps.length - 1);
            completeForm.classList.toggle('hidden', currentStep !== steps.length - 1);
        };

        backButton.addEventListener('click', () => {
            currentStep = Math.max(0, currentStep - 1);
            render();
        });

        nextButton.addEventListener('click', () => {
            currentStep = Math.min(steps.length - 1, currentStep + 1);
            render();
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                event.preventDefault();
            }
        });

        modal.addEventListener('keydown', (event) => {
            if (event.key !== 'Tab') {
                return;
            }

            const focusableElements = Array.from(modal.querySelectorAll('button:not([disabled]), [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'))
                .filter((element) => element.offsetParent !== null);
            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];

            if (event.shiftKey && document.activeElement === firstElement) {
                event.preventDefault();
                lastElement.focus();
            }

            if (! event.shiftKey && document.activeElement === lastElement) {
                event.preventDefault();
                firstElement.focus();
            }
        });

        render();
        nextButton.focus();
    })();
</script>
