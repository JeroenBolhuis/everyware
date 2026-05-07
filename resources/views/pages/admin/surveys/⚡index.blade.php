<?php

use App\Models\Survey;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Enquete-inzendingen')] class extends Component {
    use WithPagination;

    public function mount(): void
    {
        $this->authorize('viewAny', Survey::class);
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
                                <flux:table.cell variant="strong" class="text-xs sm:text-sm">{{ $survey->title }}</flux:table.cell>
                                <flux:table.cell class="text-xs sm:text-sm">
                                    <flux:badge :color="$survey->is_active ? 'emerald' : 'zinc'" size="sm">
                                        {{ $survey->is_active ? __('Actief') : __('Inactief') }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell class="text-xs sm:text-sm">{{ $survey->responses_count }}</flux:table.cell>
                                <flux:table.cell align="end">
                                    <a href="{{ route('admin.surveys.show', $survey) }}" class="btn-secondary text-xs sm:text-sm whitespace-nowrap"
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
