<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $summary
 * @property string $risk_explanation
 * @property array<int, string> $key_insights
 * @property array<int, array{priority?: string, action?: string, reason?: string}> $priority_actions
 * @property string $business_impact
 * @property float|null $confidence
 * @property int|null $compliance_score
 * @property int|null $risk_score
 * @property string|null $risk_level
 * @property string|null $compliance_level
 * @property string|null $model
 * @property string|null $prompt_version
 * @property int|null $duration_ms
 * @property string $status
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class CertificationAIAnalysis extends Model
{
    use HasFactory;

    protected $table = 'certification_ai_analyses';

    protected $fillable = [
        'summary',
        'risk_explanation',
        'key_insights',
        'priority_actions',
        'business_impact',
        'confidence',
        'compliance_score',
        'risk_score',
        'risk_level',
        'compliance_level',
        'model',
        'prompt_version',
        'duration_ms',
        'status',
        'error_message',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'key_insights' => 'array',
            'priority_actions' => 'array',
            'confidence' => 'float',
            'compliance_score' => 'integer',
            'risk_score' => 'integer',
            'duration_ms' => 'integer',
        ];
    }

    public static function latestSaved(): ?self
    {
        return self::query()
            ->where('status', 'completed')
            ->latest('created_at')
            ->latest('id')
            ->first();
    }

    public static function latestAttempt(): ?self
    {
        return self::query()->latest('created_at')->latest('id')->first();
    }
}
