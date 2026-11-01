<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('stores product uploads and retains or replaces the image on update', function () {
    Storage::fake('public');

    $user = professionalUser();
    $category = Category::factory()->create();
    $productData = [
        'name' => 'Huile locale',
        'description' => 'Huile produite localement.',
        'category_id' => $category->id,
        'producer_id' => $user->id,
        'unit' => 'litre',
        'carbon_footprint' => '1.25',
        'verification_status' => 'pending',
    ];

    $this->actingAs($user)
        ->post(route('admin.products.store'), $productData + [
            'image' => UploadedFile::fake()->image('huile.png'),
        ])
        ->assertRedirect(route('admin.products.index'));

    $product = Product::firstOrFail();
    $originalImage = $product->image;
    expect($originalImage)->toStartWith('products/');
    Storage::disk('public')->assertExists($originalImage);

    $this->actingAs($user)
        ->put(route('admin.products.update', $product), $productData)
        ->assertRedirect(route('admin.products.index'));

    expect($product->fresh()->image)->toBe($originalImage);
    Storage::disk('public')->assertExists($originalImage);

    $this->actingAs($user)
        ->put(route('admin.products.update', $product), $productData + [
            'image' => UploadedFile::fake()->image('huile-remplacement.png'),
        ])
        ->assertRedirect(route('admin.products.index'));

    $replacementImage = $product->fresh()->image;
    expect($replacementImage)->not->toBe($originalImage);
    Storage::disk('public')->assertExists($replacementImage);
    Storage::disk('public')->assertMissing($originalImage);

    $this->actingAs($user)->get(route('admin.products.index'))->assertOk()->assertSee(Storage::disk('public')->url($replacementImage));
    $this->actingAs($user)->get(route('admin.products.show', $product))->assertOk()->assertSee(Storage::disk('public')->url($replacementImage));

    $product->update(['image' => 'products/missing-image.png']);
    $this->actingAs($user)->get(route('admin.products.index'))
        ->assertOk()
        ->assertDontSee(Storage::disk('public')->url('products/missing-image.png'));
    $this->actingAs($user)->get(route('admin.products.show', $product))
        ->assertOk()
        ->assertSee('Aucune image disponible');
});