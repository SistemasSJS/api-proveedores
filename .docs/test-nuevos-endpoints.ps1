# PowerShell Script para probar los nuevos endpoints de asociación de proveedores
# API Proveedores - Los Mochis, Sinaloa, México

# Configuración
$baseUrl = "http://localhost:8080/api"
$apiKey = "/%-!?=T35sT._22<1|:"
$empresaId = 14
$proveedorId = 1

# Headers comunes
$headers = @{
    "X-API-KEY" = $apiKey
    "Accept" = "application/json"
    "Content-Type" = "application/json"
}

Write-Host "🚀 Probando los nuevos endpoints de asociación de proveedores..." 
Write-Host "================================================" 

function Test-Endpoint {
    param(
        [string]$Method,
        [string]$Url,
        [string]$Description,
        [hashtable]$Body = $null
    )
    
    Write-Host "`n📡 $Description" 
    Write-Host "   $Method $Url" 
    
    try {
        if ($Body) {
            $jsonBody = $Body | ConvertTo-Json
            $response = Invoke-RestMethod -Uri $Url -Method $Method -Headers $headers -Body $jsonBody
        } else {
            $response = Invoke-RestMethod -Uri $Url -Method $Method -Headers $headers
        }
        
        Write-Host "   ✅ SUCCESS" 
        Write-Host "   Response:" 
        $response | ConvertTo-Json -Depth 3 | Write-Host
        return $response
    }
    catch {
        $statusCode = $_.Exception.Response.StatusCode
        $errorMessage = $_.Exception.Message
        Write-Host "   ❌ ERROR ($statusCode)" 
        Write-Host "   $errorMessage" 
        
        if ($_.Exception.Response) {
            try {
                $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
                $errorBody = $reader.ReadToEnd()
                Write-Host "   Response Body:" 
                Write-Host "   $errorBody" 
            }
            catch {
                Write-Host "   No se pudo leer el cuerpo de la respuesta de error" 
            }
        }
        return $null
    }
}

# Test 1: Listar proveedores asociados (endpoint existente)
Test-Endpoint -Method "GET" -Url "$baseUrl/construcc/solicitudes-pago/empresa/$empresaId/proveedores" -Description "1. Listar proveedores asociados a empresa $empresaId"

# Test 2: Listar proveedores NO asociados (nuevo endpoint)
Test-Endpoint -Method "GET" -Url "$baseUrl/construcc/solicitudes-pago/empresa/$empresaId/proveedores/no-asociados" -Description "2. 🆕 Listar proveedores NO asociados a empresa $empresaId"

# Test 3: Buscar empresas constructoras
Test-Endpoint -Method "GET" -Url "$baseUrl/construcc/solicitudes-pago/empresas-constructoras/search?search=construcciones&limit=5" -Description "3. Buscar empresas constructoras"

# Test 4: Asociar proveedor a empresa (nuevo endpoint)
$associationBody = @{
    "proveedor_id" = $proveedorId
}
Test-Endpoint -Method "POST" -Url "$baseUrl/construcc/solicitudes-pago/empresa/$empresaId/proveedores/asociar" -Description "4. 🆕 Asociar proveedor $proveedorId a empresa $empresaId" -Body $associationBody

# Test 5: Verificar que la asociación funcionó
Test-Endpoint -Method "GET" -Url "$baseUrl/construcc/solicitudes-pago/empresa/$empresaId/proveedores" -Description "5. Verificar proveedores asociados después de la asociación"

# Test 6: Intentar asociar el mismo proveedor otra vez (debería fallar con 409)
Test-Endpoint -Method "POST" -Url "$baseUrl/construcc/solicitudes-pago/empresa/$empresaId/proveedores/asociar" -Description "6. Intentar asociar el mismo proveedor nuevamente (debería fallar)" -Body $associationBody

# Test 7: Probar con empresa inexistente (debería fallar con 404)
Test-Endpoint -Method "GET" -Url "$baseUrl/construcc/solicitudes-pago/empresa/999999/proveedores/no-asociados" -Description "7. Probar con empresa inexistente (debería fallar)"

# Test 8: Probar con proveedor inexistente (debería fallar con 422)
$invalidProviderBody = @{
    "proveedor_id" = 999999
}
Test-Endpoint -Method "POST" -Url "$baseUrl/construcc/solicitudes-pago/empresa/$empresaId/proveedores/asociar" -Description "8. Probar con proveedor inexistente (debería fallar)" -Body $invalidProviderBody

Write-Host "`n================================================" 
Write-Host "🎉 Pruebas completadas!" 
Write-Host "   Revisa los resultados arriba para verificar que todo funcione correctamente." 
Write-Host "   Los nuevos endpoints están marcados con"
