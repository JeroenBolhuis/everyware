@php
    $steps = [
        [
            'label' => 'Stap 1 van 5',
            'title' => 'Welkom bij LIC Feedback',
            'body' => 'Via dit systeem vul je enquetes in voor het LIC. Je reacties helpen om onderwijs, begeleiding en activiteiten beter aan te laten sluiten op studenten.',
        ],
        [
            'label' => 'Stap 2 van 5',
            'title' => 'Zo verdien je punten',
            'body' => 'Bij enquetes met beloningspunten krijg je punten nadat je de enquete hebt afgerond en kiest om je punten te ontvangen. Anoniem invullen kan ook, maar levert geen punten op.',
        ],
        [
            'label' => 'Stap 3 van 5',
            'title' => 'Je punten bekijken',
            'body' => 'Je totaal staat altijd op de pagina Mijn punten. Daar zie je ook welke enquetes je hebt ingevuld en hoeveel punten daarbij horen.',
        ],
        [
            'label' => 'Stap 4 van 5',
            'title' => 'Punten verzilveren',
            'body' => 'Rewards regel je via LIC-medewerkers. Neem contact met hen op voor het actuele aanbod; zij kunnen punten van je saldo afhalen wanneer je een beloning kiest.',
        ],
        [
            'label' => 'Stap 5 van 5',
            'title' => 'Kies je afdeling',
            'body' => 'Kies je studentenafdeling. We gebruiken dit om te bepalen welke afdelingsspecifieke enquetes je mag invullen.',
        ],
    ];
    $initialStep = $errors->has('academy') ? count($steps) - 1 : 0;
@endphp

<div
    id="participant-onboarding"
    class="fixed inset-0 z-50 flex items-end justify-center bg-zinc-950/70 px-4 py-4 backdrop-blur-sm sm:items-center sm:py-8"
    role="dialog"
    aria-modal="true"
    aria-labelledby="participant-onboarding-title"
    data-step="{{ $initialStep }}"
>
    <div class="w-full max-w-xl overflow-hidden rounded-2xl border border-red-100 bg-white shadow-2xl">
        <div class="border-b border-zinc-100 bg-red-50 px-5 py-4 sm:px-7">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-red-700" data-onboarding-label>
                        {{ $steps[$initialStep]['label'] }}
                    </p>
                    <h2 id="participant-onboarding-title" class="mt-1 text-2xl font-bold text-zinc-950">
                        {{ $steps[$initialStep]['title'] }}
                    </h2>
                </div>

                <div class="grid size-12 shrink-0 place-items-center rounded-full bg-red-700 text-lg font-bold text-white">
                    LIC
                </div>
            </div>

            <div class="mt-4 grid grid-cols-5 gap-2" aria-hidden="true">
                @foreach ($steps as $index => $step)
                    <div
                        class="h-1.5 rounded-full {{ $index <= $initialStep ? 'bg-red-700' : 'bg-red-200' }}"
                        data-onboarding-progress="{{ $index }}"
                    ></div>
                @endforeach
            </div>
        </div>

        <form method="POST" action="{{ route('student.onboarding.complete') }}" data-onboarding-complete-form>
            @csrf

            <div class="px-5 py-6 sm:px-7">
                @foreach ($steps as $index => $step)
                    <section class="{{ $index === $initialStep ? '' : 'hidden' }}" data-onboarding-step="{{ $index }}">
                        <p class="text-base leading-7 text-zinc-700">
                            {{ $step['body'] }}
                        </p>

                        @if ($index === count($steps) - 1)
                            <div class="mt-5">
                                <label for="onboarding-academy" class="mb-1 block text-sm font-semibold text-zinc-800">
                                    Studentenafdeling
                                </label>
                                <select
                                    id="onboarding-academy"
                                    name="academy"
                                    class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 shadow-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-200"
                                    required
                                >
                                    <option value="">Kies je afdeling</option>
                                    @foreach (\App\Enums\Academy::options() as $academy => $label)
                                        <option value="{{ $academy }}" @selected(old('academy') === $academy)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('academy')
                                    <p class="mt-2 text-sm font-medium text-red-700">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif
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

                    <button type="submit" class="btn-primary w-full sm:min-w-32">
                        Doorgaan
                    </button>
                </div>
            </div>
        </form>
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
        const submitButton = modal.querySelector('button[type="submit"]');
        let currentStep = @json($initialStep);

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
            submitButton.classList.toggle('hidden', currentStep !== steps.length - 1);
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
