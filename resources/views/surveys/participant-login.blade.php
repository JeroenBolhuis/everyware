<x-layout>
    <div class="overflow-x-hidden">
        <main class="mx-auto w-full max-w-lg px-4 py-8 sm:py-12">
            <div class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm sm:p-7">
                <h1 class="text-2xl font-bold text-zinc-950 sm:text-3xl">Inloggen voor de enquête</h1>
                <p class="mt-2 text-sm text-zinc-600 sm:text-base">
                    Vul je school-e-mailadres in. Je ontvangt een link waarmee je zonder wachtwoord verder kunt.
                </p>

                @if (session('magicLinkStatus') === 'sent')
                    <div class="mt-6 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800 sm:p-4">
                        Als dit adres bij ons bekend is of geldig is, hebben we je een e-mail gestuurd. Controleer je inbox en klik op de knop in de mail.
                    </div>
                @endif

                <form method="POST" action="{{ route('survey.participant.login.store') }}" class="mt-6 space-y-5">
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
                            class="w-full rounded-lg border border-zinc-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-red-300"
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
