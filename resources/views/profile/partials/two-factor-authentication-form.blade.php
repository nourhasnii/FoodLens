<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __("Authentification à deux facteurs") }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Ajoutez une couche de sécurité supplémentaire en exigeant un code temporaire généré par votre application d'authentification (Google Authenticator, Authy, ...).") }}
        </p>
    </header>

    <div class="mt-6 space-y-6">
        @if (session('status') === 'two-factor-confirmed')
            <p class="text-sm text-forest">{{ __("L'authentification à deux facteurs est désormais active.") }}</p>
        @elseif (session('status') === 'two-factor-disabled')
            <p class="text-sm text-forest">{{ __("L'authentification à deux facteurs a été désactivée.") }}</p>
        @elseif (session('status') === 'recovery-codes-regenerated')
            <p class="text-sm text-forest">{{ __("Vos codes de secours ont été régénérés.") }}</p>
        @endif

        {{-- State 1: not enabled, no pending secret --}}
        @if (! $user->hasEnabledTwoFactorAuthentication() && ! $user->hasPendingTwoFactorConfirmation())
            <p class="text-sm text-gray-600">
                {{ __("Vous n'avez pas encore activé l'authentification à deux facteurs.") }}
            </p>

            <form method="post" action="{{ route('two-factor.enable') }}">
                @csrf
                <x-primary-button>
                    {{ __("Activer l'authentification à deux facteurs") }}
                </x-primary-button>
            </form>
        @endif

        {{-- State 2: secret generated, waiting for OTP confirmation --}}
        @if ($user->hasPendingTwoFactorConfirmation())
            <div class="space-y-4">
                <p class="text-sm text-gray-600">
                    {{ __("Scannez ce QR code avec votre application d'authentification, puis saisissez le code généré pour finaliser l'activation.") }}
                </p>

                <div class="inline-block rounded-lg border border-gray-200 bg-white p-4">
                    {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(180)->margin(1)->generate($user->twoFactorQrCodeUrl()) !!}
                </div>

                <div class="text-xs text-gray-500">
                    <span class="font-semibold">{{ __("Clé de configuration :") }}</span>
                    <code class="break-all">{{ $user->two_factor_secret }}</code>
                </div>

                <form method="post" action="{{ route('two-factor.confirm') }}" class="space-y-3">
                    @csrf

                    <div>
                        <x-input-label for="two_factor_code" :value="__('Code de vérification')" />
                        <x-text-input id="two_factor_code" name="code" type="text" inputmode="numeric"
                                      autocomplete="one-time-code" class="mt-1 block w-full" placeholder="123456" />
                        @error('code', 'twoFactor')<p class="field-validation-error mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>{{ __("Confirmer et activer") }}</x-primary-button>
                    </div>
                </form>

                <form method="post" action="{{ route('two-factor.disable') }}">
                    @csrf
                    @method('delete')
                    <button type="submit" class="text-sm text-ink/50 hover:text-red-600 transition-colors">
                        {{ __("Annuler l'activation") }}
                    </button>
                </form>
            </div>
        @endif

        {{-- State 3: 2FA enabled --}}
        @if ($user->hasEnabledTwoFactorAuthentication())
            <div class="space-y-6">
                <p class="inline-flex items-center gap-2 text-sm text-forest">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 6L9 17L4 12"/>
                    </svg>
                    {{ __("L'authentification à deux facteurs est activée.") }}
                </p>

                {{-- Recovery codes: displayed only right after activation or regeneration --}}
                @if (in_array(session('status'), ['two-factor-confirmed', 'recovery-codes-regenerated'], true))
                    <div class="rounded-lg border border-amber-warm/40 bg-amber-warm/5 p-4">
                        <p class="text-sm font-semibold text-ink">{{ __("Codes de secours") }}</p>
                        <p class="mt-1 text-xs text-gray-600">
                            {{ __("Conservez ces codes dans un endroit sûr. Chacun ne peut être utilisé qu'une seule fois et ils ne seront plus affichés.") }}
                        </p>
                        <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach ($user->two_factor_recovery_codes ?? [] as $recoveryCode)
                                <code class="block rounded-md bg-white px-3 py-1.5 text-xs text-ink border border-gray-200">{{ $recoveryCode }}</code>
                            @endforeach
                        </div>
                    </div>
                @endif

                <form method="post" action="{{ route('two-factor.recovery-codes') }}">
                    @csrf
                    <x-secondary-button type="submit">
                        {{ __("Régénérer les codes de secours") }}
                    </x-secondary-button>
                </form>

                <div class="border-t border-gray-200 pt-6">
                    <form method="post" action="{{ route('two-factor.disable') }}" class="space-y-3">
                        @csrf
                        @method('delete')

                        <div>
                            <x-input-label for="two_factor_disable_password" :value="__('Mot de passe actuel')" />
                            <x-text-input id="two_factor_disable_password" name="password" type="password"
                                          autocomplete="current-password" class="mt-1 block w-full" />
                            @error('password', 'twoFactor')<p class="field-validation-error mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <x-danger-button>{{ __("Désactiver l'authentification à deux facteurs") }}</x-danger-button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</section>
