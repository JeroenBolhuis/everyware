<?php

use App\Models\Participant;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Deelnemers')] class extends Component {
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Participant::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function getParticipantsProperty()
    {
        $search = trim($this->search);

        return Participant::query()
            ->withCount(['surveyResponses' => fn ($query) => $query->visibleInResults()])
            ->when($search !== '', fn ($query) => $query
                ->where('public_code', preg_replace('/\D/', '', $search) ?: '__none__')
            )
            ->orderBy('public_code')
            ->paginate(15);
    }
}; ?>

<section class="w-full" aria-labelledby="admin-participants-page-title">
    <x-pages::admin.layout
        :heading="__('Deelnemers')"
        :subheading="__('Zoek op volgnummer om inzendingen te bekijken of een deelnemer te blokkeren.')"
        heading-id="admin-participants-page-title"
    >
        <x-pages::admin.participants.layout>
            <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm sm:p-6 dark:border-neutral-700 dark:bg-zinc-900">
                <div class="mb-4">
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('Zoek op volgnummer...') }}"
                        icon="magnifying-glass"
                        clearable
                        class="w-full text-xs sm:text-sm"
                        aria-label="{{ __('Zoek deelnemers op volgnummer') }}"
                    />
                </div>

                <div class="overflow-x-auto">
                    <flux:table :paginate="$this->participants">
                        <flux:table.columns>
                            <flux:table.column class="text-xs sm:text-sm">{{ __('Volgnummer') }}</flux:table.column>
                            <flux:table.column class="text-xs sm:text-sm">{{ __('Status') }}</flux:table.column>
                            <flux:table.column class="text-xs sm:text-sm">{{ __('Inzendingen') }}</flux:table.column>
                            <flux:table.column align="end" class="text-xs sm:text-sm">{{ __('Acties') }}</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @forelse ($this->participants as $participant)
                                <flux:table.row :key="$participant->id">
                                    <flux:table.cell variant="strong" class="text-xs sm:text-sm">
                                        {{ $participant->displayNameFor(auth()->user()) }}
                                    </flux:table.cell>
                                    <flux:table.cell class="text-xs sm:text-sm">
                                        @if ($participant->isBlocked())
                                            <flux:badge color="red" size="sm">{{ __('Geblokkeerd') }}</flux:badge>
                                        @else
                                            <flux:badge color="emerald" size="sm">{{ __('Actief') }}</flux:badge>
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell class="text-xs sm:text-sm">
                                        {{ $participant->survey_responses_count }}
                                    </flux:table.cell>
                                    <flux:table.cell align="end">
                                        <flux:button
                                            variant="ghost"
                                            size="sm"
                                            icon="eye"
                                            :href="route('admin.participants.show', $participant)"
                                            wire:navigate
                                            aria-label="{{ __('Bekijk deelnemer :name', ['name' => $participant->displayNameFor(auth()->user())]) }}"
                                        >
                                            {{ __('Bekijken') }}
                                        </flux:button>
                                    </flux:table.cell>
                                </flux:table.row>
                            @empty
                                <flux:table.row>
                                    <flux:table.cell colspan="4">
                                        <flux:text class="text-center text-xs sm:text-sm text-zinc-500">
                                            {{ $search ? __('Geen deelnemers gevonden voor ":search".', ['search' => $search]) : __('Er zijn nog geen deelnemers.') }}
                                        </flux:text>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforelse
                        </flux:table.rows>
                    </flux:table>
                </div>
            </div>
        </x-pages::admin.participants.layout>
    </x-pages::admin.layout>
</section>
