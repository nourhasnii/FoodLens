# NutriTrace AI — Module Stocks & Optimization Engine

Responsable : **Triki Amine** · Laravel 12 · PHP 8.2+ · intégré au projet FoodLens

Ce module gère les stocks multi-sites (entrepôts, magasins, sites de production), l'historique des mouvements, les alertes de rupture et un moteur d'optimisation explicable qui recommande la meilleure destination d'un lot selon la distance, la capacité, la DLC, l'impact CO₂ et la demande.

---

## 1. Contenu du livrable

```
app/
├── Console/Commands/RecalculateStocks.php      php artisan stocks:recalculate [--fix]
├── Enums/                                      SiteType, StockMovementType, RecommendationStatus
├── Events/                                     StockLevelLow, BatchDestinationValidated
├── Http/Controllers/                           SiteController, StockController,
│                                               StockMovementController, OptimizationController
├── Http/Requests/                              6 Form Requests (validation en français)
├── Listeners/NotifyLowStock.php                découvert automatiquement par Laravel 12
├── Models/                                     Site, Stock, StockMovement, OptimizationRecommendation
│   └── Concerns/HasStock.php                   trait à ajouter à Product et Batch
├── Notifications/LowStockNotification.php      database (+ mail optionnel)
├── Services/Stock/StockService.php             toute la logique des mouvements
├── Services/Optimization/OptimizationEngine.php  le moteur de recommandation
└── Support/                                    Geo (haversine), Fmt (format FR), BatchAttributes
config/stock.php                                paramètres métier + pondérations IA
database/migrations/                            sites, stocks, stock_movements, optimization_recommendations
database/seeders/                               SiteSeeder (8 sites tunisiens), StockDemoSeeder
resources/views/                                layouts/stock, stocks/*, sites/*, stock-movements/*, optimization/*
routes/stock.php                                toutes les routes du module
```

Les vues sont autonomes (layout `layouts/stock` avec CSS intégré) : elles ne dépendent ni de Tailwind ni de Vite. Si l'équipe a un layout commun, il suffit de remplacer `@extends('layouts.stock')` par celui-ci et de déplacer le bloc `<style>`.

---

## 2. Installation dans FoodLens

Le module s'ajoute au projet FoodLens **sans modifier les fichiers existants**, à une ligne près dans `routes/web.php`. Il utilise les modèles `Product` et `Batch` de l'équipe tels quels. Les colonnes des lots (`lot_number`, `expiration_date`, `status`) sont déclarées dans `config/stock.php`.

1. Ajouter à la fin de `routes/web.php` :
   ```php
   require __DIR__.'/stock.php';
   ```
   Les pages du module sont protégées comme le back-office : middleware `auth`, `verified` et `active.account`.
2. Créer les tables du module (sites, stocks, mouvements, recommandations, notifications) :
   ```bash
   php artisan migrate
   ```
3. Données de démonstration, facultatives et à lancer après `php artisan db:seed`, qui crée les utilisateurs :
   ```bash
   php artisan db:seed --class=StockModuleDemoSeeder
   php artisan db:seed --class=ForecastDemoSeeder
   php artisan db:seed --class=StockRandomSeeder   # facultatif : réseau aléatoire généré par les factories
   ```
   - `StockModuleDemoSeeder` crée 2 produits laitiers et 2 lots (LOT-2026-001 à J+12, LOT-2026-002 à J+2), 8 sites, 3 associations fictives, les stocks et 30 jours de ventes.
   - `ForecastDemoSeeder` ajoute « Lben 1 L » avec 8 semaines de ventes réalistes.
4. Pages : `/stocks`, `/stock-movements`, `/previsions`, `/optimization` et `/sites`, une fois connecté.

**Facultatif** :
- `APP_TIMEZONE`/`timezone` réglé sur `Africa/Tunis` dans `config/app.php`, pour des heures de mouvement locales ;
- `APP_LOCALE=fr`, pour les messages de validation en français (`lang/fr`) ;
- `STOCK_MAP_TILES=https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png`, pour des noms de villes en français sur les cartes.

