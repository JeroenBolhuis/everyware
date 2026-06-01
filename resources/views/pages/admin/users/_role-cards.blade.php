@props(['role'])

<div class="mt-3 grid grid-cols-1 gap-3 lg:grid-cols-3">
    @foreach (\App\Enums\Role::cases() as $roleOption)
        <label
            wire:key="role-card-{{ $roleOption->value }}"
            class="flex min-h-full cursor-pointer flex-col justify-between rounded-lg border border-zinc-200 bg-white p-4 text-left transition hover:border-zinc-300 hover:bg-zinc-50 focus-within:ring-2 focus-within:ring-red-500 focus-within:ring-offset-2 [&:has(input:checked)]:border-red-500 [&:has(input:checked)]:bg-red-50 [&:has(input:checked)]:ring-2 [&:has(input:checked)]:ring-red-200 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600 dark:hover:bg-zinc-800 dark:focus-within:ring-offset-zinc-900 dark:[&:has(input:checked)]:border-red-400 dark:[&:has(input:checked)]:bg-red-950/20 dark:[&:has(input:checked)]:ring-red-900/70"
        >
            <span class="flex items-start justify-between gap-3">
                <span>
                    <span class="block text-base font-semibold text-zinc-950 dark:text-white">
                        {{ $roleOption->label() }}
                    </span>
                    <span class="mt-1 block text-sm leading-5 text-zinc-600 dark:text-zinc-400">
                        {{ $roleOption->description() }}
                    </span>
                </span>

                <input
                    type="radio"
                    wire:model="role"
                    name="role"
                    value="{{ $roleOption->value }}"
                    @checked($roleOption->value === $role)
                    class="mt-1 size-5 shrink-0 border-zinc-300 text-red-600 focus:ring-red-500 dark:border-zinc-600 dark:bg-zinc-900"
                />
            </span>

            <div class="mt-4 space-y-2">
                @foreach (\App\Enums\Role::permissions() as $permission => $label)
                    @php
                        $isGranted = $roleOption->grantsPermission($permission);
                    @endphp

                    <span
                        @class([
                            'flex items-start gap-2 text-sm leading-5',
                            'font-medium text-emerald-700 dark:text-emerald-300' => $isGranted,
                            'text-zinc-400 dark:text-zinc-500' => ! $isGranted,
                        ])
                    >
                        @if ($isGranted)
                            <flux:icon.check variant="micro" class="mt-0.5 shrink-0" />
                        @else
                            <flux:icon.x-mark variant="micro" class="mt-0.5 shrink-0" />
                        @endif

                        <span>{{ $label }}</span>
                    </span>
                @endforeach
            </div>
        </label>
    @endforeach
</div>
