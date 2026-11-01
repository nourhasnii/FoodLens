<?php

use App\Models\Batch;
use App\Models\Category;
use App\Models\Product;

/**
 * Vérifie les règles métier de la gestion des rôles :
 * - `consommateur` : aucun accès au back-office professionnel.
 * - `producteur` : accès limité à ses propres produits et lots.
 * - `distributeur` : périmètre élargi (tous les produits/lots, stocks, rappels).
 */

function roleTestCategory(): Category
{
    return Category::firstOrCreate(
        ['name' => 'Test Rôles'],
        ['is_active' => true],
    );
}

/**
 * Crée un produit rattaché à un producteur donné.
 */
function roleTestProduct(\App\Models\User $producer, string $name): Product
{
    return Product::create([
        'name' => $name,
        'category' => 'Test Rôles',
        'category_id' => roleTestCategory()->id,
        'origin_country' => 'France',
        'producer_id' => $producer->id,
        'unit' => 'kg',
        'verification_status' => 'verified',
    ]);
}

/**
 * Crée un lot rattaché à un produit donné.
 */
function roleTestBatch(Product $product, string $lotNumber): Batch
{
    return Batch::create([
        'product_id' => $product->id,
        'lot_number' => $lotNumber,
        'production_date' => '2026-09-10',
        'expiration_date' => '2026-10-10',
        'quantity' => 100,
        'unit' => 'kg',
        'status' => 'active',
    ]);
}

it('redirects consumers away from every professional area', function () {
    $consumer = consumerUser();

    $this->actingAs($consumer)->get(route('admin.products.index'))->assertRedirect(route('front.products.index'));
    $this->actingAs($consumer)->get(route('admin.batches.index'))->assertRedirect(route('front.products.index'));
    $this->actingAs($consumer)->get(route('admin.nutrimind.index'))->assertRedirect(route('front.products.index'));
    $this->actingAs($consumer)->get(route('stocks.index'))->assertRedirect(route('front.products.index'));
    $this->actingAs($consumer)->get(route('dashboard'))->assertRedirect(route('front.products.index'));
});

it('scopes the product list to the producer own products', function () {
    $producerA = professionalUser();
    $producerB = professionalUser();

    $ownProduct = roleTestProduct($producerA, 'Produit Producteur A');
    $otherProduct = roleTestProduct($producerB, 'Produit Producteur B');

    $this->actingAs($producerA)->get(route('admin.products.index'))
        ->assertOk()
        ->assertSee('Produit Producteur A')
        ->assertDontSee('Produit Producteur B');

    $this->actingAs($producerA)->get(route('admin.products.show', $ownProduct))->assertOk();
    $this->actingAs($producerA)->get(route('admin.products.show', $otherProduct))->assertForbidden();
    $this->actingAs($producerA)->put(route('admin.products.update', $otherProduct), [
        'name' => 'Modification Interdite',
        'description' => 'Tentative de modification du produit d’un autre producteur.',
        'category_id' => roleTestCategory()->id,
        'producer_id' => $producerB->id,
        'unit' => 'kg',
        'origin_country' => 'France',
        'carbon_footprint' => '2.5',
        'verification_status' => 'verified',
    ])->assertForbidden();
    $this->actingAs($producerA)->delete(route('admin.products.destroy', $otherProduct))->assertForbidden();
});

it('forces a producer to own the products they create', function () {
    $producer = professionalUser();
    $otherProducer = professionalUser();

    $this->actingAs($producer)->post(route('admin.products.store'), [
        'name' => 'Produit Usurpé',
        'description' => 'Tentative de création pour un autre producteur.',
        'category_id' => roleTestCategory()->id,
        'producer_id' => $otherProducer->id,
        'unit' => 'kg',
        'origin_country' => 'France',
        'carbon_footprint' => '1.5',
        'verification_status' => 'pending',
    ])->assertRedirect(route('admin.products.index'));

    $product = Product::where('name', 'Produit Usurpé')->firstOrFail();
    expect($product->producer_id)->toBe($producer->id);
});

it('scopes the batch list to the producer own products', function () {
    $producerA = professionalUser();
    $producerB = professionalUser();

    $productA = roleTestProduct($producerA, 'Produit A Lots');
    $productB = roleTestProduct($producerB, 'Produit B Lots');

    $ownBatch = roleTestBatch($productA, 'LOT-A-001');
    $otherBatch = roleTestBatch($productB, 'LOT-B-001');

    $this->actingAs($producerA)->get(route('admin.batches.index'))
        ->assertOk()
        ->assertSee('LOT-A-001')
        ->assertDontSee('LOT-B-001');

    $this->actingAs($producerA)->get(route('admin.batches.show', $ownBatch))->assertOk();
    $this->actingAs($producerA)->get(route('admin.batches.show', $otherBatch))->assertForbidden();
    $this->actingAs($producerA)->delete(route('admin.batches.destroy', $otherBatch))->assertForbidden();
});