## 2 bis. Factories et seeders

### Factories (`database/factories`)

| Factory | Exemples d'utilisation | États disponibles |
|---|---|---|
| `SiteFactory` | `Site::factory()->retailStore()->count(5)->create()` | `production()`, `warehouse()`, `distributionCenter()`, `retailStore()`, `association()`, `inactive()`, `withoutCoordinates()`, `inCity('Sfax')` |
| `StockFactory` | `Stock::factory()->belowThreshold()->create()` | `forBatch($batch)`, `belowThreshold()`, `empty()` |
| `StockMovementFactory` | `StockMovement::factory()->count(30)->sale()->from($magasin)->create()` | `into($site)`, `sale()`, `from($site)`, `donation()`, `transfer($de, $vers)`, `on($date)` |
| `OptimizationRecommendationFactory` | `OptimizationRecommendation::factory()->lowScore()->rejected()->create()` | `accepted()`, `rejected()`, `lowScore()` |

- **Données réalistes** : les sites sont générés dans de vraies villes tunisiennes, avec leurs coordonnées GPS, et leur code dépend du type (`MAG-`, `ENT-`, `ASSO-`…). Les produits sont des produits tunisiens.
- **Factories de l'équipe** : les lots de démonstration sont créés avec la `BatchFactory` de l'équipe (`Batch::factory()->active()`). Les produits de démonstration sont créés avec des valeurs fixes (catégorie « Produits Laitiers », origine Tunisie), via le trait `Database\Factories\Concerns\ResolvesDemoProduct`.
- **Cohérence des stocks** : la quantité d'une ligne de stock est la somme de ses mouvements. Après avoir créé des mouvements par factory, lancez `StockService::recalculate(true)`, ou la commande `php artisan stocks:recalculate --fix`.

### Seeders (`database/seeders`)

| Seeder | Rôle | Factories utilisées |
|---|---|---|
| `StockModuleDemoSeeder` | Démo complète, avec des valeurs fixées pour la soutenance | `Batch::factory()` (équipe) |
| `SiteSeeder` | 8 sites tunisiens et 3 associations, avec des codes fixes | — |
| `StockDemoSeeder` | Stocks et 30 jours de ventes de la démo | — |
| `ForecastDemoSeeder` | « Lben 1 L » : 8 semaines de ventes réalistes et une livraison par semaine | `StockFactory`, `StockMovementFactory` |
| `StockRandomSeeder` | Réseau aléatoire : 7 sites, 3 produits, plus de 500 mouvements. Peut être relancé à volonté | toutes les factories du module + `Batch::factory()` (équipe) |

Ordre conseillé : `php artisan db:seed`, puis `StockModuleDemoSeeder`, `ForecastDemoSeeder`, et éventuellement `StockRandomSeeder`.


## 3. Modèle de données

| Table | Rôle | Colonnes clés |
|---|---|---|
| `sites` | Lieux de stockage (entité ajoutée, indispensable au multi-sites) | `code` unique, `type` (production / entrepôt / centre de distribution / magasin), `latitude`, `longitude`, `capacity`, `is_active` |
| `stocks` | Niveau de stock par site × produit × lot | `quantity`, `min_threshold`, `last_movement_at` ; unique (`site_id`, `product_id`, `batch_id`) |
| `stock_movements` | Journal immuable des mouvements | `reference` (MV-AAMMJJ-XXXXXX), `type`, `source_site_id`, `destination_site_id`, `quantity`, `reason`, `user_id`, `moved_at` |
| `optimization_recommendations` | Résultats IA stockés (réutilisables par le Decision Engine) | `score`, `distance_km`, `co2_kg`, `days_to_expiry`, `weights` (JSON), `candidates` (JSON), `explanation` (JSON), `status`, `chosen_site_id`, `stock_movement_id` |

