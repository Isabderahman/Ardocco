# 📍 Système de Géolocalisation - ARDOCCO

> Système complet de géolocalisation pour les régions, provinces et communes du Maroc avec coordonnées GPS réelles.

## 🚀 Installation Rapide

```bash
# 1. Exécuter la migration
php artisan migrate

# 2. Exécuter le seeder
php artisan db:seed --class=CasablancaSettatGeoSeeder

# 3. Tester l'API
./test_geolocalisation.sh
```

**✅ C'EST TOUT ! En 3 commandes, votre système est opérationnel.**

---

## 📊 Données incluses

- ✅ **1 région** : Casablanca-Settat
- ✅ **9 provinces** : Casablanca, Mohammedia, El Jadida, Nouaceur, Settat, Berrechid, Sidi Bennour, Médiouna
- ✅ **84 communes** : Toutes avec coordonnées GPS réelles (urbaines + rurales)

---

## 🔌 API Endpoints

| Endpoint | Description |
|----------|-------------|
| `GET /api/geo/stats` | Statistiques globales |
| `GET /api/geo/regions` | Liste des régions |
| `GET /api/geo/provinces/{code}` | Provinces d'une région |
| `GET /api/geo/communes/{code}` | Communes d'une province |
| `GET /api/geo/nearby` | Recherche par proximité GPS |
| `GET /api/geo/search` | Recherche par nom |

**Exemple** :
```bash
curl "http://localhost:8000/api/geo/nearby?latitude=33.5731&longitude=-7.5898&radius=10"
```

---

## 📚 Documentation

| Fichier | Contenu |
|---------|---------|
| [GEOLOCALISATION_GUIDE.md](GEOLOCALISATION_GUIDE.md) | Guide complet d'utilisation |
| [COMMANDES_GEOLOCALISATION.md](COMMANDES_GEOLOCALISATION.md) | Toutes les commandes utiles |
| [API_EXAMPLES.md](API_EXAMPLES.md) | Exemples d'utilisation de l'API |
| [RECAP_GEOLOCALISATION.md](RECAP_GEOLOCALISATION.md) | Récapitulatif complet |
| [FICHIERS_CREES.md](FICHIERS_CREES.md) | Liste des fichiers créés |

---

## 🧪 Tests

### Automatique
```bash
./test_geolocalisation.sh
```

### Manuel
```bash
# Test de connexion
curl http://localhost:8000/api/ping

# Statistiques
curl http://localhost:8000/api/geo/stats

# Recherche de communes à proximité
curl "http://localhost:8000/api/geo/nearby?latitude=33.5731&longitude=-7.5898&radius=5"
```

---

## 🗺️ Utilisation avec Leaflet.js

```javascript
// Charger les communes
fetch('http://localhost:8000/api/geo/export/casablanca-settat')
  .then(response => response.json())
  .then(data => {
    data.data.all.forEach(commune => {
      L.marker([commune.lat, commune.lng])
        .bindPopup(`<b>${commune.name_fr}</b>`)
        .addTo(map);
    });
  });
```

---

## 💡 Exemples d'utilisation

### 1. Recherche de communes à proximité d'une position GPS

```php
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

### 2. Autocomplete de communes

```javascript
async function searchCommunes(query) {
  const response = await fetch(
    `http://localhost:8000/api/geo/search?q=${query}`
  );
  const data = await response.json();
  return data.data;
}
```

### 3. Sélecteur hiérarchique Région → Province → Commune

```vue
<template>
  <div>
    <select v-model="selectedRegion" @change="loadProvinces">
      <option value="">Sélectionner une région</option>
      <option v-for="region in regions" :value="region.code">
        {{ region.name_fr }}
      </option>
    </select>

    <select v-model="selectedProvince" @change="loadCommunes">
      <option value="">Sélectionner une province</option>
      <option v-for="province in provinces" :value="province.code">
        {{ province.name_fr }}
      </option>
    </select>

    <select v-model="selectedCommune">
      <option value="">Sélectionner une commune</option>
      <option v-for="commune in communes" :value="commune.id">
        {{ commune.name_fr }}
      </option>
    </select>
  </div>
</template>
```

---

## 🎯 Cas d'usage pour ARDOCCO

### 1. Recherche d'annonces par proximité
Trouver les biens immobiliers dans un rayon de X km autour d'une position GPS.

### 2. Filtres géographiques
Permettre aux utilisateurs de filtrer par région → province → commune.

### 3. Carte interactive
Afficher les annonces sur une carte avec marqueurs.

### 4. Suggestions géolocalisées
Autocomplete intelligent basé sur la position de l'utilisateur.

### 5. Statistiques par zone
Analyser les prix moyens par commune, province ou région.

---

## 📦 Structure des tables

### Table `communes`
```
id          → UUID
province_id → UUID (FK)
name_fr     → VARCHAR
name_ar     → VARCHAR
type        → ENUM('urbaine', 'rurale')
code_postal → VARCHAR
latitude    → DECIMAL(10,8) ← NOUVEAU
longitude   → DECIMAL(11,8) ← NOUVEAU
```

### Index créés
- `communes_coordinates_index` (latitude, longitude)
- `provinces_coordinates_index` (latitude, longitude)
- `regions_coordinates_index` (latitude, longitude)

---

## 🔧 Configuration

### Activer PostGIS (optionnel)

Dans la migration, décommentez :
```php
DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');
```

### Activer earthdistance (optionnel)

Dans la migration, décommentez :
```php
DB::statement('CREATE EXTENSION IF NOT EXISTS cube');
DB::statement('CREATE EXTENSION IF NOT EXISTS earthdistance');
```

---

## 🐛 Dépannage

### Erreur : "Column not found: latitude"
```bash
php artisan migrate
```

### Coordonnées NULL
```bash
php artisan db:seed --class=CasablancaSettatGeoSeeder
```

### API ne répond pas
```bash
# Vérifier que le serveur Laravel est démarré
php artisan serve

# Tester
curl http://localhost:8000/api/ping
```

---

## 📈 Prochaines étapes

1. ✅ Ajouter les autres régions du Maroc
2. ✅ Créer des modèles Eloquent
3. ✅ Implémenter le cache Redis
4. ✅ Intégrer dans le module d'annonces
5. ✅ Créer l'interface frontend avec carte

---

## 🎓 Ressources

- [Documentation complète](GEOLOCALISATION_GUIDE.md)
- [Exemples API](API_EXAMPLES.md)
- [Formule de Haversine](https://fr.wikipedia.org/wiki/Formule_de_haversine)
- [PostGIS](https://postgis.net/)
- [Leaflet.js](https://leafletjs.com/)

---

## ✨ Caractéristiques

- ✅ Coordonnées GPS réelles (pas de 0.0)
- ✅ Précision de 1.1mm (8 décimales)
- ✅ API REST complète
- ✅ Recherche géospatiale optimisée
- ✅ Support multilingue (FR/AR)
- ✅ Documentation exhaustive
- ✅ Prêt pour production

---

**Projet** : ARDOCCO - Plateforme immobilière Maroc
**Version** : 1.0
**Date** : 2026-01-19

**🎉 Système de géolocalisation opérationnel en moins de 2 minutes !**
