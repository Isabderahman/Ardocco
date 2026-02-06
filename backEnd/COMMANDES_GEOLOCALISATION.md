# 🚀 Commandes d'Exécution - Géolocalisation

## ⚡ Installation Rapide

```bash
# Se placer dans le dossier backend
cd backEnd

# 1. Exécuter la migration (ajoute les colonnes latitude/longitude)
php artisan migrate

# 2. Exécuter le seeder (insère les données avec coordonnées GPS)
php artisan db:seed --class=CasablancaSettatGeoSeeder
```

## 🔄 Réinitialisation complète (si nécessaire)

```bash
# Supprimer toutes les données et recréer les tables
php artisan migrate:fresh --seed
```

## ✅ Vérification des données

```bash
# Ouvrir tinker (console interactive Laravel)
php artisan tinker
```

Dans tinker, copiez/collez ces commandes :

```php
// ====================================
// VÉRIFIER LES COMPTEURS
// ====================================

echo "\n📊 STATISTIQUES\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Régions       : " . DB::table('regions')->count() . "\n";
echo "Provinces     : " . DB::table('provinces')->count() . "\n";
echo "Communes      : " . DB::table('communes')->count() . "\n";
echo "  - Urbaines  : " . DB::table('communes')->where('type', 'urbaine')->count() . "\n";
echo "  - Rurales   : " . DB::table('communes')->where('type', 'rurale')->count() . "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// ====================================
// VÉRIFIER LES COORDONNÉES GPS
// ====================================

echo "📍 RÉGION CASABLANCA-SETTAT\n";
$region = DB::table('regions')->first();
echo "Nom       : {$region->name_fr} ({$region->name_ar})\n";
echo "Code      : {$region->code}\n";
echo "GPS       : {$region->latitude}, {$region->longitude}\n\n";

echo "📍 PROVINCES (extrait)\n";
DB::table('provinces')
    ->select('name_fr', 'code', 'latitude', 'longitude')
    ->get()
    ->each(function($p) {
        echo "  • {$p->name_fr} [{$p->code}] → GPS: {$p->latitude}, {$p->longitude}\n";
    });

echo "\n📍 COMMUNES URBAINES DE CASABLANCA (extrait)\n";
DB::table('communes')
    ->join('provinces', 'communes.province_id', '=', 'provinces.id')
    ->where('provinces.name_fr', 'Casablanca')
    ->where('communes.type', 'urbaine')
    ->select('communes.name_fr', 'communes.latitude', 'communes.longitude')
    ->limit(5)
    ->get()
    ->each(function($c) {
        echo "  • {$c->name_fr} → GPS: {$c->latitude}, {$c->longitude}\n";
    });

// ====================================
// VÉRIFIER QU'AUCUNE COORDONNÉE N'EST NULL
// ====================================

echo "\n🔍 VÉRIFICATION INTÉGRITÉ GPS\n";
$regionsWithoutGPS = DB::table('regions')->whereNull('latitude')->orWhereNull('longitude')->count();
$provincesWithoutGPS = DB::table('provinces')->whereNull('latitude')->orWhereNull('longitude')->count();
$communesWithoutGPS = DB::table('communes')->whereNull('latitude')->orWhereNull('longitude')->count();

echo "Régions sans GPS   : " . ($regionsWithoutGPS > 0 ? "❌ $regionsWithoutGPS" : "✅ 0") . "\n";
echo "Provinces sans GPS : " . ($provincesWithoutGPS > 0 ? "❌ $provincesWithoutGPS" : "✅ 0") . "\n";
echo "Communes sans GPS  : " . ($communesWithoutGPS > 0 ? "❌ $communesWithoutGPS" : "✅ 0") . "\n";
```

Pour quitter tinker :
```php
exit
```

## 🧪 Tester la recherche géospatiale

Dans tinker :

```php
// ====================================
// RECHERCHE PAR PROXIMITÉ (rayon 10km)
// ====================================

$latitude = 33.5731;   // Centre de Casablanca
$longitude = -7.5898;
$radius = 10; // km

echo "\n🔍 Communes dans un rayon de {$radius}km autour de Casablanca\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$communes = DB::table('communes')
    ->select('name_fr', 'name_ar', 'type', 'latitude', 'longitude')
    ->selectRaw(
        '(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance',
        [$latitude, $longitude, $latitude]
    )
    ->having('distance', '<', $radius)
    ->orderBy('distance')
    ->limit(10)
    ->get();

foreach ($communes as $commune) {
    echo sprintf(
        "  📍 %-30s [%s] → %.2f km\n",
        $commune->name_fr,
        $commune->type,
        $commune->distance
    );
}

// ====================================
// TROUVER LA COMMUNE LA PLUS PROCHE
// ====================================

echo "\n🎯 Commune la plus proche de Casablanca centre\n";
$nearest = DB::table('communes')
    ->select('name_fr', 'name_ar', 'type')
    ->selectRaw(
        '(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance',
        [$latitude, $longitude, $latitude]
    )
    ->orderBy('distance')
    ->first();

echo "  → {$nearest->name_fr} ({$nearest->name_ar})\n";
echo "  → Type: {$nearest->type}\n";
echo "  → Distance: " . round($nearest->distance, 2) . " km\n";
```

