<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <p class="text-muted">{{ __('Create, send and analyse enquetes for the LIC.') }}</p>
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
                <div class="absolute inset-0 flex items-end p-3">
                    <span class="text-caption">{{ __('Nieuwe enquête aanmaken') }}</span>
                </div>
            </div>
            <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
                <div class="absolute inset-0 flex items-end p-3">
                    <span class="text-caption">{{ __('Enquetes versturen') }}</span>
                </div>
            </div>
            <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
                <div class="absolute inset-0 flex items-end p-3">
                    <span class="text-caption">{{ __('Resultaten bekijken') }}</span>
                </div>
            </div>
        </div>
        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
            <div class="absolute inset-0 flex items-center justify-center p-6">
                <span class="text-muted">{{ __('Overzicht en activiteit — placeholder') }}</span>
            </div>
        </div>
    </div>
</x-layouts::app>
