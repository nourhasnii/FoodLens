<section
    x-data="{
        preview: null,
        pick() { this.$refs.photoInput.click(); },
        onChange(event) {
            const file = event.target.files[0];
            if (! file) return;
            this.preview = URL.createObjectURL(file);
            this.$refs.photoForm.submit();
        }
    }"
>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Photo de profil') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Formats acceptés : JPG, PNG, WEBP (max 2 Mo)') }}
        </p>
    </header>

    <div class="mt-6 flex flex-col items-start gap-6 sm:flex-row sm:items-center">
        {{-- Aperçu circulaire (initiales par défaut, photo si disponible) --}}
        <div class="relative shrink-0">
            <x-user-avatar
                :user="$user"
                size="2xl"
                class="bg-forest/10 text-forest/60 ring-2 ring-amber-warm/40 ring-offset-2 ring-offset-white"
            />

            <template x-if="preview">
                <img
                    :src="preview"
                    alt=""
                    class="absolute inset-0 h-full w-full rounded-full object-cover ring-2 ring-amber-warm/40 ring-offset-2 ring-offset-white"
                >
            </template>
        </div>

        <div class="space-y-3">
            {{-- Envoi d'une nouvelle photo --}}
            <form x-ref="photoForm" method="post" action="{{ route('profile.photo.update') }}" enctype="multipart/form-data">
                @csrf
                <input x-ref="photoInput" type="file" name="photo" accept="image/*" class="hidden" @change="onChange">
                <x-primary-button type="button" @click="pick()">
                    {{ __('Changer la photo') }}
                </x-primary-button>
            </form>

            {{-- Suppression de la photo existante --}}
            @if ($user->profile_photo_url)
                <form method="post" action="{{ route('profile.photo.destroy') }}"
                      onsubmit="return confirm('{{ __('Supprimer votre photo de profil ?') }}')">
                    @csrf
                    @method('delete')
                    <button type="submit" class="text-sm font-medium text-ink/50 transition-colors hover:text-red-600">
                        {{ __('Supprimer la photo') }}
                    </button>
                </form>
            @endif

            @error('photo', 'updatePhoto')
                <p class="field-validation-error text-sm text-red-600">{{ $message }}</p>
            @enderror

            @if (session('status') === 'photo-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2500)"
                   class="text-sm text-forest">{{ __('Votre photo de profil a été mise à jour.') }}</p>
            @elseif (session('status') === 'photo-removed')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2500)"
                   class="text-sm text-forest">{{ __('Votre photo de profil a été supprimée.') }}</p>
            @endif
        </div>
    </div>
</section>
