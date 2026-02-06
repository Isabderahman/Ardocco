# 🔌 Exemples d'utilisation de l'API Géolocalisation

## 📋 Base URL

```
http://localhost:8000/api/geo
```

---

## 🎯 Endpoints disponibles

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/ping` | Vérifier que l'API fonctionne |
| GET | `/api/geo/stats` | Statistiques globales |
| GET | `/api/geo/regions` | Liste toutes les régions |
| GET | `/api/geo/provinces/{code}` | Provinces d'une région |
| GET | `/api/geo/communes/{code}` | Communes d'une province |
| GET | `/api/geo/nearby` | Recherche par proximité GPS |
| GET | `/api/geo/search` | Recherche par nom |
| GET | `/api/geo/commune/{id}` | Détails d'une commune |
| GET | `/api/geo/export/casablanca-settat` | Export complet |

---

## 📝 Exemples de requêtes

### 1. Test de connexion API

```bash
curl http://localhost:8000/api/ping
```

**Réponse** :
```json
{
  "success": true,
  "message": "API is running",
  "timestamp": "2026-01-19 15:30:00"
}
```

---

### 2. Statistiques globales

```bash
curl http://localhost:8000/api/geo/stats
```

**Réponse** :
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
    },
    "with_gps": {
      "regions": 1,
      "provinces": 9,
      "communes": 84
    }
  }
}
```

---

### 3. Liste des régions

```bash
curl http://localhost:8000/api/geo/regions
```

**Réponse** :
```json
{
  "success": true,
  "total": 1,
  "data": [
    {
      "id": "uuid...",
      "name_fr": "Casablanca-Settat",
      "name_ar": "الدار البيضاء-سطات",
      "code": "CS",
      "latitude": "33.57310000",
      "longitude": "-7.58980000"
    }
  ]
}
```

---

### 4. Provinces d'une région

```bash
curl http://localhost:8000/api/geo/provinces/CS
```

**Réponse** :
```json
{
  "success": true,
  "region_code": "CS",
  "total": 9,
  "data": [
    {
      "id": "uuid...",
      "name_fr": "Casablanca",
      "name_ar": "الدار البيضاء",
      "code": "CAS",
      "latitude": "33.57310000",
      "longitude": "-7.58980000",
      "region_name": "Casablanca-Settat"
    },
    {
      "id": "uuid...",
      "name_fr": "Mohammedia",
      "name_ar": "المحمدية",
      "code": "MOH",
      "latitude": "33.68640000",
      "longitude": "-7.38330000",
      "region_name": "Casablanca-Settat"
    }
    // ... autres provinces
  ]
}
```

---

### 5. Communes d'une province

```bash
curl http://localhost:8000/api/geo/communes/CAS
```

**Réponse** :
```json
{
  "success": true,
  "province_code": "CAS",
  "total": 20,
  "urbaines": 17,
  "rurales": 3,
  "data": {
    "all": [
      {
        "id": "uuid...",
        "name_fr": "Casablanca-Anfa",
        "name_ar": "الدار البيضاء أنفا",
        "type": "urbaine",
        "code_postal": "20000",
        "latitude": "33.57310000",
        "longitude": "-7.58980000",
        "province_name": "Casablanca",
        "province_code": "CAS"
      }
      // ... autres communes
    ],
    "by_type": {
      "urbaines": [ /* ... */ ],
      "rurales": [ /* ... */ ]
    }
  }
}
```

---

### 6. Recherche par proximité GPS

#### 6a. Recherche basique (rayon 10 km par défaut)

```bash
curl "http://localhost:8000/api/geo/nearby?latitude=33.5731&longitude=-7.5898"
```

#### 6b. Avec rayon personnalisé (5 km)

```bash
curl "http://localhost:8000/api/geo/nearby?latitude=33.5731&longitude=-7.5898&radius=5"
```

#### 6c. Filtrer par type (urbaines uniquement)

```bash
curl "http://localhost:8000/api/geo/nearby?latitude=33.5731&longitude=-7.5898&radius=15&type=urbaine"
```

#### 6d. Limiter les résultats

```bash
curl "http://localhost:8000/api/geo/nearby?latitude=33.5731&longitude=-7.5898&radius=20&limit=10"
```

**Réponse** :
```json
{
  "success": true,
  "search_point": {
    "latitude": 33.5731,
    "longitude": -7.5898
  },
  "radius_km": 10,
  "total": 15,
  "data": [
    {
      "id": "uuid...",
      "name_fr": "Casablanca-Anfa",
      "name_ar": "الدار البيضاء أنفا",
      "type": "urbaine",
      "code_postal": "20000",
      "latitude": "33.57310000",
      "longitude": "-7.58980000",
      "province_name": "Casablanca",
      "province_code": "CAS",
      "distance": 0.00  // en km
    },
    {
      "id": "uuid...",
      "name_fr": "Anfa",
      "name_ar": "أنفا",
      "type": "urbaine",
      "code_postal": "20050",
      "latitude": "33.58920000",
      "longitude": "-7.65480000",
      "province_name": "Casablanca",
      "province_code": "CAS",
      "distance": 5.42  // en km
    }
    // ... triés par distance croissante
  ]
}
```

