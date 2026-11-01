<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Prévision de la demande (calendrier tunisien)
    |--------------------------------------------------------------------------
    | La demande n'est plus une simple moyenne : lissage exponentiel (niveau + tendance amortie),
    | effet du jour de la semaine appris sur l'historique, et effets des périodes spéciales
    | (Ramadan, Aïd, rentrée, été sur la côte). Utilisée par le moteur et le plan anti-gaspillage.
    */
    'forecast' => [
        'enabled' => env('STOCK_FORECAST', true),  // false : retour à la moyenne simple sur 30 jours
        'history_days' => 56,          // historique analysé (8 semaines)
        'horizon_days' => 14,          // horizon affiché par défaut
        'alpha' => 0.3,                // lissage du niveau (réactivité aux changements récents)
        'beta' => 0.1,                 // lissage de la tendance
        'damping' => 0.9,              // amortissement de la tendance : elle s'atténue avec le temps
        'outlier_mad' => 4.0,          // vente exceptionnelle si > médiane + 4 × écart absolu médian
        'max_multiplier' => 2.0,       // effet cumulé maximal des événements

        // Sorties qui ne sont pas des ventes (motif contenant ces mots) : exclues de l'historique
        'excluded_reasons' => ['don solidaire', 'destruction', 'casse', 'perte', 'périm', 'perim'],

        // Familles de produits reconnues par leur nom (pour appliquer des effets spécifiques)
        'categories' => [
            'laitier' => ['lait', 'laitier', 'yaourt', 'yogourt', 'lben', 'leben', 'raïb', 'raib', 'fromage', 'beurre', 'crème', 'creme'],
        ],

        // Villes balnéaires concernées par la saison estivale (comparaison sans accents ni majuscules)
        'coastal_cities' => ['sousse', 'monastir', 'nabeul', 'hammamet', 'mahdia', 'djerba', 'houmt souk', 'zarzis',
            'bizerte', 'tabarka', 'kelibia', 'la marsa', 'gammarth', 'port el kantaoui'],

        /*
         * Événements : périodes datées ('periods') ou récurrentes chaque année ('recurring', MM-JJ).
         * Multiplicateurs par famille ('*' = tous les produits). Ce sont des hypothèses métier modifiables.
         * Les dates des fêtes religieuses suivent le calendrier lunaire : estimées, à confirmer chaque année.
         */
        'events' => [
            ['label' => 'Ramadan', 'periods' => [['2026-02-18', '2026-03-19'], ['2027-02-08', '2027-03-09']],
                'multipliers' => ['laitier' => 1.35, '*' => 1.15]],
            ['label' => 'Préparatifs de l’Aïd el-Fitr', 'periods' => [['2026-03-17', '2026-03-19'], ['2027-03-06', '2027-03-09']],
                'multipliers' => ['*' => 1.25]],
            ['label' => 'Aïd el-Fitr', 'periods' => [['2026-03-20', '2026-03-21'], ['2027-03-10', '2027-03-11']],
                'multipliers' => ['*' => 0.7]],
            ['label' => 'Préparatifs de l’Aïd el-Adha', 'periods' => [['2026-05-24', '2026-05-26'], ['2027-05-13', '2027-05-15']],
                'multipliers' => ['*' => 1.2]],
            ['label' => 'Aïd el-Adha', 'periods' => [['2026-05-27', '2026-05-28'], ['2027-05-16', '2027-05-17']],
                'multipliers' => ['*' => 0.7]],
            ['label' => 'Rentrée scolaire', 'recurring' => ['09-15', '09-21'],
                'multipliers' => ['laitier' => 1.15, '*' => 1.05]],
            ['label' => 'Saison estivale (villes balnéaires)', 'recurring' => ['07-01', '08-31'], 'coastal_only' => true,
                'multipliers' => ['*' => 1.25]],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Plan anti-gaspillage
    |--------------------------------------------------------------------------
    | Quand un lot ne peut pas être vendu avant sa DLC par un simple transfert, le moteur
    | propose un plan en 3 niveaux : transferts partiels, promotion calculée, dons.
    */
    'anti_waste' => [
        // Élasticité-prix des ventes : 2,0 = une baisse de prix de 10 % augmente les ventes de 20 %
        // (ordre de grandeur courant pour les produits frais en promotion)
        'price_elasticity' => 2.0,
        // Remise maximale : au-delà, la vente se fait à perte et un don est préférable (%)
        'max_discount' => 30,
        'discount_step' => 10,         // la remise est proposée par paliers (%)
        // En dessous de cette quantité, un transfert n'est pas rentable : elle part plutôt en don (u.)
        'min_transfer_quantity' => 20,
        // Au moins ce temps de vente avant la DLC pour qu'un transfert en magasin soit proposé (heures)
        'min_selling_hours' => 12,
        // Une association doit recevoir le don au moins ce délai avant la DLC pour le redistribuer (heures)
        'donation_min_hours_before_expiry' => 6,
        // Convention des banques alimentaires : environ 0,5 kg de nourriture par repas
        'kg_per_meal' => 0.5,
        // Émissions évitées par kg de nourriture non gaspillée (moyenne FAO, kg CO₂e/kg)
        'co2_per_kg_food' => 2.5,
    ],

    /*
    | Fond de carte (sans clé d'API). Autre possibilité :
    | 'https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png'
    */
    'map' => [
        'tiles' => env('STOCK_MAP_TILES', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'),
        'attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    ],

    /*
    |--------------------------------------------------------------------------
    | Intégration avec le module Lots & Traçabilité (Ghassen)
    |--------------------------------------------------------------------------
    | Noms des colonnes de la table `batches`. Adaptez-les si le module Lots
    | utilise d'autres noms (ex: 'dlc' au lieu de 'expiry_date').
    */
    'batch' => [
        'code_column' => env('STOCK_BATCH_CODE_COLUMN', 'lot_number'),
        'expiry_column' => env('STOCK_BATCH_EXPIRY_COLUMN', 'expiration_date'),
        'status_column' => env('STOCK_BATCH_STATUS_COLUMN', 'status'),
        // Un lot dans l'un de ces statuts ne peut ni entrer en stock ni être transféré
        // (statuts des lots FoodLens : active, expired, recalled)
        'blocked_statuses' => ['recalled', 'expired'],
    ],

    'product' => [
        // Colonne de poids unitaire (kg) dans `products` si elle existe, sinon null
        'unit_weight_column' => env('STOCK_PRODUCT_WEIGHT_COLUMN'),
        'default_unit_weight_kg' => 1.0,
    ],

    // Une ligne est signalée "DLC proche" en dessous de ce nombre de jours
    'expiring_soon_days' => 7,

    'alerts' => [
        // Notification par e-mail en plus de la notification en base
        'mail' => env('STOCK_ALERT_MAIL', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Optimization Engine
    |--------------------------------------------------------------------------
    */
    'optimization' => [
        // Poids des critères (renormalisés automatiquement à 1)
        'weights' => [
            'distance' => 0.20,
            'capacity' => 0.15,
            'expiry' => 0.25,
            'co2' => 0.15,
            'demand' => 0.25,
        ],

        // Lot "urgent" : DLC dans N jours ou moins → poids DLC et distance renforcés
        'urgent_expiry_days' => 3,
        'urgency_multiplier' => 1.5,

        // Transport routier frigorifique (kg CO2e par tonne.km)
        'emission_factor_kg_per_tkm' => 0.115,
        // Distance à vol d'oiseau × facteur ≈ distance routière
        'road_factor' => 1.25,
        'average_speed_kmh' => 60,
        // Au-delà, le score de distance vaut 0
        'max_distance_km' => 600,

        // Fenêtre d'historique pour estimer la consommation moyenne
        'demand_window_days' => 30,
        // Horizon de besoin si le lot n'a pas de DLC
        'default_horizon_days' => 14,
        'max_horizon_days' => 30,

        // Part de capacité libre après réception à partir de laquelle le score capacité vaut 100
        'comfortable_free_ratio' => 0.5,

        // En dessous de ce score, la page de recommandation affiche un avertissement
        // (destination peu satisfaisante : réduire la quantité, promotion ou don)
        'low_score_threshold' => 60,
    ],
];
