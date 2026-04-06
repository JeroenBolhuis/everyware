<x-layouts::app :title="__('Enquetes')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <p class="text-muted">{{ __('Maak, bewerk en verstuur enquetes. Bekijk hier reacties en resultaten.') }}</p>
        <div class="relative flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 min-h-[12rem] h-full">
            <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
            <div class="absolute inset-0 flex items-center justify-center p-6">
                <span class="text-muted">{{ __('Lijst met enquetes — nog niet beschikbaar') }}</span>
            </div>
        </div>
    </div>
</x-layouts::app>
