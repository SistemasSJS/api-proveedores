{{-- Cabecera emisor: solo datos del proveedor (sin tarjeta de contacto). --}}
@php
    $emisorNombreUpper = static function (?string $text): string {
        $t = trim((string) ($text ?? ''));

        return $t === '' ? '' : mb_strtoupper($t, 'UTF-8');
    };

    $p = $presupuesto['proveedor'];
    $emisorComercial = trim((string) ($p->nombre_comercial ?? ''));
    $emisorRazonSocial = trim((string) ($p->razon_social ?? ''));
    $emisorNombre =
        $emisorComercial !== ''
            ? $emisorComercial
            : ($emisorRazonSocial !== '' ? $emisorRazonSocial : 'Empresa Proveedora S.A. de C.V.');
    $emisorRazonSocialLinea =
        $emisorRazonSocial !== '' && strcasecmp($emisorRazonSocial, $emisorNombre) !== 0
            ? $emisorRazonSocial
            : null;
    $emisorRfc = $p->rfc ?? null;
    $emisorTel = $p->telefono ?? null;
    $emisorEmail = $p->email ?? null;
@endphp
<div class="{{ $nameClass ?? 'company-header-name' }}">{{ $emisorNombre }}</div>
@if ($emisorRfc)
    <div class="{{ $lineClass }}">{{ $emisorRfc }}</div>
@endif
@if ($emisorRazonSocialLinea)
    <div class="{{ $lineClass }}">{{ $emisorNombreUpper($emisorRazonSocialLinea) }}</div>
@endif
@if (!$headerCompact && $emisorTel)
    <div class="{{ $lineClass }}">Tel. {{ $emisorTel }}</div>
@endif
@if (!$headerCompact && $emisorEmail)
    <div class="{{ $lineClass }}">{{ $emisorEmail }}</div>
@endif