**Relations** : `Site` 1-n `Stock` ; `Product` / `Batch` 1-n `Stock` et `StockMovement` (trait `HasStock`) ; `StockMovement` n-1 `Site` source et destination ; `OptimizationRecommendation` n-1 `Batch`, `Site` (origine, recommandé, choisi) et 1-1 `StockMovement` créé à la validation.

### Convention des mouvements

La quantité est **toujours positive** ; le sens est donné par les sites :

| Type | Source | Destination | Effet |
|---|---|---|---|
| Entrée (`in`) | — | obligatoire | + au site de destination |
| Sortie (`out`) | obligatoire | — | − au site source (vente, perte, destruction) |
| Transfert (`transfer`) | obligatoire | obligatoire, ≠ source | − source, + destination |
| Ajustement (`adjustment`) | — | le site inventorié | l'utilisateur saisit la **quantité comptée**, le service calcule l'écart et l'enregistre en + (destination) ou en − (source) |

**Stock disponible** : `quantity = Σ entrées reçues − Σ sorties émises` pour chaque ligne. Il est mis à jour automatiquement dans une transaction (`DB::transaction` + `lockForUpdate`) à chaque mouvement ; la commande `php artisan stocks:recalculate` recalcule tout depuis le journal et signale les écarts (`--fix` pour les corriger).

**Contrôles bloquants** : stock insuffisant à la source, capacité du site de destination dépassée, lot rappelé (`recalled`) ou périmé (aucune entrée ni transfert, seule la sortie reste possible pour destruction). Un lot rappelé ou périmé reste compté physiquement mais sa quantité **disponible** vaut 0.

**Alertes de rupture** : le seuil est défini par produit et par site. Quand un mouvement fait passer le disponible **de au-dessus à en dessous ou égal** au seuil, l'événement `StockLevelLow` est émis et `NotifyLowStock` envoie une notification (base de données, et e-mail si `STOCK_ALERT_MAIL=true`). Pas de spam : l'alerte n'est émise qu'au franchissement.

---

## 4. Routes et endpoints

Toutes les routes sont protégées par le middleware `auth`.

| Méthode | URI | Nom | Action |
|---|---|---|---|
| GET | `/stocks` | `stocks.index` | Vue globale par site (filtres site, produit, état, recherche) |
| GET | `/stocks/create` | `stocks.create` | Ouvrir une ligne de stock / définir un seuil |
| POST | `/stocks` | `stocks.store` | Enregistrer la ligne (+ entrée initiale optionnelle) |
| GET | `/stocks/{stock}` | `stocks.show` | Détail d'une ligne + ses mouvements |
| GET | `/stocks/{stock}/edit` | `stocks.edit` | Modifier le seuil |
| PUT/PATCH | `/stocks/{stock}` | `stocks.update` | Mettre à jour le seuil |
| DELETE | `/stocks/{stock}` | `stocks.destroy` | Supprimer une ligne vide uniquement |
| GET | `/stock-movements` | `stock-movements.index` | Historique (filtres type, site, produit, lot, dates) |
| GET | `/stock-movements/create` | `stock-movements.create` | Formulaire (champs adaptés au type) |
| POST | `/stock-movements` | `stock-movements.store` | Enregistrer un mouvement via `StockService::record()` |
| GET | `/sites` | `sites.index` | Liste des sites + taux d'occupation |
| GET/POST | `/sites/create`, `/sites` | `sites.create`, `sites.store` | Créer un site |
| GET/PUT | `/sites/{site}/edit`, `/sites/{site}` | `sites.edit`, `sites.update` | Modifier un site |
| DELETE | `/sites/{site}` | `sites.destroy` | Supprimer (refusé si du stock ou des mouvements existent) |
| GET | `/optimization` | `optimization.index` | Historique des recommandations |
| GET | `/optimization/create` | `optimization.create` | Choisir lot, site d'origine, quantité |
| POST | `/optimization` | `optimization.store` | Lancer le moteur, stocker le résultat |
| GET | `/optimization/{recommendation}` | `optimization.show` | Recommandation + score + explication + classement |
| POST | `/optimization/{recommendation}/decision` | `optimization.decide` | Valider / modifier / rejeter |

