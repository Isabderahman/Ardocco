# 📍 Guide de Géolocalisation - Région Casablanca-Settat

Ce guide explique comment utiliser le système de géolocalisation pour les régions, provinces et communes du Maroc dans le projet ARDOCCO.

## 🎯 Vue d'ensemble

Le système ajoute des coordonnées GPS (latitude/longitude) à trois niveaux :
- **Régions** : Coordonnées du centre de la région
- **Provinces** : Coordonnées du chef-lieu de la province
- **Communes** : Coordonnées réelles de chaque commune (urbaine ou rurale)

## 📦 Fichiers créés

### 1. Migration
**Fichier** : `database/migrations/2026_01_19_150000_add_coordinates_to_location_tables.php`

Ajoute les colonnes suivantes aux 3 tables :
```php
- latitude  (DECIMAL 10,8) - Précision jusqu'à ~1.1 mètre
- longitude (DECIMAL 11,8) - Précision jusqu'à ~1.1 mètre
```

Les index sont créés automatiquement pour optimiser les recherches géospatiales.

### 2. Seeder avec coordonnées GPS
**Fichier** : `database/seeders/CasablancaSettatGeoSeeder.php`

Contient les données complètes de la région Casablanca-Settat :
- ✅ 1 région avec coordonnées GPS
- ✅ 9 provinces avec coordonnées GPS
- ✅ 80+ communes avec coordonnées GPS réelles

## 🚀 Installation

### Étape 1 : Exécuter la migration

```bash
cd backEnd

# Lancer la migration
php artisan migrate

# Vérifier que les colonnes ont été ajoutées
php artisan tinker
>>> Schema::hasColumn('regions', 'latitude')
# Devrait retourner: true
```

### Étape 2 : Exécuter le seeder

```bash
# Option 1 : Exécuter uniquement le seeder Casablanca-Settat
php artisan db:seed --class=CasablancaSettatGeoSeeder

# Option 2 : Exécuter tous les seeders
php artisan db:seed

# Option 3 : Réinitialiser la base de données et tout seeder
php artisan migrate:fresh --seed
```

### Étape 3 : Vérifier les données

```bash
php artisan tinker
```

Dans tinker, exécutez :

```php
// Compter les enregistrements
DB::table('regions')->count()        // Devrait retourner: 1
DB::table('provinces')->count()       // Devrait retourner: 9
DB::table('communes')->count()        // Devrait retourner: 80+

// Vérifier les coordonnées
DB::table('regions')->first()
// Devrait afficher la région avec latitude et longitude

// Communes urbaines vs rurales
DB::table('communes')->where('type', 'urbaine')->count()
DB::table('communes')->where('type', 'rurale')->count()

// Afficher Casablanca avec ses coordonnées
DB::table('communes')->where('name_fr', 'Casablanca-Anfa')->first()

// Lister toutes les provinces avec coordonnées
DB::table('provinces')->select('name_fr', 'latitude', 'longitude')->get()
```

## 🗺️ Utilisation des coordonnées GPS

### 1. Récupérer une commune avec ses coordonnées

```php
use App\Models\Commune;

$commune = Commune::where('name_fr', 'Casablanca-Anfa')->first();
echo "Latitude: " . $commune->latitude;
echo "Longitude: " . $commune->longitude;
```

### 2. Rechercher les communes à proximité (Haversine Formula)

```php
// Trouver toutes les communes dans un rayon de 10 km autour d'un point
$latitude = 33.5731;  // Casablanca
$longitude = -7.5898;
$radius = 10; // km

$communes = DB::table('communes')
    ->select('*')
    ->selectRaw(
        '(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance',
        [$latitude, $longitude, $latitude]
    )
    ->having('distance', '<', $radius)
    ->orderBy('distance')
    ->get();
```

### 3. Recherche géospatiale avec PostGIS (si activé)

Si vous activez PostGIS dans la migration, vous pouvez faire :

```sql
-- Activer PostGIS (décommenter dans la migration)
CREATE EXTENSION IF NOT EXISTS postgis;

-- Créer une colonne géométrique
ALTER TABLE communes ADD COLUMN geom geometry(Point, 4326);

-- Remplir avec les coordonnées existantes
UPDATE communes SET geom = ST_SetSRID(ST_MakePoint(longitude, latitude), 4326);

-- Rechercher dans un rayon de 10 km
SELECT name_fr, name_ar,
       ST_Distance(geom::geography, ST_MakePoint(-7.5898, 33.5731)::geography) / 1000 as distance_km
FROM communes
WHERE ST_DWithin(geom::geography, ST_MakePoint(-7.5898, 33.5731)::geography, 10000)
ORDER BY distance_km;
```