---

### 7. Recherche par nom (autocomplete)

#### 7a. Recherche simple

```bash
curl "http://localhost:8000/api/geo/search?q=casa"
```

#### 7b. Recherche avec tri par proximité

```bash
curl "http://localhost:8000/api/geo/search?q=sidi&latitude=33.5731&longitude=-7.5898"
```

#### 7c. Limiter les résultats

```bash
curl "http://localhost:8000/api/geo/search?q=oulad&limit=5"
```

**Réponse** :
```json
{
  "success": true,
  "query": "casa",
  "total": 3,
  "data": [
    {
      "id": "uuid...",
      "name_fr": "Casablanca",
      "name_ar": "الدار البيضاء",
      "type": "urbaine",
      "code_postal": "20000",
      "latitude": "33.57310000",
      "longitude": "-7.58980000",
      "province_name": "Casablanca",
      "province_code": "CAS",
      "distance": 0.00  // si lat/lng fournis
    },
    {
      "id": "uuid...",
      "name_fr": "Casablanca-Anfa",
      "name_ar": "الدار البيضاء أنفا",
      "type": "urbaine",
      "code_postal": "20000",
      "latitude": "33.57310000",
      "longitude": "-7.58980000",
      "province_name": "Casablanca",
      "province_code": "CAS",
      "distance": 0.00
    }
  ]
}
```

---

### 8. Détails d'une commune

```bash
curl http://localhost:8000/api/geo/commune/{uuid}
```

**Réponse** :
```json
{
  "success": true,
  "data": {
    "id": "uuid...",
    "name_fr": "Casablanca-Anfa",
    "name_ar": "الدار البيضاء أنفا",
    "type": "urbaine",
    "code_postal": "20000",
    "latitude": "33.57310000",
    "longitude": "-7.58980000",
    "province_id": "uuid...",
    "province_name": "Casablanca",
    "province_name_ar": "الدار البيضاء",
    "province_code": "CAS",
    "province_latitude": "33.57310000",
    "province_longitude": "-7.58980000",
    "region_name": "Casablanca-Settat",
    "region_name_ar": "الدار البيضاء-سطات",
    "region_code": "CS",
    "created_at": "2026-01-19 15:00:00",
    "updated_at": "2026-01-19 15:00:00"
  }
}
```

---

### 9. Export complet Casablanca-Settat

```bash
curl http://localhost:8000/api/geo/export/casablanca-settat
```

**Réponse** :
```json
{
  "success": true,
  "region": "Casablanca-Settat",
  "total_communes": 84,
  "data": {
    "all": [ /* toutes les communes */ ],
    "by_province": {
      "Casablanca": {
        "total": 20,
        "communes": [ /* ... */ ]
      },
      "Mohammedia": {
        "total": 12,
        "communes": [ /* ... */ ]
      }
      // ... autres provinces
    }
  }
}
```

---

## 🔧 Utilisation avec JavaScript (fetch)

### Exemple 1 : Recherche de communes à proximité

```javascript
async function findNearbyCommunes(lat, lng, radius = 10) {
  const response = await fetch(
    `http://localhost:8000/api/geo/nearby?latitude=${lat}&longitude=${lng}&radius=${radius}`
  );
  const data = await response.json();

  if (data.success) {
    console.log(`Trouvé ${data.total} communes dans un rayon de ${radius}km`);
    data.data.forEach(commune => {
      console.log(`- ${commune.name_fr} (${commune.distance.toFixed(2)} km)`);
    });
  }
}

// Utilisation
findNearbyCommunes(33.5731, -7.5898, 5);
```

### Exemple 2 : Autocomplete de communes

```javascript
async function searchCommunes(query, userLat = null, userLng = null) {
  let url = `http://localhost:8000/api/geo/search?q=${encodeURIComponent(query)}`;

  if (userLat && userLng) {
    url += `&latitude=${userLat}&longitude=${userLng}`;
  }

  const response = await fetch(url);
  const data = await response.json();

  return data.success ? data.data : [];
}

// Utilisation
const results = await searchCommunes('casa', 33.5731, -7.5898);
console.log(results);
```

### Exemple 3 : Charger toutes les provinces

```javascript
async function loadProvinces() {
  const response = await fetch('http://localhost:8000/api/geo/provinces/CS');
  const data = await response.json();

  if (data.success) {
    return data.data.map(p => ({
      code: p.code,
      name: p.name_fr,
      coordinates: [p.latitude, p.longitude]
    }));
  }
}
```

---

## 🗺️ Intégration avec Leaflet.js

```html
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        #map { height: 600px; }
    </style>
