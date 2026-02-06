# 🚀 QUICKSTART - Géolocalisation ARDOCCO

> Système complet de géolocalisation opérationnel en 2 minutes !

---

## ⚡ Installation ultra-rapide

```bash
cd backEnd

# Étape 1 : Migration
php artisan migrate

# Étape 2 : Seeder
php artisan db:seed --class=CasablancaSettatGeoSeeder

# Étape 3 : Test
./test_geolocalisation.sh
```

**✅ TERMINÉ !** Votre API de géolocalisation est prête.

---

## 🎯 Test en 30 secondes

### 1. Vérifier que tout fonctionne

```bash
curl http://localhost:8000/api/geo/stats
```

**Résultat attendu** :
```json
{
  "success": true,
  "data": {
    "regions": 1,
    "provinces": 9,
    "communes": {
      "total": 84,
      "urbaines": 42,
      "rurales": 42
    }
  }
}
```

### 2. Rechercher des communes

```bash
curl "http://localhost:8000/api/geo/search?q=casa"
```

### 3. Trouver les communes à proximité

```bash
curl "http://localhost:8000/api/geo/nearby?latitude=33.5731&longitude=-7.5898&radius=5"
```

---

## 📊 Ce que vous avez maintenant

### ✅ Base de données
- **1 région** : Casablanca-Settat avec GPS
- **9 provinces** : Toutes avec coordonnées GPS réelles
- **84 communes** : Coordonnées GPS précises (urbaines + rurales)

### ✅ API REST complète
- 9 endpoints prêts à l'emploi
- Recherche par proximité GPS (formule de Haversine)
- Recherche par nom avec autocomplete
- Export JSON pour cartes

### ✅ Documentation
- 7 fichiers de documentation détaillée
- Exemples d'utilisation API
- Composable Nuxt 3 prêt à l'emploi
- Script de test automatique

---

## 🔌 Endpoints disponibles

