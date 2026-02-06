#!/bin/bash

# ============================================
# Script de test - Géolocalisation ARDOCCO
# ============================================

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🧪 TESTS GÉOLOCALISATION - ARDOCCO"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

BASE_URL="http://localhost:8000/api"

# Couleurs
GREEN='\033[0;32m'
RED='\033[0;31m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Fonction de test
test_endpoint() {
    local name="$1"
    local url="$2"

    echo -e "${BLUE}▶ Test: ${name}${NC}"
    echo "  URL: ${url}"

    response=$(curl -s -w "\n%{http_code}" "${url}")
    http_code=$(echo "$response" | tail -n1)
    body=$(echo "$response" | head -n-1)

    if [ "$http_code" = "200" ]; then
        echo -e "  ${GREEN}✓ Status: ${http_code} OK${NC}"

        # Vérifier si c'est du JSON valide
        if echo "$body" | jq empty 2>/dev/null; then
            echo -e "  ${GREEN}✓ JSON valide${NC}"

            # Afficher un extrait de la réponse
            success=$(echo "$body" | jq -r '.success // "N/A"')
            total=$(echo "$body" | jq -r '.total // .data.communes.total // "N/A"')

            if [ "$success" = "true" ]; then
                echo -e "  ${GREEN}✓ Success: true${NC}"
            fi

            if [ "$total" != "N/A" ]; then
                echo -e "  ${GREEN}✓ Total items: ${total}${NC}"
            fi
        else
            echo -e "  ${RED}✗ Réponse n'est pas du JSON valide${NC}"
        fi
    else
        echo -e "  ${RED}✗ Status: ${http_code} FAILED${NC}"
        echo "  Réponse: ${body}"
    fi

    echo ""
}

# ============================================
# TESTS
# ============================================

echo "1️⃣  Test de connexion API"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
test_endpoint "Ping API" "${BASE_URL}/ping"

echo "2️⃣  Statistiques globales"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
test_endpoint "Stats" "${BASE_URL}/geo/stats"

echo "3️⃣  Régions"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
test_endpoint "Liste des régions" "${BASE_URL}/geo/regions"

echo "4️⃣  Provinces"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
test_endpoint "Provinces de Casablanca-Settat" "${BASE_URL}/geo/provinces/CS"

echo "5️⃣  Communes"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
test_endpoint "Communes de Casablanca" "${BASE_URL}/geo/communes/CAS"

echo "6️⃣  Recherche par proximité"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
test_endpoint "Communes dans 10km" "${BASE_URL}/geo/nearby?latitude=33.5731&longitude=-7.5898&radius=10"

echo "7️⃣  Recherche par nom"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
test_endpoint "Recherche 'casa'" "${BASE_URL}/geo/search?q=casa"

echo "8️⃣  Export complet"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
test_endpoint "Export Casablanca-Settat" "${BASE_URL}/geo/export/casablanca-settat"

# ============================================
# RÉSUMÉ
# ============================================

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅ TESTS TERMINÉS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "Pour des tests plus détaillés, utilisez:"
echo "  • curl ${BASE_URL}/geo/stats | jq"
echo "  • curl '${BASE_URL}/geo/nearby?latitude=33.5731&longitude=-7.5898&radius=5' | jq"
echo ""
echo "Documentation complète :"
echo "  • GEOLOCALISATION_GUIDE.md"
echo "  • API_EXAMPLES.md"
echo ""