Raccourci utile : le lien « Optimiser » d'une ligne de stock ouvre `optimization.create?batch_id=…` ; le formulaire préremplit alors le site d'origine et la quantité disponible.

---

## 4 bis. Rôles, permissions et tests

**Accès (Spatie)** : toutes les routes du module ont le middleware `can:manage_stocks`.

| Rôle | Accès au module |
|---|---|
| admin, producteur, transformateur, distributeur | ✅ |
| consommateur, compte sans rôle | ❌ 403 (liens masqués dans le menu et le tableau de bord) |

- La permission est créée par la migration `2026_10_30_100000_add_manage_stocks_permission` (base existante : `php artisan migrate`) et par `StockPermissionSeeder` (appelé par `DatabaseSeeder`).
- Les alertes de rupture (`NotifyLowStock`) partent seulement aux comptes **actifs** qui ont `manage_stocks` (`App\Support\StockAccess::managers()`).

**Tests Pest** (`tests/Feature/Stocks`, 57 tests) : `php artisan test tests/Feature/Stocks`

- `StockAccessTest` : accès par rôle, 403 pour le consommateur, menu masqué.
- `StockServiceTest` : calcul du stock (entrée, sortie, transfert, ajustement), refus (stock insuffisant, même site, lot rappelé), alerte au franchissement du seuil, destinataires de l'alerte, Form Request.
- `OptimizationEngineTest` : score + explication stockés en base, contraintes (capacité, origine, associations), site le plus proche, lot rappelé/périmé refusé, validation avec transfert automatique, modification, double décision refusée.

## 5. Écrans (Blade)

1. **Vue globale des stocks** (`stocks.index`) : une carte par site avec jauge d'occupation segmentée, puis le tableau des lignes (disponible, seuil, DLC, état : OK / Stock bas / Rupture / DLC proche / Bloqué). Compteurs en haut : lignes sous le seuil, lots proches de la DLC.
2. **Détail d'une ligne** (`stocks.show`) : quantité physique et disponible, seuil, historique des mouvements de cette ligne.
3. **Historique des mouvements** (`stock-movements.index`) : journal paginé et filtrable, quantités signées colorées par type.
4. **Nouveau mouvement** (`stock-movements.create`) : les champs source / destination s'affichent selon le type choisi.
5. **Sites** (`sites.*`) : CRUD avec coordonnées GPS et capacité.
6. **Interface d'optimisation** (`optimization.show`) : cadran du score /100 de la destination recommandée, résumé en une phrase, raisons détaillées par critère, pondération appliquée, classement de tous les sites (sites exclus avec la raison), formulaire de décision.

### Cartes et localisation

Les cartes utilisent Leaflet 1.9.4 (chargé depuis cdnjs) et le fond OpenStreetMap standard (modifiable avec `STOCK_MAP_TILES` dans `.env`) : aucune clé d'API n'est nécessaire, mais une connexion Internet l'est. Sans connexion, un message remplace la carte et les pages restent utilisables.

- **Sites** (`sites.index`) : carte du réseau. La couleur d'un point indique le type de site, sa taille la capacité, et son info-bulle l'occupation.
- **Formulaire de site** (`sites.create` / `sites.edit`) : clic sur la carte ou repère déplaçable pour remplir latitude et longitude, recherche d'adresse en Tunisie (géocodage Nominatim d'OpenStreetMap) et bouton « Ma position ». La ville et l'adresse sont complétées automatiquement si elles sont vides.
- **Recommandation** (`optimization.show`) : carte du trajet origine → destination recommandée (trait plein) et destination retenue si elle diffère (pointillés). Chaque site est coloré selon son score, et les sites exclus sont en gris.

Les messages de validation sont en français (`lang/fr/validation.php`, avec `APP_LOCALE=fr`), y compris les valeurs techniques : « Sortie » au lieu de `out`.

---

## 6. Optimization Engine — fonctionnement

