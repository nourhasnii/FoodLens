<?php

namespace App\Support;

use Illuminate\Notifications\DatabaseNotification;

/**
 * Normalise une notification Laravel (table `notifications`) en tableau prêt
 * pour l'affichage côté Blade et côté Alpine (cloche + centre de notifications).
 */
class NotificationPresenter
{
    /** Libellés français des sources (types d'alerte). */
    public const SOURCES = [
        'certification' => 'Certifications',
        'ocr' => 'OCR / Étiquettes',
        'recall' => 'Lots rappelés',
        'quality' => 'Qualité',
        'coverage' => 'Couverture certification',
        'other' => 'Autres',
    ];

    /** Libellés français des sévérités. */
    public const SEVERITIES = [
        'info' => 'Information',
        'warning' => 'Avertissement',
        'critical' => 'Critique',
    ];

    /**
     * @return array<string, mixed>
     */
    public static function make(DatabaseNotification $notification): array
    {
        $data = $notification->data ?? [];

        return [
            'id' => $notification->id,
            'title' => $data['title'] ?? 'Notification',
            'message' => $data['message'] ?? '',
            'severity' => $data['severity'] ?? 'info',
            'severity_label' => self::severities()[$data['severity'] ?? 'info'] ?? 'Information',
            'source' => $data['source'] ?? 'other',
            'source_label' => self::sources()[$data['source'] ?? 'other'] ?? 'Autres',
            'priority' => (float) ($data['priority'] ?? 0),
            'read' => $notification->read_at !== null,
            'created_at' => optional($notification->created_at)->toIso8601String(),
            'created_human' => optional($notification->created_at)->diffForHumans(),
            'action_url' => $data['action_url'] ?? null,
            'action_label' => $data['action_label'] ?? null,
        ];
    }

    /** @return array<string, string> */
    public static function sources(): array
    {
        return self::SOURCES;
    }

    /** @return array<string, string> */
    public static function severities(): array
    {
        return self::SEVERITIES;
    }
}
