<?php

use App\Models\Survey;
use App\Models\SurveyAnswerRetentionSetting;
use App\Models\SurveyResponse;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Enquete-inzendingen')] class extends Component {
    use WithPagination;

    public ?int $autoDeleteAfterDays = null;
    public int $upcomingDeletionWarningDays = 7;
    public bool $showUpcomingDeletionWarning = true;

    public function mount(): void
    {
        $this->authorize('viewAny', Survey::class);

        $this->autoDeleteAfterDays = SurveyAnswerRetentionSetting::query()
            ->value('auto_delete_after_days');
    }

    public function dismissUpcomingDeletionWarning(): void
    {
        $this->showUpcomingDeletionWarning = false;
    }

    public function saveAutoDeleteAfterDays(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $validated = $this->validate([
            'autoDeleteAfterDays' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ]);

        $setting = SurveyAnswerRetentionSetting::query()->first();
        $currentAutoDeleteAfterDays = $setting?->auto_delete_after_days;
        $newAutoDeleteAfterDays = $validated['autoDeleteAfterDays'];

        if ($setting === null) {
            SurveyAnswerRetentionSetting::query()->create([
                'auto_delete_after_days' => $newAutoDeleteAfterDays,
            ]);
        } else {
            $setting->update([
                'auto_delete_after_days' => $newAutoDeleteAfterDays,
            ]);
        }

        $this->autoDeleteAfterDays = $newAutoDeleteAfterDays;

        if ($newAutoDeleteAfterDays !== null && $currentAutoDeleteAfterDays === null) {
            $this->applyDeleteDateToResponsesWithoutDeadline($newAutoDeleteAfterDays);
        }

        if (
            $newAutoDeleteAfterDays !== null
            && $currentAutoDeleteAfterDays !== null
            && $newAutoDeleteAfterDays < $currentAutoDeleteAfterDays
        ) {
            $this->tightenExistingDeleteDates($newAutoDeleteAfterDays);
            $this->deleteExpiredResponses();
        }

        $this->dispatch('retention-setting-saved');
    }

    private function applyDeleteDateToResponsesWithoutDeadline(int $days): void
    {
        SurveyResponse::query()
            ->whereNull('delete_on_date')
            ->chunkById(200, function ($responses) use ($days): void {
                foreach ($responses as $response) {
                    $referenceDate = $response->submitted_at ?? $response->created_at;

                    if ($referenceDate === null) {
                        continue;
                    }

                    $response->update([
                        'delete_on_date' => $referenceDate->copy()->addDays($days)->toDateString(),
                    ]);
                }
            });
    }

    private function tightenExistingDeleteDates(int $days): void
    {
        SurveyResponse::query()
            ->chunkById(200, function ($responses) use ($days): void {
                foreach ($responses as $response) {
                    $referenceDate = $response->submitted_at ?? $response->created_at;

                    if ($referenceDate === null) {
                        continue;
                    }

                    $newDeleteOnDate = $referenceDate->copy()->addDays($days)->toDateString();
                    $currentDeleteOnDate = $response->delete_on_date?->toDateString();

                    if ($currentDeleteOnDate === null || $currentDeleteOnDate > $newDeleteOnDate) {
                        $response->update([
                            'delete_on_date' => $newDeleteOnDate,
                        ]);
                    }
                }
            });
    }

    private function deleteExpiredResponses(): void
    {
        $deleteSurveySubmission = app(\App\Actions\Surveys\DeleteSurveySubmission::class);

        SurveyResponse::query()
            ->whereDate('delete_on_date', '<=', now()->toDateString())
            ->chunkById(200, function ($responses) use ($deleteSurveySubmission): void {
                foreach ($responses as $response) {
                    $deleteSurveySubmission->handle($response);
                }
            });
    }

    public function getSurveysProperty()
    {
        return Survey::query()
            ->withCount(['responses' => fn ($query) => $query->visibleInResults()])
            ->orderBy('title')
            ->paginate(15);
    }

    public function getUpcomingDeletionWarningProperty(): ?array
    {
        $today = now()->toDateString();
        $warningThreshold = now()->addDays($this->upcomingDeletionWarningDays)->toDateString();

        $upcomingResponses = SurveyResponse::query()
            ->whereNotNull('delete_on_date')
            ->whereDate('delete_on_date', '>=', $today)
            ->whereDate('delete_on_date', '<=', $warningThreshold);

        $count = (clone $upcomingResponses)->count();

        if ($count === 0) {
            return null;
        }

        return [
            'count' => $count,
            'next_delete_on_date' => (clone $upcomingResponses)->min('delete_on_date'),
        ];
    }

    public function getUpcomingDeletionResponsesProperty()
    {
        $today = now()->toDateString();
        $warningThreshold = now()->addDays($this->upcomingDeletionWarningDays)->toDateString();

        return SurveyResponse::query()
            ->with('survey')
            ->whereNotNull('delete_on_date')
            ->whereDate('delete_on_date', '>=', $today)
            ->whereDate('delete_on_date', '<=', $warningThreshold)
            ->orderBy('delete_on_date')
            ->limit(25)
            ->get();
    }
}; ?>

