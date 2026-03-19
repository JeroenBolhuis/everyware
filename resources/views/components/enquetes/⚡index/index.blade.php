<x-layouts::public :title="__('Enquetes')">
    <div class="space-y-6">
        <div class="space-y-2">
            <flux:heading size="xl">{{ __('Enquetes') }}</flux:heading>
            <flux:text class="text-muted">{{ __('Kies een enquete om te starten.') }}</flux:text>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            @forelse ($enquetes as $enquete)
                <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="space-y-2">
                        <flux:heading>{{ $enquete->title }}</flux:heading>
                        @if (filled($enquete->description))
                            <flux:text class="text-muted line-clamp-3">{{ $enquete->description }}</flux:text>
                        @endif
                    </div>

                    <div class="mt-4">
                        <flux:button :href="route('enquetes.show', $enquete)" variant="primary" wire:navigate>
                            {{ __('Open enquete') }}
                        </flux:button>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 sm:col-span-2">
                    <flux:text class="text-muted">{{ __('Er zijn nog geen enquetes beschikbaar.') }}</flux:text>
                </div>
            @endforelse
        </div>
    </div>
</x-layouts::public>