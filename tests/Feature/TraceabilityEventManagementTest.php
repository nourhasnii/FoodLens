<?php

use App\Models\Batch;
use App\Models\Product;
use App\Models\TraceabilityEvent;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

it('manages traceability events and renders a chronological timeline', function () {
    $actor = professionalUser();
    $product = Product::create([
        'name' => 'Tomate Bio',
        'category' => 'Vegetables',
        'origin_country' => 'Tunisia',
        'producer_id' => $actor->id,
        'unit' => 'kg',
        'verification_status' => 'verified',
    ]);
    $batch = Batch::create([
        'product_id' => $product->id,
        'lot_number' => 'TOM-TEST-001',
        'production_date' => '2026-09-10',
        'expiration_date' => '2026-10-10',
        'quantity' => 500,
        'unit' => 'kg',
        'status' => 'active',
    ]);

    $this->actingAs($actor)->post(route('admin.events.store'), [
        'batch_id' => $batch->id,
        'event_type' => 'production',
        'event_date' => '2026-09-10 08:00',
        'location' => 'Nabeul, Tunisia',
        'actor_id' => $actor->id,
        'quantity' => 500,
        'description' => 'Harvest recorded',
    ])->assertRedirect(route('admin.batches.traceability', $batch));

    $event = TraceabilityEvent::firstOrFail();

    $this->actingAs($actor)->post(route('admin.events.store'), [
        'batch_id' => $batch->id,
        'event_type' => 'transport',
        'event_date' => '2026-09-12 12:00',
        'location' => 'Nabeul to Tunis',
        'actor_id' => $actor->id,
        'temperature' => 6,
        'distance_km' => 120,
        'carbon_emission' => 0.34,
    ])->assertRedirect(route('admin.batches.traceability', $batch));

    $this->actingAs($actor)->get(route('admin.batches.traceability', $batch))
        ->assertOk()
        ->assertSee('Traceability Timeline')
        ->assertSee('Harvest recorded')
        ->assertSee('Nabeul to Tunis');

    $this->actingAs($actor)->get(route('admin.events.index', ['event_type' => 'transport']))->assertOk();
    $this->actingAs($actor)->get(route('admin.events.show', $event))->assertOk();

    $this->actingAs($actor)->put(route('admin.events.update', $event), [
        'batch_id' => $batch->id,
        'event_type' => 'processing',
        'event_date' => '2026-09-11 09:00',
        'location' => 'Processing Center',
        'actor_id' => $actor->id,
        'quantity' => 480,
        'description' => 'Processed and packed',
    ])->assertRedirect(route('admin.batches.traceability', $batch));

    expect($event->fresh()->event_type)->toBe('processing');

    $this->actingAs($actor)->delete(route('admin.events.destroy', $event))
        ->assertRedirect(route('admin.events.index'));
    $this->assertDatabaseMissing('traceability_events', ['id' => $event->id]);
});

it('validates event coordinates, type and quantities', function () {
    $user = professionalUser();
    $product = Product::create([
        'name' => 'Olives',
        'category' => 'Pantry',
        'origin_country' => 'Tunisia',
        'producer_id' => $user->id,
        'unit' => 'kg',
        'verification_status' => 'verified',
    ]);
    $batch = Batch::create([
        'product_id' => $product->id,
        'lot_number' => 'OLV-TEST-001',
        'production_date' => '2026-09-10',
        'expiration_date' => '2026-10-10',
        'quantity' => 100,
        'unit' => 'kg',
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->from(route('admin.events.create'))
        ->post(route('admin.events.store'), [
            'batch_id' => $batch->id,
            'event_type' => 'unknown',
            'event_date' => '2026-09-10 08:00',
            'location' => 'Nabeul',
            'latitude' => 100,
            'longitude' => 200,
            'quantity' => -1,
        ])
        ->assertRedirect(route('admin.events.create'))
        ->assertSessionHasErrors(['event_type', 'latitude', 'longitude', 'quantity']);
});

it('returns French validation errors for invalid event create and update submissions', function () {
    Storage::fake('public');

    $user = professionalUser();
    $product = Product::create([
        'name' => 'Oranges',
        'category' => 'Fruits',
        'origin_country' => 'Tunisie',
        'producer_id' => $user->id,
        'unit' => 'kg',
        'verification_status' => 'verified',
    ]);
    $batch = Batch::create([
        'product_id' => $product->id,
        'lot_number' => 'ORA-TEST-001',
        'production_date' => '2026-09-10',
        'expiration_date' => '2026-10-10',
        'quantity' => 100,
        'unit' => 'kg',
        'status' => 'active',
    ]);
    $event = TraceabilityEvent::create([
        'batch_id' => $batch->id,
        'event_type' => 'production',
        'event_date' => '2026-09-10 08:00',
        'location' => 'Nabeul, Tunisia',
    ]);
    $invalidData = [
        'batch_id' => '',
        'event_type' => '',
        'event_date' => 'not-a-date',
        'location' => 'AB',
        'actor_id' => 999999,
        'latitude' => 91,
        'longitude' => -181,
        'quantity' => -1,
        'description' => 'Court',
    ];

    $this->actingAs($user)
        ->from(route('admin.events.create'))
        ->post(route('admin.events.store'), $invalidData)
        ->assertRedirect(route('admin.events.create'))
        ->assertSessionHasErrors([
            'batch_id', 'event_type', 'event_date', 'location', 'actor_id', 'latitude', 'longitude', 'quantity', 'description',
        ]);

    expect(session('errors')->first('batch_id'))->toBe('Veuillez sélectionner un lot.')
        ->and(session('errors')->first('event_type'))->toBe('Veuillez sélectionner un type d’événement.')
        ->and(session('errors')->first('location'))->toBe('Le lieu doit contenir au moins 3 caractères.')
        ->and(session('errors')->first('description'))->toBe('La description doit contenir au moins 10 caractères.');

    $this->actingAs($user)
        ->from(route('admin.events.create'))
        ->post(route('admin.events.store'), [
            'batch_id' => $batch->id,
            'event_type' => 'production',
            'event_date' => '2026-09-10 08:00',
            'location' => 'Nabeul',
            'quantity' => 16,
        ])
        ->assertRedirect(route('admin.events.create'))
        ->assertSessionHasErrors(['quantity'])
        ->assertSessionDoesntHaveErrors('location')
        ->assertSessionHas('_old_input.location', 'Nabeul');

    expect(session('errors')->first('quantity'))->toBe('La quantité ne peut pas dépasser 15.');

    $this->actingAs($user)
        ->from(route('admin.events.create'))
        ->post(route('admin.events.store'), [
            'batch_id' => $batch->id,
            'event_type' => 'production',
            'event_date' => now()->addDays(7)->format('Y-m-d\\TH:i'),
            'location' => 'Nabeul',
        ])
        ->assertRedirect(route('admin.events.create'))
        ->assertSessionHasErrors(['event_date']);

    expect(session('errors')->first('event_date'))->toBe('La date de l’événement ne peut pas être dans le futur.');

    $this->actingAs($user)
        ->from(route('admin.events.edit', $event))
        ->put(route('admin.events.update', $event), $invalidData)
        ->assertRedirect(route('admin.events.edit', $event))
        ->assertSessionHasErrors(['batch_id', 'event_type', 'event_date', 'location', 'description']);
});
