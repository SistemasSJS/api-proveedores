@php
    use App\Support\PresupuestoPdf;

    $mostrarAtentamente = PresupuestoPdf::debeMostrarBloqueAtentamenteDesdePayload($presupuesto);
    $atentamente = $mostrarAtentamente
        ? PresupuestoPdf::datosBloqueAtentamenteDesdePayload($presupuesto)
        : [];

    $linea = static function (?string $text): string {
        return trim((string) ($text ?? ''));
    };

    $variant = (string) ($variant ?? 'default');
@endphp
@if ($mostrarAtentamente)
    <div class="atentamente-plain">
        @if ($variant === 'tailwind')
            <div class="tw-card-title atentamente-title">Atentamente:</div>
            <div class="atentamente-spacer" aria-hidden="true"></div>
            @if ($linea($atentamente['nombre'] ?? null) !== '')
                <div class="tw-receptor-strong">{{ $linea($atentamente['nombre']) }}</div>
            @endif
            @if ($linea($atentamente['puesto'] ?? null) !== '')
                <div class="tw-receptor-line">{{ $linea($atentamente['puesto']) }}</div>
            @endif
            @if ($linea($atentamente['empresa'] ?? null) !== '')
                <div class="tw-receptor-line">{{ $linea($atentamente['empresa']) }}</div>
            @endif
            @if ($linea($atentamente['telefono'] ?? null) !== '')
                <div class="tw-receptor-line">Tel. {{ $linea($atentamente['telefono']) }}</div>
            @endif
            @if ($linea($atentamente['correo'] ?? null) !== '')
                <div class="tw-receptor-line">{{ $linea($atentamente['correo']) }}</div>
            @endif
        @else
            <div class="receptor-title atentamente-title">Atentamente:</div>
            <div class="atentamente-spacer" aria-hidden="true"></div>
            @if ($linea($atentamente['nombre'] ?? null) !== '')
                <div class="receptor-name">{{ $linea($atentamente['nombre']) }}</div>
            @endif
            @if ($linea($atentamente['puesto'] ?? null) !== '')
                <div class="receptor-info">{{ $linea($atentamente['puesto']) }}</div>
            @endif
            @if ($linea($atentamente['empresa'] ?? null) !== '')
                <div class="receptor-info">{{ $linea($atentamente['empresa']) }}</div>
            @endif
            @if ($linea($atentamente['telefono'] ?? null) !== '')
                <div class="receptor-info">Tel. {{ $linea($atentamente['telefono']) }}</div>
            @endif
            @if ($linea($atentamente['correo'] ?? null) !== '')
                <div class="receptor-info">{{ $linea($atentamente['correo']) }}</div>
            @endif
        @endif
    </div>
@endif
