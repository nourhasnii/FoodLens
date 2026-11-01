<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Décision d'un responsable sur la DLC réelle prédite d'un lot (validée ou rejetée). */
class ShelfLifeAssessment extends Model
{
    /** @use HasFactory<\Database\Factories\ShelfLifeAssessmentFactory> */
    use HasFactory;

    public const ACCEPTED = 'accepted';

    public const REJECTED = 'rejected';

    protected $fillable = [
        'batch_id', 'printed_expiry', 'predicted_expiry', 'predicted_earliest', 'predicted_latest',
        'days_lost', 'risk', 'decision', 'note', 'model', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'printed_expiry' => 'date',
            'predicted_expiry' => 'date',
            'predicted_earliest' => 'date',
            'predicted_latest' => 'date',
            'days_lost' => 'float',
            'model' => 'array',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function decisionLabel(): string
    {
        return $this->decision === self::ACCEPTED ? 'DLC réelle validée' : 'DLC imprimée conservée';
    }
}
