<?php

use App\Models\Survey;
use App\Models\SurveyResponse;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Enquete-inzendingen')] class extends Component {
    use WithPagination;

    public int $retentionYears = 5;
    public int $upcomingDeletionWarningDays = 7;
    public bool $showUpcomingDeletionWarning = true;

    public function mount(): void
    {
        $this->authorize('viewAny', Survey::class);

        $this->retentionYears = (int) config('surveys.retention_years');
        $this->upcomingDeletionWarningDays = (int) config('surveys.upcoming_warning_days');
    }

    public function dismissUpcomingDeletionWarning(): void
    {
        $this->showUpcomingDeletionWarning = false;
    }

    public function saveRetentionYears(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $validated = $this->validate([
            'retentionYears' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $retentionYears = (int) $validated['retentionYears'];

        if (! app()->runningUnitTests()) {
            $this->updateEnvValue('SURVEYS_RETENTION_YEARS', (string) $retentionYears);
        }

        config(['surveys.retention_years' => $retentionYears]);
        $this->retentionYears = $retentionYears;

        $this->dispatch('retention-setting-saved');
    }

    private function updateEnvValue(string $key, string $value): void
    {
        $envPath = base_path('.env');
        $envContent = file_exists($envPath) ? file_get_contents($envPath) : false;

        if ($envContent === false) {
            return;
        }

        $pattern = '/^'.preg_quote($key, '/').'=.*$/m';
        $replacement = $key.'='.$value;

        if (preg_match($pattern, $envContent) === 1) {
            $updatedEnvContent = (string) preg_replace($pattern, $replacement, $envContent);
        } else {
            $updatedEnvContent = rtrim($envContent).PHP_EOL.$replacement.PHP_EOL;
        }

        file_put_contents($envPath, $updatedEnvContent);
    }

    public function getSurveysProperty()
    {
        return Survey::query()
            ->withCount(['responses' => fn ($query) => $query->visibleInResults()])
            ->orderBy('title')
            ->paginate(15);
    }

    public function getUpcomingDeletionWarningProperty(): array
    {
        $upcomingResponses = $this->upcomingDeletionResponses;
        $count = $upcomingResponses->count();
        $nextDeleteOnDate = $upcomingResponses
            ->map(fn (SurveyResponse $response) => $response->deleteOnDate())
            ->filter()
            ->sort()
            ->first();

        return [
            'count' => $count,
            'next_delete_on_date' => $nextDeleteOnDate,
        ];
    }

    public function getUpcomingDeletionResponsesProperty()
    {
        $windowStart = now()->subYears($this->retentionYears);
        $windowEnd = now()->addDays($this->upcomingDeletionWarningDays)->subYears($this->retentionYears);

        return SurveyResponse::query()
            ->with('survey')
            ->where(function ($query) use ($windowStart, $windowEnd): void {
                $query->whereBetween('submitted_at', [$windowStart, $windowEnd])
                    ->orWhere(function ($query) use ($windowStart, $windowEnd): void {
                        $query->whereNull('submitted_at')
                            ->whereBetween('created_at', [$windowStart, $windowEnd]);
                    });
            })
            ->orderBy('submitted_at')
            ->limit(25)
            ->get();
    }
}; ?>

<section class="w-full" aria-labelledby="admin-surveys-page-title">
    @include('partials.admin-heading')

    <flux:heading class="sr-only" id="admin-surveys-page-title">{{ __('Enquete-inzendingen') }}</flux:heading>

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

                @if ($this->upcomingDeletionWarning['count'] > 0)
                    <flux:text class="mt-2 text-sm">
                        {{ __('Er worden binnenkort :count inzendingen automatisch verwijderd. Eerstvolgende verwijderdatum: :date.', ['count' => $this->upcomingDeletionWarning['count'], 'date' => $this->upcomingDeletionWarning['next_delete_on_date']?->format('d-m-Y')]) }}
                    </flux:text>

                    <details class="mt-4 rounded-lg border border-amber-300 bg-white/70 p-3 text-sm dark:border-amber-600 dark:bg-amber-950/40">
                        <summary class="cursor-pointer font-medium">
                            {{ __('Toon inzendingen die binnenkort verwijderd worden') }}
                        </summary>

                        <div class="mt-3 space-y-2">
                            @foreach ($this->upcomingDeletionResponses as $upcomingResponse)
                                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                    <span>
                                        {{ __('Inzending #:id (:survey) - verwijdering op :date', ['id' => $upcomingResponse->id, 'survey' => $upcomingResponse->survey?->title ?? __('Onbekende enquete'), 'date' => $upcomingResponse->deleteOnDate()?->format('d-m-Y')]) }}
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
                {{ __('Ingestelde bewaartermijn: :value jaar', ['value' => $retentionYears]) }}
            </flux:text>

            @if (auth()->user()?->isAdmin())
                <form wire:submit="saveRetentionYears" class="mt-4 space-y-4">
                    <flux:input
                        wire:model="retentionYears"
                        :label="__('Bewaartermijn in jaren')"
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
                                       wire:navigate
                                       aria-label="{{ __('Bekijk inzendingen van enquête :title', ['title' => $survey->title]) }}">{{ __('Bekijk inzendingen') }}</a>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        </div>
    </x-pages::admin.layout>
</section>
