@php
    $appName = (string) config('app.name', 'Aplicacion');
    $logoAlt = trim($appName) !== '' ? $appName : 'Aplicacion';
    $fallbackInitial = strtoupper(mb_substr($logoAlt, 0, 1));
@endphp

@if(!empty($logoAppDataUri))
<img src="{{ $logoAppDataUri }}" alt="{{ $logoAlt }}" width="80" style="max-width:80px;height:auto;margin-bottom:12px;display:block;margin-left:auto;margin-right:auto;">
@else
<div style="width:48px;height:48px;border-radius:8px;background-color:#1e88e5;color:#ffffff;font-family:Arial,Helvetica,sans-serif;font-size:22px;line-height:48px;font-weight:700;text-align:center;margin:0 auto 12px auto;">
{{ $fallbackInitial !== '' ? $fallbackInitial : 'A' }}
</div>
@endif
