<x-guest-layout>
    <div class="bg-white rounded-2xl shadow-xl shadow-ink/5 border border-ink/5 p-6 sm:p-8 md:p-10 lg:p-12" x-data="{ recovery: false }">
        <div class="mb-8">
            <h1 class="font-fraunces font-bold text-3xl sm:text-4xl text-ink tracking-tight mb-2">Vérification en deux étapes</h1>
            <p class="text-ink/60 text-sm sm:text-base" x-show="! recovery">
                Saisissez le code à 6 chiffres généré par votre application d'authentification pour terminer la connexion.
            </p>
            <p class="text-ink/60 text-sm sm:text-base" x-show="recovery" x-cloak>
                Saisissez l'un de vos codes de secours pour terminer la connexion.
            </p>
        </div>

        <x-auth-session-status class="mb-6" :status="session('status')" />

        <form method="POST" action="{{ route('two-factor.challenge') }}" class="space-y-5 sm:space-y-6">
            @csrf

            <div x-show="! recovery">
                <x-input-label for="code" :value="__('Code d\'authentification')" />
                <x-text-input id="code" x-ref="code" class="block mt-1 w-full px-4 py-3 tracking-[0.3em] text-center text-lg"
                                type="text" inputmode="numeric" autocomplete="one-time-code"
                                name="code" autofocus placeholder="123456" />
                @error('code')<p class="field-validation-error mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div x-show="recovery" x-cloak>
                <x-input-label for="recovery_code" :value="__('Code de secours')" />
                <x-text-input id="recovery_code" x-ref="recovery" class="block mt-1 w-full px-4 py-3"
                                type="text" autocomplete="one-time-code"
                                name="recovery_code" placeholder="xxxxxxxxxx-xxxxxxxxxx" />
                @error('recovery_code')<p class="field-validation-error mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="text-sm">
                <button type="button" x-show="! recovery"
                        x-on:click="recovery = true; $nextTick(() => $refs.recovery?.focus())"
                        class="text-ink/60 hover:text-forest underline decoration-1 underline-offset-4 hover:underline-offset-2 transition-all">
                    Utiliser un code de secours
                </button>
                <button type="button" x-show="recovery" x-cloak
                        x-on:click="recovery = false; $nextTick(() => $refs.code?.focus())"
                        class="text-ink/60 hover:text-forest underline decoration-1 underline-offset-4 hover:underline-offset-2 transition-all">
                    Utiliser un code d'authentification
                </button>
            </div>

            <x-primary-button class="w-full py-3 text-base justify-center">
                {{ __('Valider') }}
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('two-factor.cancel') }}" class="mt-6 flex justify-center">
            @csrf
            @method('delete')
            <button type="submit" class="text-sm text-ink/50 hover:text-amber-warm rounded-md focus:outline-none focus:ring-2 focus:ring-amber-warm/30 focus:ring-offset-2 transition-colors">
                Annuler et revenir à la connexion
            </button>
        </form>
    </div>
</x-guest-layout>