### 4. Alternative avec l'extension earthdistance (PostgreSQL)

```sql
-- Activer les extensions (décommenter dans la migration)
CREATE EXTENSION IF NOT EXISTS cube;
CREATE EXTENSION IF NOT EXISTS earthdistance;

-- Recherche avec earthdistance
SELECT name_fr,
       earth_distance(
           ll_to_earth(latitude, longitude),
           ll_to_earth(33.5731, -7.5898)
       ) / 1000 as distance_km
FROM communes
WHERE earth_box(ll_to_earth(33.5731, -7.5898), 10000) @> ll_to_earth(latitude, longitude)
ORDER BY distance_km;
```

## 📊 Statistiques des données

### Région Casablanca-Settat

| Niveau | Nombre | Avec GPS |
|--------|--------|----------|
| Région | 1 | ✅ |
| Provinces | 9 | ✅ |
| Communes totales | 80+ | ✅ |
| Communes urbaines | ~40 | ✅ |
| Communes rurales | ~40 | ✅ |

### Provinces incluses

1. **Casablanca** (20 communes) - Tous les arrondissements + communes périphériques
2. **Mohammedia** (12 communes)
3. **El Jadida** (12 communes)
4. **Nouaceur** (7 communes)
5. **Settat** (12 communes)
6. **Berrechid** (8 communes)
7. **Sidi Bennour** (8 communes)
8. **Médiouna** (5 communes)

## 🔍 Exemples de requêtes utiles

### Trouver la commune la plus proche d'un point

```php
$lat = 33.5731;
$lng = -7.5898;

$nearest = DB::table('communes')
    ->select('*')
    ->selectRaw(
        '(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance',
        [$lat, $lng, $lat]
    )
    ->orderBy('distance')
    ->first();
```

### Lister toutes les communes d'une province avec coordonnées

```php
$communes = DB::table('communes')
    ->join('provinces', 'communes.province_id', '=', 'provinces.id')
    ->where('provinces.name_fr', 'Casablanca')
    ->select('communes.name_fr', 'communes.latitude', 'communes.longitude', 'communes.type')
    ->orderBy('communes.type')
    ->orderBy('communes.name_fr')
    ->get();
```

### Afficher la carte des provinces

```php
$provinces = DB::table('provinces')
    ->join('regions', 'provinces.region_id', '=', 'regions.id')
    ->where('regions.code', 'CS')
    ->select('provinces.name_fr', 'provinces.name_ar', 'provinces.latitude', 'provinces.longitude')
    ->get();

// Retourner en JSON pour une carte (Leaflet, Google Maps, etc.)
return response()->json($provinces);
```

## 🛠️ Personnalisation

### Ajouter d'autres régions

1. Créez un nouveau seeder (exemple : `TangerTetouanSeeder.php`)
2. Suivez la même structure que `CasablancaSettatGeoSeeder.php`
3. Ajoutez les coordonnées GPS réelles pour chaque localité
4. Ajoutez le seeder dans `DatabaseSeeder.php`

### Mettre à jour les coordonnées

Si vous avez des coordonnées plus précises :

```php
DB::table('communes')
    ->where('name_fr', 'Casablanca-Anfa')
    ->update([
        'latitude' => 33.5731,
        'longitude' => -7.5898
    ]);
```

## 📝 Notes importantes

- **Précision** : Les coordonnées ont une précision de 8 décimales (environ 1.1 mètre)
- **Format** : Latitude/Longitude en degrés décimaux (DD)
- **Système** : WGS84 (EPSG:4326) - Standard GPS mondial
- **NULL** : Les coordonnées peuvent être NULL si non disponibles
- **Performance** : Les index sont créés automatiquement pour optimiser les recherches

## 🔗 Ressources

- [Formule de Haversine](https://fr.wikipedia.org/wiki/Formule_de_haversine)
- [PostGIS Documentation](https://postgis.net/docs/)
- [Leaflet.js](https://leafletjs.com/) - Bibliothèque de cartes JavaScript
- [Google Maps API](https://developers.google.com/maps)

## 🐛 Dépannage

### Erreur : "Column not found: latitude"

Exécutez la migration :
```bash
php artisan migrate
```

### Les coordonnées sont NULL

Vérifiez que vous avez exécuté le bon seeder :
```bash
php artisan db:seed --class=CasablancaSettatGeoSeeder
```

### Problème de performance

Vérifiez que les index existent :
```sql
SHOW INDEXES FROM communes;
```

Vous devriez voir `communes_coordinates_index`.

---

**Auteur** : Ardocco Team
**Date** : 2026-01-19
**Version** : 1.0
