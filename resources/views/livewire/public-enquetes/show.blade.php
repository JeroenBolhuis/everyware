<div class="space-y-6">
    <div class="space-y-2">
        <flux:text>
            <a href="{{ route('enquetes.index') }}" class="underline underline-offset-4" wire:navigate>
                {{ __('← Terug naar enquetes') }}
            </a>
        </flux:text>

        <flux:heading size="xl">{{ $enquete->title }}</flux:heading>

        @if (filled($enquete->description))
            <flux:text class="text-muted">{{ $enquete->description }}</flux:text>
        @endif
    </div>

    <flux:button :href="route('enquetes.fill', $enquete)" variant="primary" wire:navigate>
        {{ __('Begin Enquete') }}
    </flux:button>
</div>
