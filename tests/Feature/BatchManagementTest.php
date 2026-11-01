<?php

use App\Models\Batch;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

it('allows an authenticated user to manage batches', function () {
    Storage::fake('public');

    $user = professionalUser();
    $product = Product::create([
        'name' => 'Tomates bio',
        'category' => 'Légumes',
        'origin_country' => 'France',
        'producer_id' => $user->id,
        'unit' => 'kg',
        'verification_status' => 'verified',
    ]);

    $this->actingAs($user)
        ->post(route('admin.batches.store'), [
            'product_id' => $product->id,
            'lot_number' => 'LOT-TEST-001',
            'production_date' => '2026-09-15',
            'expiration_date' => '2026-10-15',
            'quantity' => '125.50',
            'unit' => 'kg',
            'carbon_footprint' => '8.25',
            'status' => 'active',
        ])
        ->assertRedirect(route('admin.batches.index'));

    $batch = Batch::where('lot_number', 'LOT-TEST-001')->firstOrFail();
    expect($batch->qr_code_path)->toBe('qrcodes/batches/' . $batch->id . '.' . (extension_loaded('imagick') ? 'png' : 'svg'));
    Storage::disk('public')->assertExists($batch->qr_code_path);

    $this->actingAs($user)->get(route('admin.batches.show', $batch))->assertOk()->assertSee('Scan to view batch traceability');
    $this->actingAs($user)->get(route('admin.batches.qr', $batch))->assertOk();
    $this->actingAs($user)->post(route('admin.batches.regenerate-qr', $batch))->assertRedirect(route('admin.batches.show', $batch));
    Storage::disk('public')->delete($batch->qr_code_path);
    $this->actingAs($user)->get(route('admin.batches.show', $batch))->assertOk()->assertSee('QR code unavailable');
    $this->actingAs($user)->get(route('admin.batches.qr', $batch))->assertRedirect(route('admin.batches.show', $batch));
    $this->actingAs($user)->post(route('admin.batches.regenerate-qr', $batch))->assertRedirect(route('admin.batches.show', $batch));
    Storage::disk('public')->assertExists($batch->qr_code_path);

    // Le passage d'un lot au statut « recalled » (rappel) est réservé aux
    // profils disposant de la permission `launch_recall` (distributeur/admin).
    $distributor = distributorUser();

    $this->actingAs($distributor)
        ->put(route('admin.batches.update', $batch), [
            'product_id' => $product->id,
            'lot_number' => 'LOT-TEST-001-UPDATED',
            'production_date' => '2026-09-15',
            'expiration_date' => '2026-10-15',
            'quantity' => '100',
            'unit' => 'kg',
            'carbon_footprint' => '7',
            'status' => 'recalled',
        ])
        ->assertRedirect(route('admin.batches.index'));

    expect($batch->fresh()->status)->toBe('recalled');
    Storage::disk('public')->assertExists($batch->fresh()->qr_code_path);

    $this->actingAs($user)
        ->delete(route('admin.batches.destroy', $batch))
        ->assertRedirect(route('admin.batches.index'));

    $this->assertDatabaseMissing('batches', ['id' => $batch->id]);
});

it('validates expiration after production and positive quantity', function () {
    $user = professionalUser();
    $product = Product::create([
        'name' => 'Pommes',
        'category' => 'Fruits',
        'origin_country' => 'France',
        'producer_id' => $user->id,
        'unit' => 'kg',
        'verification_status' => 'verified',
    ]);

    $this->actingAs($user)
        ->from(route('admin.batches.create'))
        ->post(route('admin.batches.store'), [
            'product_id' => $product->id,
            'lot_number' => 'LOT-TEST-002',
            'production_date' => '2026-09-15',
            'expiration_date' => '2026-09-14',
            'quantity' => '0',
            'unit' => 'kg',
            'status' => 'active',
        ])
        ->assertRedirect(route('admin.batches.create'))
        ->assertSessionHasErrors(['expiration_date', 'quantity']);
});

it('returns French validation errors for invalid batch create and update submissions', function () {
    Storage::fake('public');

    $user = professionalUser();
    $product = Product::create([
        'name' => 'Carottes bio',
        'category' => 'Légumes',
        'origin_country' => 'Tunisie',
        'producer_id' => $user->id,
        'unit' => 'kg',
        'verification_status' => 'verified',
    ]);
    $batch = Batch::create([
        'product_id' => $product->id,
        'lot_number' => 'CAR-TEST-001',
        'production_date' => '2026-09-10',
        'expiration_date' => '2026-10-10',
        'quantity' => 100,
        'unit' => 'kg',
        'status' => 'active',
    ]);
    $duplicate = Batch::create([
        'product_id' => $product->id,
        'lot_number' => 'CAR-TEST-002',
        'production_date' => '2026-09-10',
        'expiration_date' => '2026-10-10',
        'quantity' => 100,
        'unit' => 'kg',
        'status' => 'active',
    ]);

    $invalidData = [
        'product_id' => '',
        'lot_number' => $duplicate->lot_number,
        'production_date' => now()->addDay()->toDateString(),
        'expiration_date' => now()->addDay()->toDateString(),
        'quantity' => 0,
        'unit' => str_repeat('u', 21),
        'carbon_footprint' => 100000,
        'status' => 'unknown',
    ];

    $this->actingAs($user)
        ->from(route('admin.batches.create'))
        ->post(route('admin.batches.store'), $invalidData)
        ->assertRedirect(route('admin.batches.create'))
        ->assertSessionHasErrors([
            'product_id', 'lot_number', 'production_date', 'expiration_date', 'quantity', 'unit', 'carbon_footprint', 'status',
        ]);

    expect(session('errors')->first('product_id'))->toBe('Veuillez sélectionner un produit.')
        ->and(session('errors')->first('lot_number'))->toBe('Ce numéro de lot existe déjà.')
        ->and(session('errors')->first('production_date'))->toBe('La date de production ne peut pas être dans le futur.')
        ->and(session('errors')->first('expiration_date'))->toBe("La date d'expiration doit être postérieure à la date de production.")
        ->and(session('errors')->first('quantity'))->toBe('La quantité doit être supérieure à 0.');

    $this->actingAs($user)
        ->from(route('admin.batches.edit', $batch))
        ->put(route('admin.batches.update', $batch), array_merge($invalidData, ['product_id' => $product->id]))
        ->assertRedirect(route('admin.batches.edit', $batch))
        ->assertSessionHasErrors(['lot_number', 'expiration_date', 'quantity']);
});
