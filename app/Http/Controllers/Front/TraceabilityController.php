<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TraceabilityController extends Controller
{
    /**
     * Public lot search. Shows the timeline for the requested lot,
     * or a friendly empty state with a featured example.
     */
    public function index(Request $request): View
    {
        $lot = trim((string) $request->query('lot', ''));

        $batch = $lot !== '' ? $this->findByLot($lot) : null;

        return view('front.traceability.show', [
            'batch' => $batch,
            'searchedLot' => $lot,
            'featured' => $batch === null ? $this->featuredBatch() : null,
        ]);
    }

    /**
     * Public traceability view for a lot number (deep link).
     */
    public function show(Batch $batch): View
    {
        $batch->load(['product', 'events' => fn ($query) => $query->orderBy('event_date')]);

        return view('front.traceability.show', [
            'batch' => $batch,
            'searchedLot' => $batch->lot_number,
            'featured' => null,
        ]);
    }

    private function findByLot(string $lot): ?Batch
    {
        return Batch::query()
            ->with(['product', 'events' => fn ($query) => $query->orderBy('event_date')])
            ->whereRaw('LOWER(lot_number) = ?', [mb_strtolower($lot)])
            ->first();
    }

    private function featuredBatch(): ?Batch
    {
        return Batch::query()
            ->with(['product', 'events' => fn ($query) => $query->orderBy('event_date')])
            ->has('events')
            ->latest()
            ->first();
    }
}