</head>
<body>
    <div id="map"></div>
    <script>
        // Initialiser la carte
        const map = L.map('map').setView([33.5731, -7.5898], 10);

        // Fond de carte
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(map);

        // Charger les communes depuis l'API
        fetch('http://localhost:8000/api/geo/export/casablanca-settat')
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    result.data.all.forEach(commune => {
                        const marker = L.marker([commune.lat, commune.lng])
                            .bindPopup(`
                                <b>${commune.name_fr}</b><br>
                                ${commune.name_ar}<br>
                                Type: ${commune.type}<br>
                                Province: ${commune.province}
                            `);

                        marker.addTo(map);
                    });
                }
            });
    </script>
</body>
</html>
```

---

## 🧪 Tests avec cURL

### Test complet

```bash
# 1. Vérifier l'API
curl http://localhost:8000/api/ping

# 2. Stats
curl http://localhost:8000/api/geo/stats

# 3. Liste des régions
curl http://localhost:8000/api/geo/regions

# 4. Provinces de Casablanca-Settat
curl http://localhost:8000/api/geo/provinces/CS

# 5. Communes de Casablanca
curl http://localhost:8000/api/geo/communes/CAS

# 6. Communes dans un rayon de 5 km autour de Casablanca
curl "http://localhost:8000/api/geo/nearby?latitude=33.5731&longitude=-7.5898&radius=5"

# 7. Recherche "sidi" trié par proximité
curl "http://localhost:8000/api/geo/search?q=sidi&latitude=33.5731&longitude=-7.5898"

# 8. Export complet
curl http://localhost:8000/api/geo/export/casablanca-settat > casablanca_settat.json
```

---

## 📱 Intégration Nuxt 3 (Frontend)

### Composable pour l'API Géolocalisation

```typescript
// composables/useGeoAPI.ts
export const useGeoAPI = () => {
  const config = useRuntimeConfig()
  const baseURL = config.public.apiBaseURL || 'http://localhost:8000/api'

  const getStats = async () => {
    const { data } = await useFetch(`${baseURL}/geo/stats`)
    return data.value
  }

  const getRegions = async () => {
    const { data } = await useFetch(`${baseURL}/geo/regions`)
    return data.value
  }

  const getProvinces = async (regionCode: string) => {
    const { data } = await useFetch(`${baseURL}/geo/provinces/${regionCode}`)
    return data.value
  }

  const getCommunes = async (provinceCode: string) => {
    const { data } = await useFetch(`${baseURL}/geo/communes/${provinceCode}`)
    return data.value
  }

  const findNearby = async (lat: number, lng: number, radius = 10, type?: string) => {
    const params = new URLSearchParams({
      latitude: lat.toString(),
      longitude: lng.toString(),
      radius: radius.toString(),
      ...(type && { type })
    })

    const { data } = await useFetch(`${baseURL}/geo/nearby?${params}`)
    return data.value
  }

  const searchCommunes = async (query: string, lat?: number, lng?: number) => {
    const params = new URLSearchParams({ q: query })
    if (lat && lng) {
      params.append('latitude', lat.toString())
      params.append('longitude', lng.toString())
    }

    const { data } = await useFetch(`${baseURL}/geo/search?${params}`)
    return data.value
  }

  return {
    getStats,
    getRegions,
    getProvinces,
    getCommunes,
    findNearby,
    searchCommunes
  }
}
```

### Utilisation dans un composant

```vue
<script setup>
const { findNearby } = useGeoAPI()
const communes = ref([])

onMounted(async () => {
  const result = await findNearby(33.5731, -7.5898, 10)
  if (result?.success) {
    communes.value = result.data
  }
})
</script>

<template>
  <div>
    <h2>Communes à proximité</h2>
    <ul>
      <li v-for="commune in communes" :key="commune.id">
        {{ commune.name_fr }} - {{ commune.distance.toFixed(2) }} km
      </li>
    </ul>
  </div>
</template>
```

---

## ⚡ Optimisation des performances

### 1. Mise en cache (Laravel)

Ajoutez dans le contrôleur :

```php
use Illuminate\Support\Facades\Cache;

public function regions()
{
    return Cache::remember('geo.regions', 3600, function () {
        return DB::table('regions')
            ->select('id', 'name_fr', 'name_ar', 'code', 'latitude', 'longitude')
            ->orderBy('name_fr')
            ->get();
    });
}
```

### 2. Rate limiting (routes/api.php)

```php
Route::middleware('throttle:60,1')->group(function () {
    // Routes limitées à 60 requêtes par minute
});
```

---

**Projet** : ARDOCCO
**Documentation API Géolocalisation**
**Version** : 1.0
**Date** : 2026-01-19
