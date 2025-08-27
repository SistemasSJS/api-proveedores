# ============================================================================
# Laravel Queue Worker Supervisor para Windows Server
# ============================================================================
# Este script supervisa el Queue Worker de Laravel y lo reinicia 
# automaticamente en caso de fallas
# ============================================================================

param(
    [string]$ProjectPath = $null,
    [int]$MaxRetries = 5,
    [int]$RestartDelay = 10,
    [int]$MaxTime = 3600,
    [int]$Timeout = 1800,
    [int]$Memory = 512,
    [int]$Sleep = 3,
    [int]$Tries = 3
)

# Detectar automaticamente el directorio del proyecto si no se especifica
if (-not $ProjectPath) {
    # Obtener el directorio donde esta ubicado este script
    $ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Definition
    
    # Primero verificar si el script esta en la raiz del proyecto Laravel
    if (Test-Path (Join-Path $ScriptDir "artisan")) {
        # El script esta en la raiz del proyecto
        $ProjectPath = $ScriptDir
    } elseif (Test-Path (Join-Path (Split-Path -Parent $ScriptDir) "artisan")) {
        # El script esta en una subcarpeta, el proyecto esta un nivel arriba
        $ProjectPath = Split-Path -Parent $ScriptDir
    } else {
        # No se pudo detectar automaticamente
        Write-Host "ERROR: No se pudo detectar automaticamente el directorio del proyecto Laravel." -ForegroundColor Red
        Write-Host "Directorio actual del script: $ScriptDir" -ForegroundColor Yellow
        Write-Host "Por favor, especifica la ruta usando el parametro -ProjectPath" -ForegroundColor Red
        Write-Host "Ejemplo: .\queue-worker-supervisor.ps1 -ProjectPath 'C:\mi\proyecto\laravel'" -ForegroundColor Yellow
        exit 1
    }
}

# Configuracion
$logPath = "$ProjectPath\storage\logs\queue-supervisor.log"
$retryCount = 0
$startTime = Get-Date

# Funcion para escribir logs con timestamp
function Write-Log {
    param(
        [string]$Message,
        [string]$Level = "INFO"
    )
    
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $logMessage = "[$timestamp] [$Level] $Message"
    
    # Escribir a consola
    Write-Host $logMessage -ForegroundColor $(
        switch ($Level) {
            "ERROR" { "Red" }
            "WARNING" { "Yellow" }
            "SUCCESS" { "Green" }
            default { "White" }
        }
    )
    
    # Escribir a archivo de log
    try {
        # Crear directorio de logs si no existe
        $logDir = Split-Path $logPath -Parent
        if (-not (Test-Path $logDir)) {
            New-Item -ItemType Directory -Path $logDir -Force | Out-Null
        }
        
        $logMessage | Out-File -FilePath $logPath -Append -Encoding UTF8
    } catch {
        Write-Host "ERROR: No se pudo escribir al log: $_" -ForegroundColor Red
    }
}

# Funcion para validar el entorno
function Test-Environment {
    Write-Log "Validando entorno de Laravel Queue Worker..." "INFO"
    
    # Verificar que existe el directorio del proyecto
    if (-not (Test-Path $ProjectPath)) {
        Write-Log "ERROR: No existe el directorio del proyecto: $ProjectPath" "ERROR"
        return $false
    }
    
    # Verificar que existe artisan
    $artisanPath = Join-Path $ProjectPath "artisan"
    if (-not (Test-Path $artisanPath)) {
        Write-Log "ERROR: No se encontro el archivo artisan en: $artisanPath" "ERROR"
        return $false
    }
    
    # Verificar que PHP esta disponible
    try {
        $phpVersion = & php --version 2>$null
        if ($LASTEXITCODE -eq 0) {
            Write-Log "PHP disponible: $($phpVersion[0])" "SUCCESS"
        } else {
            Write-Log "ERROR: PHP no esta disponible en el PATH" "ERROR"
            return $false
        }
    } catch {
        Write-Log "ERROR: No se pudo ejecutar PHP: $_" "ERROR"
        return $false
    }
    
    return $true
}

# Funcion principal de supervision
function Start-QueueWorkerSupervisor {
    Write-Log "====================================================================" "INFO"
    Write-Log "Iniciando Laravel Queue Worker Supervisor" "INFO"
    Write-Log "Proyecto: $ProjectPath" "INFO"
    Write-Log "Max Reintentos: $MaxRetries" "INFO"
    Write-Log "Delay entre reintentos: $RestartDelay segundos" "INFO"
    Write-Log "====================================================================" "INFO"
    
    # Validar entorno
    if (-not (Test-Environment)) {
        Write-Log "ERROR: Validacion del entorno fallo. Terminando supervisor." "ERROR"
        exit 1
    }
    
    # Loop principal de supervision
    while ($true) {
        try {
            Write-Log "Iniciando Queue Worker (intento: $($retryCount + 1)/$MaxRetries)" "INFO"
            
            # Cambiar al directorio del proyecto
            Set-Location $ProjectPath
            
            # Construir argumentos para el queue worker
            $arguments = @(
                "artisan",
                "queue:work",
                "database",
                "--sleep=$Sleep",
                "--tries=$Tries",
                "--max-time=$MaxTime",
                "--timeout=$Timeout",
                "--memory=$Memory",
                "--verbose"
            )
            
            Write-Log "Ejecutando: php $($arguments -join ' ')" "INFO"
            
            # Iniciar el proceso del queue worker
            $process = Start-Process -FilePath "php" -ArgumentList $arguments -PassThru -NoNewWindow -Wait
            
            # Verificar el codigo de salida
            if ($process.ExitCode -eq 0) {
                Write-Log "Queue Worker termino normalmente (codigo: $($process.ExitCode))" "SUCCESS"
                $retryCount = 0  # Reset counter on successful run
                
                # Si termino normalmente, esperar un poco antes de reiniciar
                Write-Log "Esperando 5 segundos antes de reiniciar..." "INFO"
                Start-Sleep -Seconds 5
                
            } else {
                Write-Log "Queue Worker termino con codigo de error: $($process.ExitCode)" "ERROR"
                $retryCount++
                
                if ($retryCount -ge $MaxRetries) {
                    Write-Log "CRITICO: Maximo numero de reintentos alcanzado ($MaxRetries). Deteniendo supervisor." "ERROR"
                    exit 1
                }
                
                Write-Log "Esperando $RestartDelay segundos antes de reintentar..." "WARNING"
                Start-Sleep -Seconds $RestartDelay
            }
            
        } catch {
            Write-Log "EXCEPCION: Error inesperado: $_" "ERROR"
            $retryCount++
            
            if ($retryCount -ge $MaxRetries) {
                Write-Log "CRITICO: Maximo numero de reintentos por excepciones alcanzado. Deteniendo supervisor." "ERROR"
                exit 1
            }
            
            Write-Log "Esperando $RestartDelay segundos antes de reintentar..." "WARNING"
            Start-Sleep -Seconds $RestartDelay
        }
    }
}

# Manejar Ctrl+C para terminar gracefully
$null = Register-ObjectEvent -InputObject ([Console]) -EventName CancelKeyPress -Action {
    Write-Log "Senal de interrupcion recibida. Terminando supervisor..." "WARNING"
    exit 0
}

# Iniciar el supervisor
try {
    Start-QueueWorkerSupervisor
} catch {
    Write-Log "ERROR CRITICO: El supervisor fallo: $_" "ERROR"
    exit 1
} finally {
    Write-Log "Laravel Queue Worker Supervisor terminado." "INFO"
}
