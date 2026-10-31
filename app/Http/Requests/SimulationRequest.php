<?php

namespace App\Http\Requests;

use App\Models\StockSimulation;
use App\Services\Simulation\DigitalTwinSimulator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Scénario « Et si ? » du jumeau numérique. */
class SimulationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $day = ['nullable', 'integer', 'min:1', 'max:'.DigitalTwinSimulator::MAX_HORIZON];
        $site = ['nullable', 'integer', Rule::exists('sites', 'id')];

        return [
            'name' => ['nullable', 'string', 'max:120'],
            'preset' => ['required', Rule::in(array_keys(StockSimulation::PRESETS))],
            'horizon' => ['required', 'integer', 'min:'.DigitalTwinSimulator::MIN_HORIZON, 'max:'.DigitalTwinSimulator::MAX_HORIZON],

            'demand_change' => ['nullable', 'numeric', 'min:-90', 'max:200'],
            'demand_product_id' => ['nullable', 'integer', Rule::exists('products', 'id')],
            'demand_from' => $day,
            'demand_to' => [...$day, 'gte:demand_from'],

            'closed_site_id' => $site,
            'closed_from' => $day,
            'closed_days' => ['nullable', 'integer', 'min:1', 'max:'.DigitalTwinSimulator::MAX_HORIZON],

            'transport_delay' => ['nullable', 'integer', 'min:0', 'max:7'],
            'transport_from' => $day,

            'cold_site_id' => $site,
            'cold_day' => $day,
            'cold_lost_days' => ['nullable', 'integer', 'min:1', 'max:14'],
        ];
    }

    public function attributes(): array
    {
        return [
            'horizon' => 'durée simulée', 'demand_change' => 'variation de la demande', 'demand_product_id' => 'produit',
            'demand_from' => 'premier jour du pic', 'demand_to' => 'dernier jour du pic',
            'closed_site_id' => 'site fermé', 'closed_from' => 'jour de fermeture', 'closed_days' => 'durée de fermeture',
            'transport_delay' => 'retard des camions', 'transport_from' => 'début du retard',
            'cold_site_id' => 'site touché', 'cold_day' => 'jour de la panne de froid', 'cold_lost_days' => 'jours de DLC perdus',
        ];
    }

    /** Scénario au format du simulateur (les jours saisis commencent à 1, le simulateur compte à partir de 0). */
    public function scenario(): array
    {
        $v = $this->validated();
        $day = fn (?int $value, int $default) => $value !== null ? $value - 1 : $default;

        return [
            'horizon' => (int) $v['horizon'],
            'demand' => [
                'change' => (float) ($v['demand_change'] ?? 0),
                'product_id' => $v['demand_product_id'] ?? null,
                'from' => $day($v['demand_from'] ?? null, 0),
                'to' => $day($v['demand_to'] ?? null, (int) $v['horizon'] - 1),
            ],
            'closed' => [
                'site_id' => $v['closed_site_id'] ?? null,
                'from' => $day($v['closed_from'] ?? null, 1),
                'days' => (int) ($v['closed_days'] ?? 3),
            ],
            'transport' => [
                'delay' => (int) ($v['transport_delay'] ?? 0),
                'from' => $day($v['transport_from'] ?? null, 0),
            ],
            'cold_chain' => [
                'site_id' => $v['cold_site_id'] ?? null,
                'day' => $day($v['cold_day'] ?? null, 1),
                'lost_days' => (int) ($v['cold_lost_days'] ?? 3),
            ],
        ];
    }
}
