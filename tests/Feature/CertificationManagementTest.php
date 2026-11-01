<?php

use App\Models\Certification;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('allows an authenticated user to manage certifications with documents', function () {
    Storage::fake('public');
    $user = professionalUser();
    $product = Product::create([
        'name' => 'Tomate Bio',
        'category' => 'Vegetables',
        'origin_country' => 'Tunisia',
        'producer_id' => $user->id,
        'unit' => 'kg',
        'verification_status' => 'verified',
    ]);

    $response = $this->actingAs($user)->post(route('admin.certifications.store'), [
        'product_id' => $product->id,
        'name' => 'Organic Standard',
        'certificate_number' => 'CERT-TEST-001',
        'issuing_organization' => 'Test Organization',
        'issued_at' => '2026-09-10',
        'expires_at' => '2027-09-10',
        'document' => UploadedFile::fake()->create('certificate.pdf', 100, 'application/pdf'),
        'status' => 'valid',
        'notes' => 'Verified document',
    ]);

    $response->assertRedirect(route('admin.certifications.index'));
    $certification = Certification::where('certificate_number', 'CERT-TEST-001')->firstOrFail();
    expect($certification->effective_status)->toBe(Certification::STATUS_VALID);
    Storage::disk('public')->assertExists($certification->document_path);

    $this->actingAs($user)->get(route('admin.certifications.show', $certification))->assertOk();
    $this->actingAs($user)->get(route('admin.certifications.index', ['search' => 'CERT-TEST-001', 'status' => 'valid']))->assertOk();

    $this->actingAs($user)->put(route('admin.certifications.update', $certification), [
        'product_id' => $product->id,
        'name' => 'Organic Standard Updated',
        'certificate_number' => 'CERT-TEST-001-UPDATED',
        'issuing_organization' => 'Test Organization',
        'issued_at' => '2026-09-10',
        'expires_at' => now()->addDays(10)->toDateString(),
        'status' => 'valid',
        'notes' => 'Expiring soon',
    ])->assertRedirect(route('admin.certifications.index'));

    expect($certification->fresh()->effective_status)->toBe(Certification::STATUS_EXPIRING);

    $this->actingAs($user)->delete(route('admin.certifications.destroy', $certification))
        ->assertRedirect(route('admin.certifications.index'));
    $this->assertDatabaseMissing('certifications', ['id' => $certification->id]);
    Storage::disk('public')->assertMissing($certification->document_path);
});

it('validates certification dates and document types', function () {
    $user = professionalUser();
    $product = Product::create([
        'name' => 'Olives',
        'category' => 'Pantry',
        'origin_country' => 'Tunisia',
        'producer_id' => $user->id,
        'unit' => 'kg',
        'verification_status' => 'verified',
    ]);

    $this->actingAs($user)
        ->from(route('admin.certifications.create'))
        ->post(route('admin.certifications.store'), [
            'product_id' => $product->id,
            'name' => 'Unsafe file',
            'certificate_number' => 'CERT-TEST-002',
            'issuing_organization' => 'Test Organization',
            'issued_at' => '2026-09-15',
            'expires_at' => '2026-09-14',
            'document' => UploadedFile::fake()->create('certificate.exe', 10, 'application/octet-stream'),
            'status' => 'valid',
        ])
        ->assertRedirect(route('admin.certifications.create'))
        ->assertSessionHasErrors(['expires_at', 'document']);
});
