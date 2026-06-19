{{--
  Líneas de datos emisor (sin logo ni folio).
  Orden: nombre empresa → RFC → contacto → tel/correo.
  Mayúsculas: solo nombre persona y puesto. Correo y teléfono sin cambiar.
--}}
@php
    $emisorNombreUpper = static function (?string $text): string {
        $t = trim((string) ($text ?? ''));

        return $t === '' ? '' : mb_strtoupper($t, 'UTF-8');
    };

    $emisorLineaContactoDisplay = static function (?string $text) use ($emisorNombreUpper): string {
        $t = trim((string) ($text ?? ''));
        if ($t === '') {
            return '';
        }
        if (str_contains($t, '@')) {
            return $t;
        }
        if (stripos($t, 'Tel.') === 0) {
            return $t;
        }

        return $emisorNombreUpper($t);
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
    $emisorContactoLineas = $presupuesto['emisor_contacto_lineas'] ?? [];
    $tieneTarjetaEmisor = !empty($presupuesto['config_emisor_presupuesto_id']);
    $nombreTarjeta = $tieneTarjetaEmisor && !empty($emisorContactoLineas)
        ? array_shift($emisorContactoLineas)
        : null;
    $lineasRestantesTarjeta = $emisorContactoLineas;
@endphp
<div class="{{ $nameClass ?? 'company-header-name' }}">{{ $emisorNombre }}</div>
@if ($emisorRfc)
    <div class="{{ $lineClass }}">{{ $emisorRfc }}</div>
@endif
@if ($tieneTarjetaEmisor)
    @if ($nombreTarjeta)
        <div class="{{ $lineClass }}">{{ $emisorNombreUpper($nombreTarjeta) }}</div>
    @endif
    @foreach ($lineasRestantesTarjeta as $lineaContacto)
        <div class="{{ $lineClass }}">{{ $emisorLineaContactoDisplay($lineaContacto) }}</div>
    @endforeach
@else
    @if ($emisorRazonSocialLinea)
        <div class="{{ $lineClass }}">{{ $emisorNombreUpper($emisorRazonSocialLinea) }}</div>
    @endif
    @if (!$headerCompact && $emisorTel)
        <div class="{{ $lineClass }}">Tel. {{ $emisorTel }}</div>
    @endif
    @if (!$headerCompact && $emisorEmail)
        <div class="{{ $lineClass }}">{{ $emisorEmail }}</div>
    @endif
@endif
