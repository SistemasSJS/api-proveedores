{{-- Cabecera emisor: datos del proveedor; con tarjeta emisor, persona de contacto (tel/correo de la tarjeta). --}}
@php
    $emisorNombreUpper = static function (?string $text): string {
        $t = trim((string) ($text ?? ''));

        return $t === '' ? '' : mb_strtoupper($t, 'UTF-8');
    };

    $p = $presupuesto['proveedor'];
    $configId = (int) ($presupuesto['config_emisor_presupuesto_id'] ?? 0);
    $tarjetaActiva = $configId > 0;

    $emisorComercial = trim((string) ($p->nombre_comercial ?? ''));
    $emisorRazonSocial = trim((string) ($p->razon_social ?? ''));
    $emisorNombre =
        $emisorComercial !== ''
            ? $emisorComercial
            : ($emisorRazonSocial !== '' ? $emisorRazonSocial : 'Empresa Proveedora S.A. de C.V.');
    $emisorRfc = $p->rfc ?? null;

    if ($tarjetaActiva) {
        $emisorRazonSocialLinea = $emisorNombreUpper($presupuesto['empresa_emisora_nombre'] ?? null);
        $emisorPuestoLinea = $emisorNombreUpper($presupuesto['empresa_emisora_puesto'] ?? null);
        $emisorTel = trim((string) ($presupuesto['empresa_emisora_telefono'] ?? ''));
        $emisorEmail = trim((string) ($presupuesto['empresa_emisora_correo'] ?? ''));
        $mostrarDireccion = false;
    } else {
        $emisorRazonSocialLinea =
            $emisorRazonSocial !== '' && strcasecmp($emisorRazonSocial, $emisorNombre) !== 0
                ? $emisorRazonSocial
                : null;
        $emisorPuestoLinea = null;
        $emisorTel = trim((string) ($p->telefono ?? ''));
        $emisorEmail = trim((string) ($p->email ?? ''));
        $mostrarDireccion = true;
    }

    $emisorDireccion = trim((string) ($p->direccion_empresa ?? ''));
    $ciudad = trim((string) ($p->ciudad ?? ''));
    $estadoGeo = trim((string) ($p->estado ?? ''));
    $emisorCiudadEstado = '';
    if ($mostrarDireccion && ($ciudad !== '' || $estadoGeo !== '')) {
        $emisorCiudadEstado = $ciudad . ($ciudad !== '' && $estadoGeo !== '' ? ', ' : '') . $estadoGeo;
        if ($emisorCiudadEstado !== '') {
            $emisorCiudadEstado .= ', México';
        }
    }
@endphp
<div class="{{ $nameClass ?? 'company-header-name' }}">{{ $emisorNombre }}</div>
@if ($emisorRfc)
    <div class="{{ $lineClass }}">{{ $emisorRfc }}</div>
@endif
@if ($emisorRazonSocialLinea)
    <div class="{{ $lineClass }}">{{ $emisorRazonSocialLinea }}</div>
@endif
@if (!$headerCompact && $emisorPuestoLinea)
    <div class="{{ $lineClass }}">{{ $emisorPuestoLinea }}</div>
@endif
@if (!$headerCompact && $mostrarDireccion && $emisorDireccion !== '')
    <div class="{{ $lineClass }}">{{ $emisorDireccion }}</div>
@endif
@if (!$headerCompact && $mostrarDireccion && $emisorCiudadEstado !== '')
    <div class="{{ $lineClass }}">{{ $emisorCiudadEstado }}</div>
@endif
@if (!$headerCompact && $emisorTel !== '')
    <div class="{{ $lineClass }}">Tel. {{ $emisorTel }}</div>
@endif
@if (!$headerCompact && $emisorEmail !== '')
    <div class="{{ $lineClass }}">{{ $emisorEmail }}</div>
@endif
