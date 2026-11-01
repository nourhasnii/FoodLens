<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

it('renders the focused dashboard with empty database data', function () {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Quick Access')
        ->assertSee('Recent Activity')
        ->assertSee('Products')
        ->assertSee('Alerts / risks');
});

it('sends consumers to the public catalogue instead of the internal dashboard', function () {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create();
    Role::firstOrCreate(['name' => 'consommateur', 'guard_name' => 'web']);
    $user->assignRole('consommateur');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('front.products.index'));
});
