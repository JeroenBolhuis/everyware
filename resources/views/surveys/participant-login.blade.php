<x-layout>
    <div class="min-h-screen flex flex-col overflow-x-hidden">
        <x-surveys.page-header/>

        <main class="flex-1 max-w-2xl mx-auto w-full py-6 sm:py-10 px-3 sm:px-4 md:px-6">
            <div class="bg-white border rounded-lg sm:rounded-2xl shadow-md p-4 sm:p-6 md:p-8">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">Inloggen voor de enquête</h1>
                <p class="text-sm sm:text-base text-gray-600 mb-6">
                    Vul je school-e-mailadres in. Je ontvangt een link waarmee je zonder wachtwoord verder kunt.
                </p>

                @if (session('magicLinkStatus') === 'sent')
                    <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-3 sm:p-4 text-sm sm:text-base text-green-800">
                        Als dit adres bij ons bekend is of geldig is, hebben we je een e-mail gestuurd. Controleer je inbox en klik op de knop in de mail.
                    </div>
                @endif

                <form method="POST" action="{{ route('survey.participant.login.store') }}" class="space-y-5">
                    @csrf
                    <input type="hidden" name="redirect" value="{{ $redirect }}">

                    <div>
                        <label for="email" class="mb-1 block text-sm font-medium text-gray-700">
                            E-mailadres <span class="text-red-600">*</span>
                        </label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autocomplete="email"
                            class="w-full rounded-full border border-gray-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-red-300"
                            placeholder="naam@voorbeeld.nl"
                        >
                        @error('email')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="btn-primary w-full sm:w-auto">
                        Stuur mij een inloglink
                    </button>
                </form>
            </div>
        </main>
    </div>
</x-layout>
