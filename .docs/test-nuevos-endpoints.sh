#!/bin/bash
# Script para probar los nuevos endpoints de asociación de proveedores
# API Proveedores - Los Mochis, Sinaloa, México

# Configuración
BASE_URL="http://localhost:8080/api"
API_KEY="7f2wnCyn7ctmTE7B3mrtDPKCPVF9z8pYseihsHA6"
EMPRESA_ID=14
PROVEEDOR_ID=1

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

echo -e "${CYAN}🚀 Probando los nuevos endpoints de asociación de proveedores...${NC}"
echo -e "${YELLOW}================================================${NC}"

# Función para hacer requests
test_endpoint() {
    local method=$1
    local url=$2
    local description=$3
    local data=$4
    
    echo -e "\n${GREEN}📡 $description${NC}"
    echo -e "   ${method} ${url}"
    
    if [ -n "$data" ]; then
        response=$(curl -s -w "\n%{http_code}" -X $method \
            -H "X-API-KEY: $API_KEY" \
            -H "Accept: application/json" \
            -H "Content-Type: application/json" \
            -d "$data" \
            "$url")
    else
        response=$(curl -s -w "\n%{http_code}" -X $method \
            -H "X-API-KEY: $API_KEY" \
            -H "Accept: application/json" \
            "$url")
    fi
    
    # Separar el cuerpo de la respuesta del código de estado
    http_code=$(echo "$response" | tail -n1)
    response_body=$(echo "$response" | head -n -1)
    
    if [[ $http_code -ge 200 && $http_code -lt 300 ]]; then
        echo -e "   ${GREEN}✅ SUCCESS ($http_code)${NC}"
        echo -e "   ${CYAN}Response:${NC}"
        echo "$response_body" | jq . 2>/dev/null || echo "$response_body"
    else
        echo -e "   ${RED}❌ ERROR ($http_code)${NC}"
        echo -e "   ${YELLOW}Response:${NC}"
        echo "$response_body" | jq . 2>/dev/null || echo "$response_body"
    fi
}

# Test 1: Listar proveedores asociados (endpoint existente)
test_endpoint "GET" "$BASE_URL/construcc/solicitudes-pago/empresa/$EMPRESA_ID/proveedores" "1. Listar proveedores asociados a empresa $EMPRESA_ID"

# Test 2: Listar proveedores NO asociados (nuevo endpoint)
test_endpoint "GET" "$BASE_URL/construcc/solicitudes-pago/empresa/$EMPRESA_ID/proveedores/no-asociados" "2. 🆕 Listar proveedores NO asociados a empresa $EMPRESA_ID"

# Test 3: Buscar empresas constructoras
test_endpoint "GET" "$BASE_URL/construcc/solicitudes-pago/empresas-constructoras/search?search=construcciones&limit=5" "3. Buscar empresas constructoras"

# Test 4: Asociar proveedor a empresa (nuevo endpoint)
association_data='{"proveedor_id": '$PROVEEDOR_ID'}'
test_endpoint "POST" "$BASE_URL/construcc/solicitudes-pago/empresa/$EMPRESA_ID/proveedores/asociar" "4. 🆕 Asociar proveedor $PROVEEDOR_ID a empresa $EMPRESA_ID" "$association_data"

# Test 5: Verificar que la asociación funcionó
test_endpoint "GET" "$BASE_URL/construcc/solicitudes-pago/empresa/$EMPRESA_ID/proveedores" "5. Verificar proveedores asociados después de la asociación"

# Test 6: Intentar asociar el mismo proveedor otra vez (debería fallar con 409)
test_endpoint "POST" "$BASE_URL/construcc/solicitudes-pago/empresa/$EMPRESA_ID/proveedores/asociar" "6. Intentar asociar el mismo proveedor nuevamente (debería fallar)" "$association_data"

# Test 7: Probar con empresa inexistente (debería fallar con 404)
test_endpoint "GET" "$BASE_URL/construcc/solicitudes-pago/empresa/999999/proveedores/no-asociados" "7. Probar con empresa inexistente (debería fallar)"

# Test 8: Probar con proveedor inexistente (debería fallar con 422)
invalid_provider_data='{"proveedor_id": 999999}'
test_endpoint "POST" "$BASE_URL/construcc/solicitudes-pago/empresa/$EMPRESA_ID/proveedores/asociar" "8. Probar con proveedor inexistente (debería fallar)" "$invalid_provider_data"

echo -e "\n${YELLOW}================================================${NC}"
echo -e "${CYAN}🎉 Pruebas completadas!${NC}"
echo -e "   Revisa los resultados arriba para verificar que todo funcione correctamente."
echo -e "   Los nuevos endpoints están marcados con 🆕"