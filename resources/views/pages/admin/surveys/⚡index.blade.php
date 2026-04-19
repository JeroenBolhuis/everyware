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
            class="my-6 rounded-xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-700 dark:bg-zinc-900">
            <flux:table :paginate="$this->surveys">
                <flux:table.columns>
                    <flux:table.column>{{ __('Enquete') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column>{{ __('Inzendingen') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('Acties') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->surveys as $survey)
                        <flux:table.row :key="$survey->id">
                            <flux:table.cell variant="strong">{{ $survey->title }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="$survey->is_active ? 'emerald' : 'zinc'" size="sm">
                                    {{ $survey->is_active ? __('Actief') : __('Inactief') }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $survey->responses_count }}</flux:table.cell>
                            <flux:table.cell align="end">
                                <a href="{{ route('admin.surveys.show', $survey) }}" class="btn-secondary"
                                   wire:navigate>{{ __('Bekijk inzendingen') }}</a>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    </x-pages::admin.layout>
</section>
