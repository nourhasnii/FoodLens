<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certification extends Model
{
    use HasFactory;
    public const STATUS_VALID = 'valid';
    public const STATUS_EXPIRING = 'expiring';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_PENDING = 'pending';

    public const STATUSES = [
        self::STATUS_VALID,
        self::STATUS_EXPIRING,
        self::STATUS_EXPIRED,
        self::STATUS_PENDING,
    ];

    protected $fillable = [
        'product_id',
        'name',
        'certificate_number',
        'issuing_organization',
        'issued_at',
        'expires_at',
        'document_path',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'expires_at' => 'date',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getEffectiveStatusAttribute(): string
    {
        return self::calculateStatus($this->expires_at, $this->status);
    }

    public static function calculateStatus(CarbonInterface|string|null $expiresAt, ?string $status): string
    {
        if ($status === self::STATUS_PENDING || ! $expiresAt) {
            return self::STATUS_PENDING;
        }

        $expirationDate = ($expiresAt instanceof CarbonInterface ? $expiresAt : Carbon::parse($expiresAt))
            ->toDateString();
        $today = today()->toDateString();

        if ($expirationDate < $today) {
            return self::STATUS_EXPIRED;
        }

        return $expirationDate <= today()->addDays(30)->toDateString()
            ? self::STATUS_EXPIRING
            : self::STATUS_VALID;
    }

    public function scopeWithEffectiveStatus($query, string $status)
    {
        return match ($status) {
            self::STATUS_PENDING => $query->where('status', self::STATUS_PENDING),
            self::STATUS_EXPIRED => $query->where('status', '!=', self::STATUS_PENDING)->whereDate('expires_at', '<', today()),
            self::STATUS_EXPIRING => $query->where('status', '!=', self::STATUS_PENDING)->whereBetween('expires_at', [today(), now()->addDays(30)->toDateString()]),
            self::STATUS_VALID => $query->where('status', '!=', self::STATUS_PENDING)->whereDate('expires_at', '>', now()->addDays(30)->toDateString()),
            default => $query->whereRaw('1 = 0'),
        };
    }
}