@php
    $status = $user->account_status;
    $isInformationRequired = $status === 'information_required';
    $isRejected = $status === 'rejected';
    $isSuspended = $status === 'suspended';

    $heading = match (true) {
        $isInformationRequired => 'Informations complémentaires requises',
        $isRejected => 'Votre demande n’a pas été retenue',
        $isSuspended => 'Votre compte est temporairement suspendu',
        default => 'Merci pour votre inscription',
    };

    $message = match (true) {
        $isInformationRequired => 'Notre équipe a besoin de quelques précisions avant de valider votre compte professionnel. Merci de compléter et de renvoyer votre dossier.',
        $isRejected => 'Après examen, votre demande de compte professionnel n’a pas été validée par l’équipe NutriTrace.',
        $isSuspended => 'L’accès à votre compte a été temporairement suspendu. Contactez l’équipe NutriTrace pour plus d’informations.',
        default => 'Votre compte professionnel a bien été reçu et est en cours de vérification par l’équipe NutriTrace. Vous recevrez un email dès qu’une décision sera prise.',
    };

    $eyebrow = match (true) {
        $isInformationRequired => 'Action requise',
        $isRejected => 'Demande refusée',
        $isSuspended => 'Compte suspendu',
        default => 'Compte en attente de validation',
    };
@endphp

<x-guest-layout>
    <div class="rounded-3xl border border-ink/8 bg-white p-8 text-center shadow-sm sm:p-10">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-warm/10 text-amber-warm">
            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 2h12M6 22h12" />
                <path d="M8 2v3.5c0 2.2 4 3.6 4 6.5s-4 4.3-4 6.5V22" />
                <path d="M16 2v3.5c0 2.2-4 3.6-4 6.5s4 4.3 4 6.5V22" />
            </svg>
        </div>

        <p class="mt-6 text-xs font-bold uppercase tracking-[0.18em] text-amber-warm">{{ $eyebrow }}</p>
        <h1 class="mt-2 font-fraunces text-2xl font-bold text-ink sm:text-3xl">{{ $heading }}</h1>
        <p class="mx-auto mt-4 max-w-md text-sm leading-6 text-ink/60">{{ $message }}</p>

        {{-- Identité du compte --}}
        <div class="mx-auto mt-6 max-w-sm rounded-2xl border border-ink/8 bg-cream/60 px-4 py-3 text-left">
            <p class="truncate text-sm font-semibold text-ink">{{ $user->name }}</p>
            <p class="mt-0.5 truncate text-xs text-ink/50">{{ $user->email }}</p>
            <span class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-ink/5 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-ink/60">
                <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-amber-warm"></span>
                {{ $user->role_label }}
            </span>
        </div>

        @if($isRejected && $user->rejection_reason)
            <div class="mx-auto mt-5 max-w-sm rounded-2xl bg-rose-500/10 px-4 py-3 text-left text-sm text-rose-700">
                <p class="font-semibold">Motif</p>
                <p class="mt-1">{{ $user->rejection_reason }}</p>
            </div>
        @endif

        @if($isInformationRequired)
            <div class="mt-6">
                <a href="{{ route('account.verification.edit') }}"
                   class="inline-flex w-full items-center justify-center rounded-full bg-[#1F3D2E] px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-[#16281E]">
                    Compléter mon dossier
                </a>
            </div>
        @endif

        <div class="mt-8 border-t border-ink/8 pt-6">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-secondary-button type="submit" class="w-full justify-center">Se déconnecter</x-secondary-button>
            </form>
            <a href="{{ url('/') }}" class="mt-3 inline-block text-xs font-medium text-ink/50 underline-offset-4 hover:text-ink hover:underline">Retour au site</a>
        </div>
    </div>
</x-guest-layout>
