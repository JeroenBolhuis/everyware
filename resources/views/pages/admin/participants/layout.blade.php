<div class="flex items-start max-lg:flex-col">
    <div class="me-10 w-full pb-4 lg:w-[220px]">
        <flux:navlist aria-label="{{ __('Deelnemers') }}">
            <flux:navlist.item
                :href="route('admin.participants.index')"
                :current="\Illuminate\Support\Facades\Route::is('admin.participants.index') || \Illuminate\Support\Facades\Route::is('admin.participants.show')"
                wire:navigate
            >
                {{ __('Overzicht') }}
            </flux:navlist.item>

            <flux:navlist.item
                :href="route('admin.participants.points')"
                :current="\Illuminate\Support\Facades\Route::is('admin.participants.points')"
                wire:navigate
            >
                {{ __('Punten aanpassen') }}
            </flux:navlist.item>
        </flux:navlist>
    </div>

    <flux:separator class="lg:hidden" />

    <div class="flex-1 self-stretch max-lg:pt-6">
        {{ $slot }}
    </div>
</div>
