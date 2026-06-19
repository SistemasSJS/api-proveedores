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

    $variant = (string) ($variant ?? 'default');
@endphp
@if ($mostrarAtentamente)
    <div class="atentamente-plain">
        @if ($variant === 'tailwind')
            <div class="tw-card-title">Atentamente:</div>
            @if (!empty($atentamente['nombre']))
                <div class="tw-receptor-strong">{{ $upperPersona($atentamente['nombre']) }}</div>
            @endif
            @if (!empty($atentamente['puesto']))
                <div class="tw-receptor-line">{{ $upperPersona($atentamente['puesto']) }}</div>
            @endif
            @if (!empty($atentamente['empresa']))
                <div class="tw-receptor-line">{{ $atentamente['empresa'] }}</div>
            @endif
            @if (!empty($atentamente['telefono']))
                <div class="tw-receptor-line">Tel. {{ $atentamente['telefono'] }}</div>
            @endif
            @if (!empty($atentamente['correo']))
                <div class="tw-receptor-line">{{ $atentamente['correo'] }}</div>
            @endif
        @else
            <div class="receptor-title">Atentamente:</div>
            @if (!empty($atentamente['nombre']))
                <div class="receptor-name">{{ $upperPersona($atentamente['nombre']) }}</div>
            @endif
            @if (!empty($atentamente['puesto']))
                <div class="receptor-info">{{ $upperPersona($atentamente['puesto']) }}</div>
            @endif
            @if (!empty($atentamente['empresa']))
                <div class="receptor-info">{{ $atentamente['empresa'] }}</div>
            @endif
            @if (!empty($atentamente['telefono']))
                <div class="receptor-info">Tel. {{ $atentamente['telefono'] }}</div>
            @endif
            @if (!empty($atentamente['correo']))
                <div class="receptor-info">{{ $atentamente['correo'] }}</div>
            @endif
        @endif
    </div>
@endif
