{{-- Encabezado documento (logo, emisor, folio). $headerCompact true = segunda hoja y siguientes. Requiere $presupuesto. --}}
@php
    $headerCompact = !empty($headerCompact);
@endphp
<div class="header{{ $headerCompact ? ' header--compact' : '' }}">
    <table class="header-content">
        <tr>
            <td class="logo-section">
                @php
                    $logoProveedorBase64 = $presupuesto['logo_proveedor_base64'] ?? null;
                    $nombreEmpresa =
                        $presupuesto['proveedor']->nombre_comercial ??
                        ($presupuesto['proveedor']->razon_social ?? 'P');
                    $inicial = strtoupper(substr($nombreEmpresa, 0, 1));
                @endphp
                @if ($logoProveedorBase64)
                    <img src="{{ $logoProveedorBase64 }}" alt="Logo" class="logo-img" />
                @else
                    <div class="logo-fallback">{{ $inicial }}</div>
                @endif
            </td>
            <td class="header-info">
                @php
                    $p = $presupuesto['proveedor'];
                    $emisorComercial = trim((string) ($p->nombre_comercial ?? ''));
                    $emisorRazonSocial = trim((string) ($p->razon_social ?? ''));
                    $emisorNombre =
                        $emisorComercial !== ''
                            ? $emisorComercial
                            : ($emisorRazonSocial !== ''
                                ? $emisorRazonSocial
                                : 'Empresa Proveedora S.A. de C.V.');
                    $emisorRazonSocialLinea =
                        $emisorRazonSocial !== '' &&
                        strcasecmp($emisorRazonSocial, $emisorNombre) !== 0
                            ? $emisorRazonSocial
                            : null;
                    $emisorRfc = $p->rfc ?? null;
                    $emisorDireccion = $p->direccion_empresa ?? null;
                    $df = $p->direccion_fiscal ?? null;
                    $ciudad =
                        $p->ciudad ??
                        (is_array($df)
                            ? $df['ciudad'] ?? 'Ciudad de México'
                            : $df->ciudad ?? 'Ciudad de México');
                    $estado = is_array($df) ? $df['estado'] ?? 'CDMX' : $df->estado ?? 'CDMX';
                    $emisorCiudad = $ciudad . ', ' . $estado . ', México';
                    $emisorTel = $p->telefono ?? null;
                    $emisorEmail = $p->email ?? null;
                @endphp
                <div class="company-header-name">{{ $emisorNombre }}</div>
                @if ($emisorRazonSocialLinea)
                    <div class="company-header-info">{{ $emisorRazonSocialLinea }}</div>
                @endif
                @if ($emisorRfc)
                    <div class="company-header-info">{{ $emisorRfc }}</div>
                @endif
                @if (!$headerCompact && $emisorTel)
                    <div class="company-header-info">Tel. {{ $emisorTel }}</div>
                @endif
                @if (!$headerCompact && $emisorEmail)
                    <div class="company-header-info">{{ $emisorEmail }}</div>
                @endif
            </td>
            <td class="folio-section">
                <div class="folio-label">Presupuesto</div>
                <div class="folio-number">
                    {{ $presupuesto['numero_presupuesto'] ?? 'PRES-000001' }}
                </div>
                @if (!$headerCompact && !empty($presupuesto['uuid']))
                    <div class="folio-uuid">{{ $presupuesto['uuid'] }}</div>
                @endif
                <div class="folio-date">
                    @php
                        $fecha = $presupuesto['fecha_emision'] ?? now();
                        if (is_string($fecha)) {
                            $fecha = \Carbon\Carbon::parse($fecha);
                        }
                        $fechaFormateada = $fecha
                            ->locale('es')
                            ->translatedFormat('d \d\e F \d\e\l Y');
                    @endphp
                    {{ $fechaFormateada }}
                </div>
            </td>
        </tr>
    </table>
</div>
<div class="header-rule" role="presentation"></div>
