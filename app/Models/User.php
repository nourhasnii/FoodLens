<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'profile_photo_path',
        'password',
        'email_verified_at',
        'account_status',
        'organization_name',
        'phone',
        'country',
        'region',
        'address',
        'professional_identifier',
        'must_change_password',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
        'information_requested_at',
        'information_requested_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'information_requested_at' => 'datetime',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Public URL of the user's profile photo, or null when none is set.
     */
    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (blank($this->profile_photo_path)) {
            return null;
        }

        if (Str::startsWith($this->profile_photo_path, ['http://', 'https://'])) {
            return $this->profile_photo_path;
        }

        return asset('storage/'.ltrim($this->profile_photo_path, '/'));
    }

    /**
     * Two-letter initials used as a fallback avatar when no photo is set.
     */
    public function getProfileInitialsAttribute(): string
    {
        $name = trim((string) $this->name);

        if ($name === '') {
            return '?';
        }

        $parts = preg_split('/\s+/', $name) ?: [];
        $initials = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        return $initials !== '' ? $initials : mb_strtoupper(mb_substr($name, 0, 1));
    }

    /**
     * Libellé français du rôle principal de l'utilisateur (affiché dans l'interface).
     */
    public function getRoleLabelAttribute(): string
    {
        $role = $this->getRoleNames()->first();

        return match ($role) {
            'consommateur' => 'Consommateur',
            'producteur' => 'Producteur',
            'distributeur' => 'Distributeur',
            'transformateur' => 'Transformateur',
            'admin' => 'Administrateur',
            default => $role ? ucfirst($role) : '—',
        };
    }

    public function verificationRequests()
    {
        return $this->hasMany(VerificationRequest::class);
    }

    public function submittedVerificationRequests()
    {
        return $this->hasMany(VerificationRequest::class, 'reviewed_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function informationRequestedBy()
    {
        return $this->belongsTo(User::class, 'information_requested_by');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'actor_id');
    }

    public function accountActivationTokens()
    {
        return $this->hasMany(AccountActivationToken::class);
    }

    public function isActive(): bool
    {
        return $this->account_status === null || $this->account_status === 'active';
    }

    /**
     * Whether two-factor authentication is enabled and confirmed for this user.
     */
    public function hasEnabledTwoFactorAuthentication(): bool
    {
        return ! is_null($this->two_factor_secret) && ! is_null($this->two_factor_confirmed_at);
    }

    /**
     * Whether a secret has been generated but not yet confirmed with an OTP.
     */
    public function hasPendingTwoFactorConfirmation(): bool
    {
        return ! is_null($this->two_factor_secret) && is_null($this->two_factor_confirmed_at);
    }

    /**
     * Generate a fresh base32 TOTP secret.
     */
    public function generateTwoFactorSecretKey(): string
    {
        return app(Google2FA::class)->generateSecretKey();
    }

    /**
     * Build the otpauth:// URL used to provision authenticator apps.
     */
    public function twoFactorQrCodeUrl(): ?string
    {
        if (is_null($this->two_factor_secret)) {
            return null;
        }

        return app(Google2FA::class)->getQRCodeUrl(
            config('app.name', 'NutriTrace'),
            $this->email,
            $this->two_factor_secret,
        );
    }

    /**
     * Verify a TOTP code against the stored secret (accepting a small time window).
     */
    public function verifyTwoFactorCode(?string $code): bool
    {
        if (is_null($this->two_factor_secret) || blank($code)) {
            return false;
        }

        return (bool) app(Google2FA::class)->verifyKey($this->two_factor_secret, (string) $code, 1);
    }

    /**
     * Generate a fresh set of one-time recovery codes.
     *
     * @return array<int, string>
     */
    public function generateTwoFactorRecoveryCodes(int $count = 8): array
    {
        return collect(range(1, $count))
            ->map(fn () => Str::random(10).'-'.Str::random(10))
            ->all();
    }

    /**
     * Verify and consume a recovery code. Returns true when the code matched.
     */
    public function consumeTwoFactorRecoveryCode(?string $code): bool
    {
        if (blank($code)) {
            return false;
        }

        $codes = $this->two_factor_recovery_codes ?? [];

        foreach ($codes as $index => $stored) {
            if (hash_equals($stored, (string) $code)) {
                unset($codes[$index]);

                $this->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

                return true;
            }
        }

        return false;
    }
}