| URL | Description | Exemple |
|-----|-------------|---------|
| `GET /api/geo/stats` | Statistiques | [Voir](http://localhost:8000/api/geo/stats) |
| `GET /api/geo/regions` | Liste régions | [Voir](http://localhost:8000/api/geo/regions) |
| `GET /api/geo/provinces/CS` | Provinces | [Voir](http://localhost:8000/api/geo/provinces/CS) |
| `GET /api/geo/communes/CAS` | Communes | [Voir](http://localhost:8000/api/geo/communes/CAS) |
| `GET /api/geo/nearby?...` | Recherche GPS | [Tester](#) |
| `GET /api/geo/search?q=...` | Recherche nom | [Tester](#) |

---

## 💡 Exemples d'utilisation

### JavaScript / Fetch

```javascript
// Rechercher des communes à proximité
const response = await fetch(
  'http://localhost:8000/api/geo/nearby?latitude=33.5731&longitude=-7.5898&radius=10'
)
const data = await response.json()
console.log(data.data) // Liste des communes
```

### PHP / Laravel

```php
// Dans un contrôleur
$communes = DB::table('communes')
    ->selectRaw(
        '*, (6371 * acos(cos(radians(?)) * cos(radians(latitude)) *
        cos(radians(longitude) - radians(?)) + sin(radians(?)) *
        sin(radians(latitude)))) AS distance',
        [33.5731, -7.5898, 33.5731]
    )
    ->having('distance', '<', 10)
    ->orderBy('distance')
    ->get();
```

### Vue 3 / Nuxt 3

```vue
<script setup>
// Utiliser le composable fourni
const { findNearby } = useGeoAPI()
const communes = ref([])

onMounted(async () => {
  communes.value = await findNearby(33.5731, -7.5898, 10)
})
</script>

<template>
  <ul>
    <li v-for="commune in communes" :key="commune.id">
      {{ commune.name_fr }} - {{ commune.distance.toFixed(2) }} km
    </li>
  </ul>
</template>
```

---

## 🗺️ Intégration carte (Leaflet)

```html
<div id="map" style="height: 600px;"></div>

<script>
const map = L.map('map').setView([33.5731, -7.5898], 10)

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map)

// Charger les communes
fetch('http://localhost:8000/api/geo/export/casablanca-settat')
  .then(res => res.json())
  .then(data => {
    data.data.all.forEach(commune => {
      L.marker([commune.lat, commune.lng])
        .bindPopup(`<b>${commune.name_fr}</b>`)
        .addTo(map)
    })
  })
</script>
```

---

## 📚 Documentation détaillée

| Fichier | Contenu | Priorité |
|---------|---------|----------|
| [README_GEOLOCALISATION.md](README_GEOLOCALISATION.md) | Aperçu général | ⭐⭐⭐ |
| [INSTALLATION.txt](INSTALLATION.txt) | Guide d'installation | ⭐⭐⭐ |
| [API_EXAMPLES.md](API_EXAMPLES.md) | Exemples API | ⭐⭐⭐ |
| [GEOLOCALISATION_GUIDE.md](GEOLOCALISATION_GUIDE.md) | Guide complet | ⭐⭐ |
| [COMMANDES_GEOLOCALISATION.md](COMMANDES_GEOLOCALISATION.md) | Commandes utiles | ⭐⭐ |
| [EXAMPLE_NUXT_COMPOSABLE.ts](EXAMPLE_NUXT_COMPOSABLE.ts) | Composable Nuxt | ⭐⭐ |
| [RECAP_GEOLOCALISATION.md](RECAP_GEOLOCALISATION.md) | Récapitulatif | ⭐ |
| [FICHIERS_CREES.md](FICHIERS_CREES.md) | Liste fichiers | ⭐ |

---

## 🔍 Vérification après installation

### Dans tinker

```bash
php artisan tinker
```

```php
// Vérifier les compteurs
DB::table('regions')->count()    // → 1
DB::table('provinces')->count()   // → 9
DB::table('communes')->count()    // → 84

// Vérifier les coordonnées GPS
DB::table('communes')
    ->whereNotNull('latitude')
    ->whereNotNull('longitude')
    ->count()  // → 84 (toutes ont des coordonnées)

// Afficher une commune avec GPS
DB::table('communes')
    ->where('name_fr', 'Casablanca-Anfa')
    ->first()

// Tester la recherche par proximité
DB::table('communes')
    ->selectRaw(
        '*, (6371 * acos(cos(radians(?)) * cos(radians(latitude)) *
        cos(radians(longitude) - radians(?)) + sin(radians(?)) *
        sin(radians(latitude)))) AS distance',
        [33.5731, -7.5898, 33.5731]
    )
    ->having('distance', '<', 5)
    ->orderBy('distance')
    ->get()
```

---

## 🎯 Cas d'usage pour ARDOCCO

### 1. **Recherche d'annonces immobilières**
```
"Trouver tous les appartements dans un rayon de 5km autour de Casablanca-Anfa"
```

### 2. **Filtres géographiques**
```
Région → Province → Commune (sélecteur hiérarchique)
```

### 3. **Carte interactive**
```
Afficher toutes les annonces sur une carte Leaflet/Google Maps
```

### 4. **Suggestions intelligentes**
```
Autocomplete basé sur la position GPS de l'utilisateur
```

### 5. **Statistiques par zone**
```
Prix moyen par m² par commune/province
```

---

## 🐛 Problèmes courants

### ❌ "Column not found: latitude"
**Solution** : Exécutez la migration
```bash
php artisan migrate
```

### ❌ API ne répond pas
**Solution** : Démarrez le serveur Laravel
```bash
php artisan serve
```

### ❌ Coordonnées NULL
**Solution** : Exécutez le seeder
```bash
php artisan db:seed --class=CasablancaSettatGeoSeeder
```

---

## 🎓 Ressources

- [Formule de Haversine](https://fr.wikipedia.org/wiki/Formule_de_haversine) - Calcul de distance GPS
- [PostGIS](https://postgis.net/) - Extension PostgreSQL géospatiale
- [Leaflet.js](https://leafletjs.com/) - Bibliothèque de cartes
- [GeoJSON](https://geojson.org/) - Format d'échange de données géographiques

---

## 📊 Structure des données

```
Region (Casablanca-Settat)
  ├── Code: CS
  ├── GPS: 33.5731, -7.5898
  │
  └── Provinces (9)
      ├── Casablanca (CAS)
      │   ├── GPS: 33.5731, -7.5898
      │   └── Communes: 20
      │       ├── Casablanca-Anfa (urbaine)
      │       ├── Anfa (urbaine)
      │       ├── Aïn Chock (urbaine)
      │       └── ...
      │
      ├── Mohammedia (MOH)
      │   ├── GPS: 33.6864, -7.3833
      │   └── Communes: 12
      │
      └── ... 7 autres provinces
```

---

## ⚙️ Configuration optionnelle

### Activer PostGIS (PostgreSQL)

Dans la migration `add_coordinates_to_location_tables.php`, décommentez :

```php
DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');
```

Puis relancez :
```bash
php artisan migrate:rollback --step=1
php artisan migrate
```

### Activer le cache

Dans `GeoLocationController.php` :

```php
use Illuminate\Support\Facades\Cache;

public function regions()
{
    return Cache::remember('geo.regions', 3600, function () {
        return DB::table('regions')->get();
    });
}
```

---

## 🎉 Félicitations !

Vous avez maintenant un **système de géolocalisation complet et opérationnel** pour ARDOCCO.

**Prochaines étapes suggérées** :
1. ✅ Intégrer dans le frontend Nuxt 3
2. ✅ Ajouter une carte interactive
3. ✅ Implémenter la recherche géolocalisée d'annonces
4. ✅ Ajouter les autres régions du Maroc
5. ✅ Optimiser avec Redis cache

---

**Questions ?** Consultez la documentation complète dans les fichiers `.md`

**Projet** : ARDOCCO - Plateforme immobilière Maroc
**Version** : 1.0
**Date** : 2026-01-19

---

```
 _____                                 _
|  __ \                               | |
| |  \/ ___  ___        ___  ___  _ __| |_
| | __ / _ \/ _ \______/ __|/ _ \| '__| __|
| |_\ \  __/ (_) |_____\__ \ (_) | |  | |_
 \____/\___|\___/      |___/\___/|_|   \__|

```

**🗺️ Système de géolocalisation opérationnel !**
