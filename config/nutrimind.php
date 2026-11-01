<?php

/*
|--------------------------------------------------------------------------
| NutriMind — Moteur de décision explicable
|--------------------------------------------------------------------------
| Règles métier déterministes et configurables utilisées par
| App\Services\NutriMind\RiskRules et App\Services\NutriMind\NutriMindService.
|
| Aucune donnée inventée : chaque règle s'appuie sur une colonne réelle des
| tables products, batches, certifications, ocr_checks, quality_checks,
| transport_conditions. Le score est la somme des contributions des règles
| confirmées (« risk »), plafonnée à 100. Les informations manquantes
| (« kind » = missing) sont signalées séparément et n'entrent pas dans le score,
| afin de ne pas confondre absence de donnée et absence de risque.
*/

return [

    // Version du jeu de règles : tracée sur chaque évaluation (historique).
    'rule_set_version' => '1.0',

    /*
    | Seuils de sévérité du score global (0-100).
    | score >= critical : critique ; >= warning : avertissement ; sinon info.
    */
    'severity_thresholds' => [
        'critical' => 70,
        'warning' => 40,
    ],

    /*
    | Un événement de sécurité confirmé (ex. lot rappelé) impose une sévérité
    | critique et un plancher de score, indépendamment du cumul des autres règles.
    */
    'critical_override' => [
        'floor_score' => 70,
    ],

    /*
    | Règles de risque.
    |  - weight     : points apportés lorsque la règle se déclenche (avant modulation)
    |  - severity   : sévérité de la constatation (peut être renforcée par la règle)
    |  - kind       : 'risk' (confirmé, entre dans le score) ou 'missing' (donnée absente)
    |  - critical_override : true si l'événement est un incident de sécurité confirmé
    */
    'rules' => [

        'recall' => [
            'label' => 'Lot rappelé',
            'category' => 'recall',
            'severity' => 'critical',
            'kind' => 'risk',
            'weight' => 60.0,
            'critical_override' => true,
        ],

        'certification_expired' => [
            'label' => 'Certification expirée',
            'category' => 'certification',
            'severity' => 'critical',
            'kind' => 'risk',
            'weight' => 40.0,
        ],

        'certification_expiring' => [
            'label' => 'Certification proche de l’expiration',
            'category' => 'certification',
            'severity' => 'warning',
            'kind' => 'risk',
            'weight' => 20.0,
            'window_days' => 30,
        ],

        'ocr_inconsistency' => [
            'label' => 'Incohérence OCR',
            'category' => 'ocr',
            'severity' => 'warning',
            'kind' => 'risk',
            'weight' => 35.0,
            'threshold' => 40,
            'critical_threshold' => 70,
        ],

        'quality_failed' => [
            'label' => 'Contrôle qualité défavorable',
            'category' => 'quality',
            'severity' => 'warning',
            'kind' => 'risk',
            'weight' => 45.0,
            'threshold' => 50,
            'critical_threshold' => 30,
        ],

        'cold_chain' => [
            'label' => 'Rupture de la chaîne du froid',
            'category' => 'cold_chain',
            'severity' => 'warning',
            'kind' => 'risk',
            'weight' => 30.0,
            'min_ratio' => 0.3,
        ],

        'batch_expired' => [
            'label' => 'Lot périmé',
            'category' => 'freshness',
            'severity' => 'warning',
            'kind' => 'risk',
            'weight' => 15.0,
        ],

        // --- Informations manquantes (hors score, signalées séparément) ---

        'missing_certification' => [
            'label' => 'Aucune certification enregistrée',
            'category' => 'certification',
            'severity' => 'warning',
            'kind' => 'missing',
        ],

        'missing_quality_check' => [
            'label' => 'Aucun contrôle qualité',
            'category' => 'quality',
            'severity' => 'info',
            'kind' => 'missing',
        ],

        'missing_ocr_check' => [
            'label' => 'Aucun contrôle OCR',
            'category' => 'ocr',
            'severity' => 'info',
            'kind' => 'missing',
        ],

        'product_unverified' => [
            'label' => 'Produit non vérifié',
            'category' => 'coverage',
            'severity' => 'info',
            'kind' => 'missing',
        ],
    ],

    /*
    | Graphe d'impact (NutriMind Impact Graph).
    | Exploration bornée des relations Eloquent existantes : profondeur et nombre
    | de nœuds limités, protection contre les cycles. Aucune base de graphe.
    */
    'impact' => [
        'max_depth' => 3,
        'max_nodes' => 120,
    ],

    /*
    | Scénarios « what-if ».
    | Chaque scénario injecte une constatation hypothétique (jamais persistée en
    | tant que fait réel) en réutilisant la règle correspondante. `scope` aligne la
    | clé de portée sur celle des règles réelles pour éviter tout double comptage.
    */
    'scenarios' => [

        'certification_expired' => [
            'label' => 'Certification expirée',
            'description' => 'Une certification du produit viendrait à expirer.',
            'rule' => 'certification_expired',
            'scope' => 'product',
            'severity' => 'critical',
            'contribution' => 40.0,
            'reason' => 'Hypothèse : une certification du produit est expirée.',
        ],

        'quality_failed' => [
            'label' => 'Contrôle qualité échoué',
            'description' => 'Un contrôle qualité attribuerait 20/100 (décision de retrait).',
            'rule' => 'quality_failed',
            'scope' => 'batch',
            'severity' => 'critical',
            'contribution' => 27.0,
            'reason' => 'Hypothèse : contrôle qualité à 20/100, décision de retrait.',
        ],

        'ocr_inconsistency' => [
            'label' => 'Incohérence OCR confirmée',
            'description' => 'Une incohérence d’étiquetage serait confirmée (80/100).',
            'rule' => 'ocr_inconsistency',
            'scope' => 'batch',
            'severity' => 'warning',
            'contribution' => 28.0,
            'reason' => 'Hypothèse : score d’incohérence OCR de 80/100.',
        ],

        'cold_chain_break' => [
            'label' => 'Rupture de la chaîne du froid',
            'description' => 'La moitié des relevés sortiraient de la plage autorisée.',
            'rule' => 'cold_chain',
            'scope' => 'batch',
            'severity' => 'warning',
            'contribution' => 15.0,
            'reason' => 'Hypothèse : 50 % des relevés hors plage (2–5 °C).',
        ],

        'recall' => [
            'label' => 'Rappel de lot',
            'description' => 'Un rappel de lot serait prononcé.',
            'rule' => 'recall',
            'scope' => 'batch',
            'severity' => 'critical',
            'contribution' => 60.0,
            'reason' => 'Hypothèse : le lot ferait l’objet d’un rappel.',
        ],
    ],

    /*
    | Recommandations correctives sûres (Phase 4).
    | Chaque constatation est traduite en une recommandation métier. Le lien vers
    | un workflow existant est résolu côté service (routes réelles uniquement) :
    | si aucun workflow n'existe, seule la recommandation est affichée, sans
    | bouton inventé. `consequential` = l'action modifie un statut réglementé et
    | doit être confirmée explicitement par l'utilisateur.
    */
    'recommendations' => [

        'recall' => [
            'label' => 'Traiter le rappel du lot',
            'guidance' => 'Appliquez la procédure de rappel : identifiez les distributeurs concernés, informez-les et suivez les retours. Incident de sécurité confirmé.',
            'consequential' => true,
        ],

        'certification_expired' => [
            'label' => 'Renouveler la certification expirée',
            'guidance' => 'La conformité n’est plus démontrable. Renouvelez la certification auprès de l’organisme émetteur avant toute nouvelle distribution.',
            'consequential' => false,
        ],

        'certification_expiring' => [
            'label' => 'Anticiper le renouvellement de la certification',
            'guidance' => 'La certification arrive à échéance. Lancez le renouvellement dès maintenant pour éviter une rupture de conformité.',
            'consequential' => false,
        ],

        'missing_certification' => [
            'label' => 'Régulariser la certification manquante',
            'guidance' => 'Aucune certification active n’est enregistrée. Ajoutez la certification du produit pour rétablir la traçabilité réglementaire.',
            'consequential' => false,
        ],

        'quality_failed' => [
            'label' => 'Revue qualité avant distribution',
            'guidance' => 'Le contrôle qualité est défavorable. Analysez les facteurs, isolez le lot concerné et décidez d’une ré-inspection ou d’un retrait.',
            'consequential' => true,
        ],

        'missing_quality_check' => [
            'label' => 'Planifier un contrôle qualité',
            'guidance' => 'La conformité sanitaire reste à établir. Réalisez un contrôle qualité sur ce produit ou ce lot.',
            'consequential' => false,
        ],

        'ocr_inconsistency' => [
            'label' => 'Vérifier l’étiquetage',
            'guidance' => 'Les données extraites diffèrent des données déclarées. Contrôlez l’étiquette physique et corrigez les informations divergentes.',
            'consequential' => false,
        ],

        'missing_ocr_check' => [
            'label' => 'Lancer un contrôle OCR',
            'guidance' => 'Aucune vérification d’étiquetage n’a été réalisée. Lancez un contrôle OCR pour confirmer la cohérence des informations.',
            'consequential' => false,
        ],

        'cold_chain' => [
            'label' => 'Enquêter sur la rupture de chaîne du froid',
            'guidance' => 'Des relevés sortent de la plage autorisée. Vérifiez le transport, évaluez l’impact sur la qualité et documentez la décision.',
            'consequential' => false,
        ],

        'batch_expired' => [
            'label' => 'Retirer le lot périmé',
            'guidance' => 'Le lot a dépassé sa date limite. Retirez-le de la distribution et mettez à jour son statut.',
            'consequential' => true,
        ],

        'product_unverified' => [
            'label' => 'Soumettre le produit à vérification',
            'guidance' => 'Le produit n’est pas encore vérifié par la plateforme. Complétez le dossier de vérification.',
            'consequential' => false,
        ],
    ],
];
