<?php

use App\Mail\AdminParticipantMessageMail;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Services\ParticipantService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Deelnemers mailen')] class extends Component {
    /** @var array<int, int> */
    public array $selectedSurveyIds = [];

    /** @var array<int, string> */
    public array $manualEmails = [];

    public string $manualEmail = '';

    public string $subject = '';

    public string $message = '';

    public ?int $linkedSurveyId = null;

    public int $lastSentCount = 0;

    public function mount(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    public function addSurvey(int $surveyId): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        if (! Survey::query()->whereKey($surveyId)->exists()) {
            return;
        }

        $this->selectedSurveyIds = collect($this->selectedSurveyIds)
            ->push($surveyId)
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    public function removeSurvey(int $surveyId): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $this->selectedSurveyIds = collect($this->selectedSurveyIds)
            ->reject(fn (int $id): bool => $id === $surveyId)
            ->values()
            ->all();
    }

    public function clearList(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $this->selectedSurveyIds = [];
        $this->manualEmails = [];
        $this->manualEmail = '';
        $this->lastSentCount = 0;
    }

    public function addManualEmail(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $validated = $this->validate([
            'manualEmail' => ['required', 'email:rfc', 'max:255'],
        ]);

        $this->manualEmails = collect($this->manualEmails)
            ->push($this->normalizeEmail($validated['manualEmail']))
            ->unique()
            ->values()
            ->all();

        $this->manualEmail = '';
    }

    public function removeLastManualEmail(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        array_pop($this->manualEmails);
        $this->manualEmails = array_values($this->manualEmails);
    }

    public function send(): void
    {
        /** @var User|null $user */
        $user = auth()->user();
        abort_unless($user?->isAdmin(), 403);

        $validated = $this->validate([
            'selectedSurveyIds' => ['array'],
            'selectedSurveyIds.*' => ['integer', 'exists:surveys,id'],
            'manualEmails' => ['array'],
            'manualEmails.*' => ['email:rfc', 'max:255'],
            'linkedSurveyId' => ['nullable', 'integer', 'exists:surveys,id'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $emails = $this->recipientEmails($user, $validated['selectedSurveyIds'], $validated['manualEmails']);
        $linkedSurvey = $this->linkedSurvey($validated['linkedSurveyId'] ?? null);

        if ($emails === []) {
            $this->addError('selectedSurveyIds', __('Voeg minimaal een enquete of handmatig e-mailadres toe.'));

            return;
        }

        foreach ($emails as $email) {
            Mail::to($email)->send(new AdminParticipantMessageMail(
                $validated['subject'],
                $validated['message'],
                $linkedSurvey !== null ? route('survey.share.show', $linkedSurvey->share_token) : null,
                $linkedSurvey?->title,
            ));
        }

        $this->lastSentCount = count($emails);
        $this->reset('subject', 'message');
        $this->dispatch('participant-mails-sent');
    }

    public function getSurveysProperty(): Collection
    {
        return Survey::query()
            ->withCount(['responses' => fn ($query) => $query->visibleInResults()])
            ->withMax('responses', 'submitted_at')
            ->whereHas('responses', fn ($query) => $query->visibleInResults())
            ->orderByDesc('responses_max_submitted_at')
            ->orderByDesc('created_at')
            ->get();
    }

    public function getSelectedSurveysProperty(): Collection
    {
        if ($this->selectedSurveyIds === []) {
            return collect();
        }

        return Survey::query()
            ->whereKey($this->selectedSurveyIds)
            ->orderByDesc('created_at')
            ->get();
    }

    public function getLinkableSurveysProperty(): Collection
    {
        return Survey::query()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereNull('ends_at')
                    ->orWhereDate('ends_at', '>=', today());
            })
            ->orderByDesc('created_at')
            ->get();
    }

    public function getRecipientCountProperty(): int
    {
        /** @var User|null $user */
        $user = auth()->user();

        if (! $user?->isAdmin()) {
            return 0;
        }

        return count($this->recipientEmails($user, $this->selectedSurveyIds, $this->manualEmails));
    }

    public function getDuplicateCountProperty(): int
    {
        $participantIds = $this->participantIdsForSelectedSurveys();

        $respondentDuplicateCount = max(0, $participantIds->count() - $participantIds->unique()->count());
        $emailDuplicateCount = max(0, count($this->respondentAndManualEmails()) - $this->recipientCount);

        return $respondentDuplicateCount + $emailDuplicateCount;
    }

    private function recipientEmails(User $user, array $surveyIds, array $manualEmails): array
    {
        return collect(app(ParticipantService::class)->emailsForSurveyRespondents($user, $surveyIds))
            ->merge($manualEmails)
            ->map(fn (string $email): string => $this->normalizeEmail($email))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function respondentAndManualEmails(): array
    {
        /** @var User|null $user */
        $user = auth()->user();

        if (! $user?->isAdmin()) {
            return [];
        }

        return collect(app(ParticipantService::class)->emailsForSurveyRespondents($user, $this->selectedSurveyIds))
            ->merge($this->manualEmails)
            ->map(fn (string $email): string => $this->normalizeEmail($email))
            ->filter()
            ->values()
            ->all();
    }

    private function normalizeEmail(string $email): string
    {
        return Str::lower(trim($email));
    }

    private function linkedSurvey(mixed $surveyId): ?Survey
    {
        if ($surveyId === null || $surveyId === '') {
            return null;
        }

        return Survey::query()
            ->whereKey((int) $surveyId)
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereNull('ends_at')
                    ->orWhereDate('ends_at', '>=', today());
            })
            ->first();
    }

    private function participantIdsForSelectedSurveys(): Collection
    {
        if ($this->selectedSurveyIds === []) {
            return collect();
        }

        return SurveyResponse::query()
            ->visibleInResults()
            ->whereIn('survey_id', $this->selectedSurveyIds)
            ->whereNotNull('participant_id')
            ->pluck('participant_id');
    }
}; ?>

