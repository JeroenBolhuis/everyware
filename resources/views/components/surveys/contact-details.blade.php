<div class="grid gap-4 md:grid-cols-2">
    <div class="md:col-span-2">
        <label for="contact_name" class="mb-1 block text-sm font-medium text-gray-700">
            Naam <span class="text-red-600">*</span>
        </label>
        <input
            id="contact_name"
            type="text"
            name="contact_name"
            value="{{ old('contact_name') }}"
            autocomplete="name"
            class="w-full rounded-full border border-gray-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-red-300"
            placeholder="Bijvoorbeeld: Jamie Jansen"
        >
        @error('contact_name')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2">
        <label for="contact_email" class="mb-1 block text-sm font-medium text-gray-700">
            E-mailadres <span class="text-red-600">*</span>
        </label>
        <input
            id="contact_email"
            type="email"
            name="contact_email"
            value="{{ old('contact_email') }}"
            autocomplete="email"
            class="w-full rounded-full border border-gray-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-red-300"
            placeholder="naam@voorbeeld.nl"
        >
        @error('contact_email')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>