**Entrées** : un lot, son site d'origine, une quantité. Le moteur évalue chaque site actif différent de l'origine.

### Étape 1 — contraintes bloquantes

Un site est **exclu** (et affiché comme tel, avec la raison) si :
- ses coordonnées GPS manquent ;
- sa place libre est inférieure à la quantité (`capacité − occupation < quantité`) ;
- le lot arriverait après sa DLC (`temps de trajet ≥ jours restants`).

Le lot lui-même est refusé s'il est rappelé ou périmé.

### Étape 2 — cinq scores de 0 à 100

Notations : `d` = distance routière = haversine × 1,25 ; `t` = temps de trajet = d / 60 km/h ; `J` = jours avant DLC ; `q` = quantité ; `v` = ventes moyennes/jour du site sur 30 jours (sorties enregistrées) ; `s` = stock actuel du produit au site ; `m` = seuil.

| Critère | Formule | Idée |
|---|---|---|
| Proximité | `100 × (1 − min(d, 600) / 600)` | Plus c'est près, mieux c'est |
| Capacité | `100 × min(1, (1 − occupation après réception) / 0,5)` | Note max s'il reste ≥ 50 % de place libre |
| Demande | `100 × min(1, besoin / q)` avec `besoin = max(0, v × H − s, m − s)` et `H = min(30, J − t)` | Le site a-t-il besoin de ce lot pendant sa durée de vie ? |
| Écoulement avant DLC | `100` si `(s + q)/v ≤ J − t`, sinon `100 × (J − t) / ((s + q)/v)` ; 15 si aucune vente | Le site peut-il tout vendre avant la date limite ? |
| Impact CO₂ | `CO₂ = d × tonnes × 0,115 kg/t·km` ; score = `100 × CO₂min / CO₂site` | Relatif au site faisable le moins émetteur |

### Étape 3 — pondération (dynamique)

Poids par défaut (`config/stock.php`) : demande 25 %, écoulement DLC 25 %, proximité 20 %, capacité 15 %, CO₂ 15 %.
Si le lot est à **3 jours ou moins** de sa DLC, les poids « écoulement DLC » et « proximité » sont multipliés par 1,5 puis tout est renormalisé à 100 % — la raison est affichée dans l'explication.

`Score final = Σ (poids × score du critère)`. Les sites faisables sont classés par score ; le premier est recommandé.

### Étape 4 — explication (pas de boîte noire)