<section class="w-full">
    @include('partials.admin-heading')

    <flux:heading class="sr-only">{{ __('Enquete-inzendingen') }}</flux:heading>

    <x-pages::admin.layout
        :heading="__('Enquete-inzendingen')"
        :subheading="__('Bekijk enquetes en open individuele inzendingen, inclusief gedeelde contactgegevens.')"
    >
        @if ($showUpcomingDeletionWarning)
            <div class="my-6 rounded-lg sm:rounded-xl border border-amber-300 bg-amber-50 p-4 sm:p-6 text-amber-950 dark:border-amber-700 dark:bg-amber-950/30 dark:text-amber-100">
                <div class="flex items-start justify-between gap-3">
                    <flux:heading size="lg">{{ __('Waarschuwing automatische verwijdering') }}</flux:heading>
                    <button
                        type="button"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-amber-300 bg-white/70 text-amber-900 hover:bg-white dark:border-amber-600 dark:bg-amber-950/40 dark:text-amber-100"
                        wire:click="dismissUpcomingDeletionWarning"
                        aria-label="{{ __('Melding sluiten') }}"
                    >
                        &times;
                    </button>
                </div>

                @if ($this->upcomingDeletionWarning !== null)
                    <flux:text class="mt-2 text-sm">
                        {{ __('Er worden binnenkort :count inzendingen automatisch verwijderd. Eerstvolgende verwijderdatum: :date.', ['count' => $this->upcomingDeletionWarning['count'], 'date' => \Illuminate\Support\Carbon::parse($this->upcomingDeletionWarning['next_delete_on_date'])->format('d-m-Y')]) }}
                    </flux:text>

                    <details class="mt-4 rounded-lg border border-amber-300 bg-white/70 p-3 text-sm dark:border-amber-600 dark:bg-amber-950/40">
                        <summary class="cursor-pointer font-medium">
                            {{ __('Toon inzendingen die binnenkort verwijderd worden') }}
                        </summary>

                        <div class="mt-3 space-y-2">
                            @foreach ($this->upcomingDeletionResponses as $upcomingResponse)
                                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                    <span>
                                        {{ __('Inzending #:id (:survey) - verwijdering op :date', ['id' => $upcomingResponse->id, 'survey' => $upcomingResponse->survey?->title ?? __('Onbekende enquete'), 'date' => $upcomingResponse->delete_on_date?->format('d-m-Y')]) }}
                                    </span>
                                    <a
                                        href="{{ route('admin.responses.show', $upcomingResponse) }}"
                                        class="btn-secondary w-fit text-xs"
                                        wire:navigate
                                    >
                                        {{ __('Open inzending') }}
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </details>
                @else
                    <flux:text class="mt-2 text-sm">
                        {{ __('Er staan momenteel geen automatische verwijderingen gepland binnen :days dagen.', ['days' => $upcomingDeletionWarningDays]) }}
                    </flux:text>
                @endif
            </div>
        @endif

        <div
            class="my-6 rounded-lg sm:rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 shadow-sm dark:border-neutral-700 dark:bg-zinc-900">
            <flux:heading size="lg">{{ __('Automatische verwijdering van antwoorden') }}</flux:heading>
            <flux:text class="mt-2 text-sm text-zinc-500">
                {{ __('Ingestelde bewaartermijn: :value', ['value' => $autoDeleteAfterDays !== null ? $autoDeleteAfterDays.' dagen' : __('Uitgeschakeld')]) }}
            </flux:text>

            @if (auth()->user()?->isAdmin())
                <form wire:submit="saveAutoDeleteAfterDays" class="mt-4 space-y-4">
                    <flux:input
                        wire:model="autoDeleteAfterDays"
                        :label="__('Verwijder antwoorden na (dagen)')"
                        type="number"
                        min="1"
                        inputmode="numeric"
                    />

                    <div class="flex items-center gap-4">
                        <button type="submit" class="btn-primary">{{ __('Opslaan') }}</button>

                        <x-action-message on="retention-setting-saved">
                            {{ __('Opgeslagen.') }}
                        </x-action-message>
                    </div>
                </form>
            @else
                <flux:text class="mt-4 text-sm text-zinc-500">
                    {{ __('alleen administratoren kunnen deze waarden aanpassen') }}
                </flux:text>
            @endif
        </div>

        <div
            class="my-6 rounded-lg sm:rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 shadow-sm dark:border-neutral-700 dark:bg-zinc-900">
            <div class="overflow-x-auto">
                <flux:table :paginate="$this->surveys">
                    <flux:table.columns>
                        <flux:table.column class="text-xs sm:text-sm">{{ __('Enquete') }}</flux:table.column>
                        <flux:table.column class="text-xs sm:text-sm">{{ __('Status') }}</flux:table.column>
                        <flux:table.column class="text-xs sm:text-sm">{{ __('Inzendingen') }}</flux:table.column>
                        <flux:table.column align="end" class="text-xs sm:text-sm">{{ __('Acties') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($this->surveys as $survey)
                            <flux:table.row :key="$survey->id">
                                <flux:table.cell variant="strong"
                                                 class="text-xs sm:text-sm">{{ $survey->title }}</flux:table.cell>
                                <flux:table.cell class="text-xs sm:text-sm">
                                    <flux:badge :color="$survey->is_active ? 'emerald' : 'zinc'" size="sm">
                                        {{ $survey->is_active ? __('Actief') : __('Inactief') }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell
                                    class="text-xs sm:text-sm">{{ $survey->responses_count }}</flux:table.cell>
                                <flux:table.cell align="end">
                                    <a href="{{ route('admin.surveys.show', $survey) }}"
                                       class="btn-secondary text-xs sm:text-sm whitespace-nowrap"
                                       wire:navigate>{{ __('Bekijk inzendingen') }}</a>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        </div>
    </x-pages::admin.layout>
</section>
