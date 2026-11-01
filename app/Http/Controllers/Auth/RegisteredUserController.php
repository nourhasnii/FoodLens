<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\BrevoMailService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use RuntimeException;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * @throws ValidationException
     */
    public function store(RegisterRequest $request, AuditLogService $audit, BrevoMailService $mail): RedirectResponse
    {
        $validated = $request->validated();
        $role = $validated['role'] ?? 'consommateur';

        $allowedRoles = ['consommateur', 'producteur', 'distributeur'];
        if (! in_array($role, $allowedRoles, true)) {
            throw ValidationException::withMessages(['role' => 'The selected role is invalid.']);
        }

        $isConsumer = $role === 'consommateur';

        $user = User::create([
            ...collect($validated)->except(['role'])->all(),
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
            'account_status' => $isConsumer ? 'active' : 'pending',
            'must_change_password' => ! $isConsumer,
        ]);

        $user->assignRole(Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']));

        event(new Registered($user));

        $audit->record(
            'USER_REGISTERED',
            $user,
            [],
            ['role' => $role, 'account_status' => $user->account_status],
            null,
            null,
        );

        $emailFailed = false;

        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $exception) {
            $emailFailed = true;
            Log::warning('NutriTrace verification email could not be delivered.', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);
        }

        if ($isConsumer) {
            try {
                $mail->sendWelcomeConsumerEmail($user);
            } catch (RuntimeException) {
                $emailFailed = true;
            }
        } else {
            $verificationRequest = $user->verificationRequests()->create(['status' => 'pending', 'submitted_at' => now()]);
            $audit->record(
                'VERIFICATION_SUBMITTED',
                $verificationRequest,
                [],
                ['user_id' => $user->id],
                null,
                null,
            );

            try {
                $mail->sendRegistrationReceivedEmail($user);
            } catch (RuntimeException) {
                $emailFailed = true;
            }
            try {
                $mail->sendAdminNewVerificationRequestEmail($user, $verificationRequest);
            } catch (RuntimeException) {
                $emailFailed = true;
            }
        }

        Auth::login($user);

        if ($isConsumer) {
            return redirect()->route('dashboard')->with($emailFailed ? 'error' : 'success', $emailFailed
                ? "Votre compte est prêt, mais certains e-mails n'ont pas pu être envoyés."
                : 'Votre compte est prêt. Bienvenue sur NutriTrace !');
        }

        return redirect()->route('dashboard')->with($emailFailed ? 'error' : 'success', $emailFailed
            ? "Votre inscription a bien été enregistrée, mais certains e-mails n'ont pas pu être envoyés."
            : 'Votre inscription a bien été enregistrée et sera examinée par notre équipe.');
    }
}