La recommandation stocke en JSON : une phrase de synthèse, les contributions de chaque critère avec leurs chiffres réels (km, kg CO₂, ventes/jour, jours d'écoulement…), l'écart avec le deuxième site et la liste des sites exclus avec leur motif.

### Étape 5 — décision de l'utilisateur

- **Valider** : la destination recommandée est retenue ;
- **Modifier** : l'utilisateur choisit un autre site (conservé en `chosen_site_id`) ;
- **Rejeter** : aucune suite.

Pour Valider / Modifier, une case permet de **créer automatiquement le transfert** de stock (le mouvement est lié via `stock_movement_id`). L'événement `BatchDestinationValidated` est ensuite émis.

### Étape 6 — avertissement « score faible »

Si le meilleur score est inférieur à `low_score_threshold` (60 par défaut), la page affiche un avertissement. Il contient :
- le critère le plus faible et le conseil adapté (promotion ou don si c'est la DLC, répartition si c'est la demande…) ;
- la quantité que l'ensemble des sites peut réellement vendre avant la DLC : `Σ max(0, ventes/jour × (jours restants − trajet) − stock actuel)` ;
- un bouton « Relancer avec N u. » qui prérenseigne la quantité absorbable par le meilleur site.

Tous les paramètres (poids, facteur d'émission, vitesse, distance max, horizons…) sont modifiables dans `config/stock.php` sans toucher au code.

---

## 7. Intégration avec les autres modules

- **Ghassen (Lots & Traçabilité)** : le module lit `batches` (code, DLC, statut). Pour ajouter une étape « transport » à la timeline lors d'une validation, créer un listener sur `BatchDestinationValidated` :

  ```php
  // app/Listeners/AddTransferToTimeline.php
  public function handle(BatchDestinationValidated $event): void
  {
      $reco = $event->recommendation;
      TraceabilityEvent::create([
          'batch_id' => $reco->batch_id,
          'type' => 'transport',
          'description' => 'Transfert vers '.$reco->chosenSite->name,
          // ... selon le schéma de Ghassen
      ]);
      // éventuellement : $reco->batch->update(['status' => 'in_transit']);
  }
  ```

- **Amal (Alertes)** : pour centraliser les ruptures dans sa table `alerts`, ajouter un listener sur `StockLevelLow` qui crée une `Alert` (type `stock_low`, niveau `warning`, ou `critical` si disponible = 0).

- **Decision Engine** : les recommandations sont en base (`optimization_recommendations`), avec score, critères, pondération et décision humaine — directement réutilisables.

---

## 7 bis. Plan anti-gaspillage

Quand un lot ne peut pas être vendu avant sa DLC par un simple transfert (score inférieur à 60), la page de recommandation propose un **plan en 3 niveaux**. Il est calculé par `App\Services\Optimization\AntiWastePlanner`.

1. **Transferts partiels** : chaque point de vente reçoit ce qu'il peut vendre avant la DLC, soit `ventes/jour × (jours restants − trajet) − stock qui périme avant ce lot`. Le plan applique la règle **FEFO** : le produit qui périme le plus tôt est vendu en premier. Le stock sans lot est considéré comme plus récent.
2. **Promotion calculée** : c'est la plus petite remise, par paliers de 10 %, qui écoule le reste. On suppose que les ventes sont multipliées par `1 + élasticité × remise`, avec une élasticité de 2,0. La remise est plafonnée à 30 %, car au-delà la vente se fait à perte et un don est préférable.
3. **Don solidaire** : le reste part vers les associations les plus proches, qui doivent recevoir le don au moins 6 h avant la DLC. Pour une association, la capacité est la quantité maximale acceptée par don.

Les envois de moins de 20 u. ne sont pas rentables : ils sont ajoutés au don, sauf s'il n'y a aucune association joignable. Ce qui ne peut être ni vendu ni donné est signalé comme **perte probable**.

**Appliquer le plan** crée des transferts (motif « Plan anti-gaspillage #n · promotion −30 % ») et des sorties (motif « Don solidaire · association »). Le plan est ensuite conservé en base (`rescue_plan`), et la recommandation passe au statut « Plan anti-gaspillage ». Chaque don dispose d'un **bon de don imprimable** (`optimization.donation`).

**Impact** : un bandeau 🌱, sur les pages Stocks et Optimisation, cumule les plans appliqués. Il indique :
- les kg de nourriture sauvés ;
- les repas offerts, à raison de 0,5 kg par repas (convention des banques alimentaires) ;
- le CO₂e évité, compté à 2,5 kg CO₂e par kg non gaspillé (moyenne FAO), transport déduit.

Tous les paramètres sont dans `config/stock.php`, section `anti_waste`.

| Méthode | URI | Nom | Action |
|---|---|---|---|
| POST | `/optimization/{recommendation}/rescue` | `optimization.rescue` | Appliquer le plan anti-gaspillage |
| GET | `/optimization/{recommendation}/donation/{site}` | `optimization.donation` | Bon de don imprimable |
| GET | `/previsions` | `forecast.index` | Prévision de la demande (site, produit, date de départ, horizon) |

**Installation** : lancer `php artisan migrate` pour ajouter les colonnes `rescue_plan`, `rescue_applied_at` et `rescue_applied_by`, puis `php artisan db:seed --class=SiteSeeder` pour ajouter les 3 associations fictives. Le seeder peut être relancé sans risque.

**Démonstration** : sur un lot à J-2 dont le score est faible, le plan affiche « Sans plan : 200 u. perdues → Avec le plan : 0 ». Il combine des transferts vers Sousse, Monastir et Nabeul, une remise de 30 %, et un don à l'association de Sfax. Cliquer sur « Appliquer », puis montrer les mouvements créés, le bon de don et le bandeau d'impact.

---

## 7 ter. Prévision de la demande (calendrier tunisien)

La demande n'est plus une simple moyenne sur 30 jours. Elle est prévue jour par jour par `App\Services\Forecast\DemandForecaster`, et cette prévision est utilisée par le **moteur d'optimisation**, le **plan anti-gaspillage** et les **ruptures prévues**. Elle se désactive avec `STOCK_FORECAST=false` dans `.env`, ce qui revient à la moyenne simple.

### Méthode, étape par étape

1. **Historique** : les ventes des 8 dernières semaines, jour par jour. Les sorties dont le motif indique un don, une casse ou une destruction ne sont pas des ventes (scope `StockMovement::sales()`). Un jour sans **aucune** vente saisie dans le magasin est une donnée manquante (magasin fermé, saisie oubliée) : il est ignoré au lieu de compter pour 0.
2. **Ventes exceptionnelles** : toute valeur au-dessus de `médiane + 4 × 1,4826 × écart absolu médian` est ramenée à ce plafond. Une erreur de saisie ou une commande isolée ne fausse donc pas la prévision.
3. **Calendrier tunisien** : les effets des périodes spéciales sont retirés de l'historique, puis réappliqués aux jours prévus. Les périodes prises en compte sont le Ramadan (produits laitiers × 1,35, autres × 1,15), les préparatifs et les jours de l'Aïd el-Fitr et de l'Aïd el-Adha, la rentrée scolaire, et la saison estivale dans les villes balnéaires. Les produits sont classés en familles d'après leur nom (« laitier » pour lait, yaourt, lben…).
4. **Rythme de la semaine** : l'effet de chaque jour est appris sur l'historique. Il est rapproché de 1 quand il y a peu de semaines de données.
5. **Lissage exponentiel de Holt** : un niveau et une tendance, cette tendance étant amortie (× 0,9 par jour) pour ne pas s'emballer. La prévision d'un jour vaut `(niveau + tendance amortie) × effet du jour × effet des événements`. La fourchette probable à 80 % vaut ± 1,28 écart-type des erreurs passées.
6. **Auto-calibrage et précision** : 20 réglages sont essayés, qui combinent la réactivité, la tendance et le rythme de la semaine (activé ou non). Pour chacun, l'IA rejoue les 7 derniers jours comme si elle ne les connaissait pas, puis garde le réglage qui se trompe le moins. Son erreur (WAPE) est affichée à côté de celle de la moyenne simple.

### Écrans

- **Prévisions** (`/previsions`, `forecast.index`) affiche :
  - un graphique avec les ventes réelles des 21 derniers jours, la prévision et sa fourchette, et les périodes spéciales surlignées ;
  - l'explication des 7 étapes, la précision mesurée et la date de rupture prévue ;
  - le détail jour par jour.

  Des raccourcis permettent de **simuler** la demande à une période donnée, par exemple « Et si c'était le Ramadan ? ».
- **Stocks** : un encadré « Ruptures prévues dans les 7 prochains jours » annonce les ruptures avant qu'elles arrivent. Il se base sur le stock disponible et les ventes prévues cumulées.

### Démonstration

`php artisan db:seed --class=ForecastDemoSeeder` crée le produit **« Lben 1 L »** avec 8 semaines de ventes réalistes dans 5 magasins : pic le week-end, légère hausse, rentrée scolaire et ± 8 % de hasard. Les données existantes ne sont pas modifiées, et le seeder ne fait rien s'il a déjà été lancé.

Sur cet historique, l'IA retrouve seule le rythme de la semaine (samedi +21 %, lundi −14 %) et se trompe 2 à 4 fois moins que la moyenne simple. Sur les données de démo d'origine, qui n'ont pas de rythme, l'auto-calibrage désactive honnêtement l'effet du jour, et la prévision rejoint la moyenne.

Les dates des fêtes religieuses suivent le calendrier lunaire : elles sont estimées dans `config/stock.php` (`forecast.events`) et doivent être confirmées chaque année. Les multiplicateurs sont des hypothèses métier modifiables.

---

## 7 quater. Jumeau numérique « Et si ? » (IA de simulation)

Menu **Digital twin** (`/simulations`). Le jumeau numérique copie l'**état réel** des stocks d'aujourd'hui (lignes, lots, DLC, sites GPS), applique un scénario, puis rejoue 7 à 30 jours **deux fois** avec la même demande et les mêmes incidents :

| | Sans IA (pratique habituelle) | Avec IA |
|---|---|---|
| Vente | lot le plus proche de sa DLC d'abord (FEFO) | idem |
| Réassort | quand le magasin passe sous son seuil, jusqu'à 2 × seuil, depuis l'entrepôt le mieux fourni, premier lot venu | quand le stock ne tiendra pas jusqu'au lendemain de la prochaine livraison (prévision + délai camion), lot qui périme le plus tôt |
| Lot à risque | rien : jeté à la DLC | transfert anticipé vers le magasin le plus proche qui vendra avant la DLC, puis promotion calculée (élasticité-prix), puis don la veille de la DLC |

- **Scénarios** : pic de demande (Ramadan +35 %), panne de camion (retard), site fermé, rupture de la chaîne du froid (lots qui perdent des jours de DLC), ou personnalisé.
- **Demande** : prévision du module (`DemandForecaster`, calendrier tunisien), jour par jour.
- **Résultats** : ventes, ventes perdues, taux de service, unités et kg jetés, dons et repas, km, CO₂ du transport et du gaspillage, graphiques cumulés, carte animée jour par jour, journal des décisions.
- **Contrôle** : pour chaque stratégie, stock de départ = vendu + jeté + donné + restant. Les stocks réels ne sont **jamais** modifiés.
- Code : `app/Services/Simulation/DigitalTwinSimulator.php`, `SimulationController`, `SimulationRequest`, table `stock_simulations` (historique), `StockSimulationFactory`.

**Tests** : `DigitalTwinSimulatorTest` (11).

## 8. Scénario de démonstration (soutenance, ~5 min)

1. `/sites` : montrer les 8 sites et leurs jauges d'occupation.
2. `/stocks` : vue globale ; montrer Sousse en **stock bas** (120 u. pour un seuil de 150 et 40 ventes/jour).
3. `/stock-movements/create` : enregistrer une sortie de 160 u. à Gabès (300 → 140, sous le seuil de 150) → la notification de rupture est créée ; puis montrer l'historique filtré par site.
4. Tenter une sortie supérieure au stock → message d'erreur métier.
5. `/optimization/create` : lot de l'entrepôt de Sfax, 300 u. → la recommandation apparaît avec son score et ses raisons.
6. Commenter le classement : Gabès et Kairouan sont les plus proches mais vendent peu (Kairouan : **aucune vente**, risque d'invendus) ; Sousse l'emporte grâce à sa demande et à son écoulement rapide avant la DLC ; Tunis est pénalisé par la distance et le CO₂.
7. Choisir **Modifier** vers un autre magasin avec transfert automatique → montrer le mouvement créé et les stocks mis à jour.
8. Refaire l'optimisation sur un lot à ≤ 3 jours de sa DLC → montrer la pondération qui change et l'explication correspondante.

---

## 9. Limites et pistes d'amélioration

- La distance est une estimation (haversine × facteur routier) ; une API d'itinéraire (OSRM, Google Directions) peut remplacer `Geo::haversineKm`.
- La demande est une moyenne glissante ; une prévision saisonnière ou par jour de semaine améliorerait la précision.
- Le moteur choisit une destination unique ; une répartition d'un lot sur plusieurs sites relève de la programmation linéaire (ex: minimiser CO₂ + invendus sous contraintes de capacité).
- Les destinataires des notifications sont tous les utilisateurs : à restreindre selon les rôles du projet dans `NotifyLowStock`.