<section class="w-full" aria-labelledby="admin-participant-mail-page-title">
    @include('partials.admin-heading')

    <flux:heading class="sr-only" id="admin-participant-mail-page-title">{{ __('Deelnemers mailen') }}</flux:heading>

    <x-pages::admin.layout
        :heading="__('Deelnemers mailen')"
        :subheading="__('Stel een maillijst samen uit eerdere enquete-inzendingen zonder e-mailadressen in de interface te tonen.')"
    >
        <div class="my-6 rounded-lg border border-sky-200 bg-sky-50 p-4 text-sky-950 dark:border-sky-800 dark:bg-sky-950/30 dark:text-sky-100">
            <flux:heading size="lg">{{ __('Dubbele e-mailadressen worden niet toegevoegd') }}</flux:heading>
            <flux:text class="mt-2 text-sm">
                {{ __('Je start met een lege maillijst. Voeg van meest recent naar minder recent de deelnemers toe die op eerdere enquetes hebben gereageerd, of voeg handmatig een e-mailadres toe. Als hetzelfde adres meerdere keren voorkomt, ontvangt die persoon maar een mail.') }}
            </flux:text>
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(340px,420px)]">
            <div class="rounded-lg border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-700 dark:bg-zinc-900 sm:p-6">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <flux:heading size="lg">{{ __('Eerdere enquetes') }}</flux:heading>
                        <flux:text class="mt-1 text-sm text-zinc-500">{{ __('Meest recente reacties staan bovenaan.') }}</flux:text>
                    </div>
                    <flux:badge color="zinc" size="sm">{{ __(':count beschikbaar', ['count' => $this->surveys->count()]) }}</flux:badge>
                </div>

                <div class="mt-5 overflow-x-auto">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>{{ __('Enquete') }}</flux:table.column>
                            <flux:table.column>{{ __('Reacties') }}</flux:table.column>
                            <flux:table.column>{{ __('Laatste reactie') }}</flux:table.column>
                            <flux:table.column align="end">{{ __('Actie') }}</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @forelse ($this->surveys as $survey)
                                @php
                                    $isSelected = in_array($survey->id, $selectedSurveyIds, true);
                                @endphp

                                <flux:table.row :key="'mail-survey-'.$survey->id">
                                    <flux:table.cell variant="strong">{{ $survey->title }}</flux:table.cell>
                                    <flux:table.cell>
                                        <flux:badge color="zinc" size="sm">{{ $survey->responses_count }}</flux:badge>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        {{ $survey->responses_max_submitted_at ? \Illuminate\Support\Carbon::parse($survey->responses_max_submitted_at)->format('d-m-Y H:i') : __('Onbekend') }}
                                    </flux:table.cell>
                                    <flux:table.cell align="end">
                                        @if ($isSelected)
                                            <flux:button
                                                size="sm"
                                                variant="ghost"
                                                icon="minus"
                                                wire:click="removeSurvey({{ $survey->id }})"
                                            >
                                                {{ __('Verwijderen') }}
                                            </flux:button>
                                        @else
                                            <flux:button
                                                size="sm"
                                                variant="primary"
                                                icon="plus"
                                                wire:click="addSurvey({{ $survey->id }})"
                                            >
                                                {{ __('Toevoegen') }}
                                            </flux:button>
                                        @endif
                                    </flux:table.cell>
                                </flux:table.row>
                            @empty
                                <flux:table.row>
                                    <flux:table.cell colspan="4">
                                        <flux:text class="text-center text-sm text-zinc-500">
                                            {{ __('Er zijn nog geen enquetes met mailbare reacties.') }}
                                        </flux:text>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforelse
                        </flux:table.rows>
                    </flux:table>
                </div>

                <div class="mt-6 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:heading size="lg">{{ __('Handmatig e-mailadres') }}</flux:heading>
                    <flux:text class="mt-1 text-sm text-zinc-500">
                        {{ __('Het adres wordt toegevoegd aan de maillijst, maar niet als zichtbaar adres getoond.') }}
                    </flux:text>

                    <div class="mt-4 flex flex-col gap-3 sm:flex-row">
                        <div class="min-w-0 flex-1">
                            <flux:input
                                wire:model="manualEmail"
                                type="email"
                                placeholder="{{ __('naam@example.com') }}"
                                aria-label="{{ __('Handmatig e-mailadres') }}"
                            />
                            <flux:error name="manualEmail" />
                        </div>
                        <flux:button
                            type="button"
                            variant="primary"
                            icon="plus"
                            wire:click="addManualEmail"
                        >
                            {{ __('Toevoegen') }}
                        </flux:button>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-700 dark:bg-zinc-900 sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <flux:heading size="lg">{{ __('Huidige maillijst') }}</flux:heading>
                        <flux:text class="mt-1 text-sm text-zinc-500">
                            {{ __(':count unieke ontvangers', ['count' => $this->recipientCount]) }}
                        </flux:text>
                    </div>
                    <flux:badge color="{{ $this->recipientCount > 0 ? 'emerald' : 'zinc' }}" size="sm">
                        {{ $this->recipientCount }}
                    </flux:badge>
                </div>

                @if ($this->duplicateCount > 0)
                    <flux:text class="mt-3 text-sm text-zinc-500">
                        {{ __(':count dubbele ontvanger(s) zijn overgeslagen.', ['count' => $this->duplicateCount]) }}
                    </flux:text>
                @endif

                @if ($manualEmails !== [])
                    <flux:text class="mt-3 text-sm text-zinc-500">
                        {{ __(':count handmatig toegevoegde ontvanger(s)', ['count' => count($manualEmails)]) }}
                    </flux:text>
                @endif

                <div class="mt-4 space-y-2">
                    @forelse ($this->selectedSurveys as $survey)
                        <div wire:key="selected-mail-survey-{{ $survey->id }}" class="flex items-center justify-between gap-3 rounded-md border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700">
                            <span class="truncate">{{ $survey->title }}</span>
                            <flux:button
                                size="sm"
                                variant="ghost"
                                icon="x-mark"
                                wire:click="removeSurvey({{ $survey->id }})"
                                aria-label="{{ __('Verwijder :title uit maillijst', ['title' => $survey->title]) }}"
                            />
                        </div>
                    @empty
                        <flux:text class="rounded-md border border-dashed border-zinc-300 p-4 text-center text-sm text-zinc-500 dark:border-zinc-700">
                            {{ __('Je maillijst is nog leeg.') }}
                        </flux:text>
                    @endforelse
                </div>

                @if ($selectedSurveyIds !== [])
                    <flux:button class="mt-4" size="sm" variant="ghost" icon="trash" wire:click="clearList">
                        {{ __('Maillijst leegmaken') }}
                    </flux:button>
                @elseif ($manualEmails !== [])
                    <div class="mt-4 flex flex-wrap gap-2">
                        <flux:button size="sm" variant="ghost" icon="arrow-uturn-left" wire:click="removeLastManualEmail">
                            {{ __('Laatste handmatige ontvanger verwijderen') }}
                        </flux:button>
                        <flux:button size="sm" variant="ghost" icon="trash" wire:click="clearList">
                            {{ __('Maillijst leegmaken') }}
                        </flux:button>
                    </div>
                @endif

                <form wire:submit="send" class="mt-6 space-y-4">
                    <flux:input
                        wire:model="subject"
                        :label="__('Onderwerp')"
                        maxlength="255"
                    />
                    <flux:error name="subject" />

                    <flux:field>
                        <flux:label>{{ __('Bericht') }}</flux:label>
                        <flux:textarea wire:model="message" rows="8" maxlength="5000" />
                        <flux:error name="message" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Enquetelink toevoegen') }}</flux:label>
                        <select
                            wire:model="linkedSurveyId"
                            class="block h-10 w-full rounded-lg border border-zinc-200 border-b-zinc-300/80 bg-white px-3 text-sm text-zinc-700 shadow-xs dark:border-white/10 dark:bg-white/10 dark:text-zinc-300"
                        >
                            <option value="">{{ __('Geen link toevoegen') }}</option>
                            @foreach ($this->linkableSurveys as $survey)
                                <option value="{{ $survey->id }}">{{ $survey->title }}</option>
                            @endforeach
                        </select>
                        <flux:text class="mt-2 text-sm text-zinc-500">
                            {{ __('De mail krijgt een knop naar de gekozen actieve enquete.') }}
                        </flux:text>
                        <flux:error name="linkedSurveyId" />
                    </flux:field>

                    <flux:error name="selectedSurveyIds" />

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <flux:button
                            type="submit"
                            variant="primary"
                            icon="paper-airplane"
                            :disabled="$this->recipientCount === 0"
                            wire:loading.attr="disabled"
                        >
                            {{ __('Mail verzenden') }}
                        </flux:button>

                        <x-action-message on="participant-mails-sent">
                            {{ __('Verzonden naar :count unieke ontvangers.', ['count' => $lastSentCount]) }}
                        </x-action-message>
                    </div>
                </form>
            </div>
        </div>
    </x-pages::admin.layout>
</section>