## 📊 Requêtes SQL directes (optionnel)

```bash
# Se connecter à PostgreSQL
psql -U votre_utilisateur -d votre_base_de_donnees
```

Dans PostgreSQL :

```sql
-- Lister toutes les régions avec coordonnées
SELECT name_fr, name_ar, code, latitude, longitude
FROM regions;

-- Lister toutes les provinces de Casablanca-Settat
SELECT p.name_fr, p.code, p.latitude, p.longitude
FROM provinces p
JOIN regions r ON p.region_id = r.id
WHERE r.code = 'CS'
ORDER BY p.name_fr;

-- Compter les communes par province
SELECT p.name_fr AS province,
       COUNT(*) AS total_communes,
       COUNT(CASE WHEN c.type = 'urbaine' THEN 1 END) AS urbaines,
       COUNT(CASE WHEN c.type = 'rurale' THEN 1 END) AS rurales
FROM communes c
JOIN provinces p ON c.province_id = p.id
GROUP BY p.name_fr
ORDER BY total_communes DESC;

-- Vérifier les index
SELECT indexname, indexdef
FROM pg_indexes
WHERE tablename IN ('regions', 'provinces', 'communes')
AND indexname LIKE '%coordinates%';
```

## 🗺️ Export pour carte (JSON)

```php
// Dans tinker ou dans un controller

// Export de toutes les provinces avec coordonnées
$provinces = DB::table('provinces')
    ->join('regions', 'provinces.region_id', '=', 'regions.id')
    ->where('regions.code', 'CS')
    ->select(
        'provinces.id',
        'provinces.name_fr',
        'provinces.name_ar',
        'provinces.code',
        'provinces.latitude',
        'provinces.longitude'
    )
    ->get();

echo json_encode($provinces, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// Export de toutes les communes pour une carte
$communes = DB::table('communes')
    ->join('provinces', 'communes.province_id', '=', 'provinces.id')
    ->join('regions', 'provinces.region_id', '=', 'regions.id')
    ->where('regions.code', 'CS')
    ->select(
        'communes.id',
        'communes.name_fr',
        'communes.name_ar',
        'communes.type',
        'communes.code_postal',
        'communes.latitude',
        'communes.longitude',
        'provinces.name_fr as province'
    )
    ->get();

// Sauvegarder dans un fichier
file_put_contents(
    storage_path('app/communes_casablanca_settat.json'),
    json_encode($communes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

echo "✅ Fichier exporté: storage/app/communes_casablanca_settat.json\n";
```

## 🔧 Commandes de maintenance

```bash
# Vider uniquement les données (garde la structure)
php artisan db:wipe

# Recréer les tables et seeder
php artisan migrate:fresh --seed

# Exécuter uniquement les migrations manquantes
php artisan migrate

# Rollback de la dernière migration
php artisan migrate:rollback

# Rollback de la migration de géolocalisation spécifiquement
php artisan migrate:rollback --step=1

# Voir le statut des migrations
php artisan migrate:status

# Liste tous les seeders disponibles
php artisan db:seed --list
```

## 📦 Backup des données

```bash
# Exporter la base de données (PostgreSQL)
pg_dump -U votre_utilisateur votre_base > backup_$(date +%Y%m%d).sql

# Restaurer depuis un backup
psql -U votre_utilisateur votre_base < backup_20260119.sql

# Exporter uniquement les données de géolocalisation
pg_dump -U votre_utilisateur -t regions -t provinces -t communes votre_base > geo_backup.sql
```

## 🎨 Visualisation sur une carte (exemple Leaflet)

Créez un fichier HTML pour visualiser :

```html
<!DOCTYPE html>
<html>
<head>
    <title>Carte Casablanca-Settat</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        #map { height: 600px; }
    </style>
</head>
<body>
    <h1>Région Casablanca-Settat</h1>
    <div id="map"></div>
    <script>
        // Initialiser la carte
        const map = L.map('map').setView([33.5731, -7.5898], 9);

        // Ajouter le fond de carte
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(map);

        // Charger les données depuis votre API Laravel
        fetch('/api/communes/casablanca-settat')
            .then(response => response.json())
            .then(data => {
                data.forEach(commune => {
                    L.marker([commune.latitude, commune.longitude])
                        .bindPopup(`
                            <b>${commune.name_fr}</b><br>
                            ${commune.name_ar}<br>
                            Type: ${commune.type}<br>
                            Province: ${commune.province}
                        `)
                        .addTo(map);
                });
            });
    </script>
</body>
</html>
```

## 📞 Support

Pour toute question ou problème :
- Consultez le fichier `GEOLOCALISATION_GUIDE.md`
- Vérifiez les logs Laravel : `storage/logs/laravel.log`
- Activez le mode debug dans `.env` : `APP_DEBUG=true`

---

**Projet** : ARDOCCO
**Date** : 2026-01-19
