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

    public function mount(): void
    {
        $this->authorize('viewAny', Survey::class);

        $this->autoDeleteAfterDays = SurveyAnswerRetentionSetting::query()
            ->value('auto_delete_after_days');
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
            ->withCount('responses')
            ->orderBy('title')
            ->paginate(15);
    }
}; ?>

<section class="w-full">
    @include('partials.admin-heading')

    <flux:heading class="sr-only">{{ __('Enquete-inzendingen') }}</flux:heading>

    <x-pages::admin.layout
        :heading="__('Enquete-inzendingen')"
        :subheading="__('Bekijk enquetes en open individuele inzendingen, inclusief gedeelde contactgegevens.')"
    >
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
