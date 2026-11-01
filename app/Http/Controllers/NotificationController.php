<?php

namespace App\Http\Controllers;

use App\Support\NotificationPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /** Centre de notifications : filtres (source, sévérité, état) + tri par priorité + pagination. */
    public function index(Request $request): View
    {
        $query = $request->user()->notifications();

        $query->when(
            $request->filled('source') && array_key_exists($request->string('source')->value(), NotificationPresenter::sources()),
            fn ($q) => $q->where('data->source', $request->string('source')->value())
        );

        $query->when(
            $request->filled('severity') && array_key_exists($request->string('severity')->value(), NotificationPresenter::severities()),
            fn ($q) => $q->where('data->severity', $request->string('severity')->value())
        );

        $query->when($request->input('state') === 'unread', fn ($q) => $q->whereNull('read_at'));
        $query->when($request->input('state') === 'read', fn ($q) => $q->whereNotNull('read_at'));

        $sort = $request->input('sort', 'priority');

        $notifications = ($sort === 'recent'
            ? $query->latest()
            : $query->orderByDesc('data->priority')->latest())
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($notification) => NotificationPresenter::make($notification));

        return view('notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $request->user()->unreadNotifications()->count(),
            'sources' => NotificationPresenter::sources(),
            'severities' => NotificationPresenter::severities(),
            'filters' => [
                'source' => $request->input('source'),
                'severity' => $request->input('severity'),
                'state' => $request->input('state'),
                'sort' => $sort,
            ],
        ]);
    }

    /** Marque une notification comme lue (contrôle de propriété via le scope utilisateur). */
    public function markAsRead(Request $request, string $notification): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $record = $request->user()->notifications()->findOrFail($notification);
        $record->markAsRead();

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'unread' => $request->user()->unreadNotifications()->count(),
            ]);
        }

        return back();
    }

    /** Marque toutes les notifications de l'utilisateur comme lues. */
    public function markAllAsRead(Request $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'unread' => 0]);
        }

        return back()->with('success', 'Toutes les notifications ont été marquées comme lues.');
    }

    /** Compteur de non-lues (rafraîchissement de la cloche sans rechargement de page). */
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json(['unread' => $request->user()->unreadNotifications()->count()]);
    }

    /** Notifications récentes pour le menu déroulant de la cloche. */
    public function recent(Request $request): JsonResponse
    {
        $items = $request->user()->notifications()
            ->orderByDesc('data->priority')
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn ($notification) => NotificationPresenter::make($notification))
            ->values();

        return response()->json([
            'items' => $items,
            'unread' => $request->user()->unreadNotifications()->count(),
        ]);
    }
}
