@php
    use App\Support\PresupuestoPdf;

    $mostrarAtentamente = PresupuestoPdf::debeMostrarBloqueAtentamenteDesdePayload($presupuesto);
    $atentamente = $mostrarAtentamente
        ? PresupuestoPdf::datosBloqueAtentamenteDesdePayload($presupuesto)
        : [];

    $upperPersona = static function (?string $text): string {
        $t = trim((string) ($text ?? ''));

        return $t === '' ? '' : mb_strtoupper($t, 'UTF-8');
    };
@endphp
@if ($mostrarAtentamente)
    <div class="atentamente-block">
        <div class="atentamente-title">Atentamente:</div>
        <div class="atentamente-spacer" aria-hidden="true"></div>
        @if (!empty($atentamente['nombre']))
            <div class="atentamente-nombre">{{ $upperPersona($atentamente['nombre']) }}</div>
        @endif
        @if (!empty($atentamente['puesto']))
            <div class="atentamente-line">{{ $upperPersona($atentamente['puesto']) }}</div>
        @endif
        @if (!empty($atentamente['empresa']))
            <div class="atentamente-line">{{ $atentamente['empresa'] }}</div>
        @endif
        @if (!empty($atentamente['telefono']))
            <div class="atentamente-line">Tel. {{ $atentamente['telefono'] }}</div>
        @endif
        @if (!empty($atentamente['correo']))
            <div class="atentamente-line">{{ $atentamente['correo'] }}</div>
        @endif
    </div>
@endif
