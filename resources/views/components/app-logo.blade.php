@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="" {{ $attributes }}>
        <x-slot name="logo" class="flex h-14 w-36 items-center justify-start overflow-visible">
            <img src="/images/Avans_Hogeschool_Logo.png" alt="Avans Hogeschool Logo" class="h-8 w-auto max-w-none" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="" {{ $attributes }}>
        <x-slot name="logo" class="flex h-14 w-36 items-center justify-start overflow-visible">
            <img src="/images/Avans_Hogeschool_Logo.png" alt="Avans Hogeschool Logo" class="h-8 w-auto max-w-none" />
        </x-slot>
    </flux:brand>
@endif