it('prevents a producer from creating a batch for another producer product', function () {
    $producerA = professionalUser();
    $producerB = professionalUser();
    $productB = roleTestProduct($producerB, 'Produit Distant');

    $this->actingAs($producerA)->post(route('admin.batches.store'), [
        'product_id' => $productB->id,
        'lot_number' => 'LOT-INTERDIT-001',
        'production_date' => '2026-09-10',
        'expiration_date' => '2026-10-10',
        'quantity' => '10',
        'unit' => 'kg',
        'status' => 'active',
    ])->assertForbidden();

    $this->assertDatabaseMissing('batches', ['lot_number' => 'LOT-INTERDIT-001']);
});

it('reserves the recall status to users allowed to launch recalls', function () {
    $producer = professionalUser();
    $product = roleTestProduct($producer, 'Produit Rappel');
    $batch = roleTestBatch($product, 'LOT-RAPPEL-001');

    $payload = [
        'product_id' => $product->id,
        'lot_number' => 'LOT-RAPPEL-001',
        'production_date' => '2026-09-10',
        'expiration_date' => '2026-10-10',
        'quantity' => '100',
        'unit' => 'kg',
        'status' => 'recalled',
    ];

    // Un producteur (sans `launch_recall`) ne peut pas déclencher un rappel.
    $this->actingAs($producer)->put(route('admin.batches.update', $batch), $payload)->assertForbidden();
    expect($batch->fresh()->status)->toBe('active');

    // Un distributeur (`launch_recall`) le peut, y compris sur le lot d'un tiers.
    $this->actingAs(distributorUser())->put(route('admin.batches.update', $batch), $payload)
        ->assertRedirect(route('admin.batches.index'));
    expect($batch->fresh()->status)->toBe('recalled');
});

it('restricts stock management to roles holding the manage_stocks permission', function () {
    $producer = professionalUser();
    $distributor = distributorUser();

    $this->actingAs($producer)->get(route('stocks.index'))->assertForbidden();
    $this->actingAs($distributor)->get(route('stocks.index'))->assertOk();
});

it('scopes NutriMind to the producer own entities and opens it to distributors', function () {
    $producerA = professionalUser();
    $producerB = professionalUser();

    $productA = roleTestProduct($producerA, 'Produit NutriMind A');
    $productB = roleTestProduct($producerB, 'Produit NutriMind B');

    $this->actingAs($producerA)->get(route('admin.nutrimind.show', ['product', $productA->id]))->assertOk();
    $this->actingAs($producerA)->get(route('admin.nutrimind.show', ['product', $productB->id]))->assertForbidden();
    $this->actingAs($producerA)->post(route('admin.nutrimind.assess', ['product', $productB->id]))->assertForbidden();

    $this->actingAs(distributorUser())->get(route('admin.nutrimind.show', ['product', $productB->id]))->assertOk();
});

it('lets a distributor manage every product regardless of the producer', function () {
    $producer = professionalUser();
    $distributor = distributorUser();
    $product = roleTestProduct($producer, 'Produit Distribuable');

    $this->actingAs($distributor)->get(route('admin.products.index'))->assertOk()->assertSee('Produit Distribuable');
    $this->actingAs($distributor)->get(route('admin.products.show', $product))->assertOk();
    $this->actingAs($distributor)->get(route('admin.products.edit', $product))->assertOk();
});

it('treats the transformateur role like a producer (own scope only)', function () {
    $transformateur = transformateurUser();
    $producer = professionalUser();

    $ownProduct = roleTestProduct($transformateur, 'Produit Transformateur');
    $otherProduct = roleTestProduct($producer, 'Produit Autre Producteur');

    $this->actingAs($transformateur)->get(route('admin.products.index'))
        ->assertOk()
        ->assertSee('Produit Transformateur')
        ->assertDontSee('Produit Autre Producteur');

    $this->actingAs($transformateur)->get(route('admin.products.show', $otherProduct))->assertForbidden();

    // Pas de permission de gestion des stocks ni de rappel global.
    $this->actingAs($transformateur)->get(route('stocks.index'))->assertForbidden();
});

it('scopes the dashboard data to the producer own products and batches', function () {
    $producerA = professionalUser();
    $producerB = professionalUser();

    $productA = roleTestProduct($producerA, 'Produit Dashboard A');
    $productB = roleTestProduct($producerB, 'Produit Dashboard B');

    roleTestBatch($productA, 'LOT-DASH-A');
    roleTestBatch($productB, 'LOT-DASH-B');

    $this->actingAs($producerA)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('LOT-DASH-A')
        ->assertDontSee('LOT-DASH-B');
});

it('gives administrators the full scope and the account management area', function () {
    $admin = adminUser();
    $producer = professionalUser();

    roleTestProduct($producer, 'Produit Visible Admin');

    $this->actingAs($admin)->get(route('admin.products.index'))
        ->assertOk()
        ->assertSee('Produit Visible Admin');

    $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();

    // Un producteur n'accède pas à la gestion des comptes (contrôle serveur).
    $this->actingAs($producer)->get(route('admin.users.index'))->assertForbidden();
});
