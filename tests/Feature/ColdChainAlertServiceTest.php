<?php

use App\Models\Batch;
use App\Models\TransportCondition;
use App\Services\ColdChainAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('classifies cold-chain temperature conditions and creates the expected alerts', function () {
    $batch = Batch::factory()->create();
    $service = app(ColdChainAlertService::class);

    $criticalCondition = TransportCondition::factory()->for($batch)->create([
        'temperature' => 12,
        'duration_minutes' => 0,
    ]);
    $criticalAlert = $service->syncAlert($criticalCondition);

    expect($criticalAlert?->severity)->toBe('critical');

    $warningCondition = TransportCondition::factory()->for($batch)->create([
        'temperature' => 7,
        'duration_minutes' => 0,
    ]);
    $warningAlert = $service->syncAlert($warningCondition);

    expect($warningAlert?->severity)->toBe('warning');

    $safeCondition = TransportCondition::factory()->for($batch)->create([
        'temperature' => 4,
        'duration_minutes' => 0,
    ]);

    expect($service->syncAlert($safeCondition))->toBeNull();
});
