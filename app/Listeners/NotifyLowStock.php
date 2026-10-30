<?php

namespace App\Listeners;

use App\Events\StockLevelLow;
use App\Notifications\LowStockNotification;
use App\Support\StockAccess;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;

/** Découvert automatiquement par Laravel 12 (dossier app/Listeners). */
class NotifyLowStock
{
    public function handle(StockLevelLow $event): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        // Seulement les utilisateurs actifs qui gèrent les stocks (permission manage_stocks)
        $recipients = StockAccess::managers();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new LowStockNotification(
            siteId: $event->site->getKey(),
            siteName: $event->site->name,
            productId: $event->product->getKey(),
            productName: (string) $event->product->name,
            available: $event->available,
            threshold: $event->threshold,
        ));
    }
}
