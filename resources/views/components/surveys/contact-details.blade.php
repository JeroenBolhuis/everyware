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
            required
            class="w-full rounded-xl border border-gray-200 p-3 focus:outline-none focus:ring-2 focus:ring-red-300"
            placeholder="Bijvoorbeeld: Jamie Jansen"
        >
        @error('contact_name')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="contact_email" class="mb-1 block text-sm font-medium text-gray-700">
            E-mailadres <span class="text-red-600">*</span>
        </label>
        <input
            id="contact_email"
            type="email"
            name="contact_email"
            value="{{ old('contact_email') }}"
            autocomplete="email"
            required
            class="w-full rounded-xl border border-gray-200 p-3 focus:outline-none focus:ring-2 focus:ring-red-300"
            placeholder="naam@voorbeeld.nl"
        >
        @error('contact_email')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="contact_phone" class="mb-1 block text-sm font-medium text-gray-700">
            Telefoonnummer <span class="text-gray-500">(optioneel)</span>
        </label>
        <input
            id="contact_phone"
            type="tel"
            name="contact_phone"
            value="{{ old('contact_phone') }}"
            autocomplete="tel"
            class="w-full rounded-xl border border-gray-200 p-3 focus:outline-none focus:ring-2 focus:ring-red-300"
            placeholder="06 12345678"
        >
        @error('contact_phone')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>