<div style="background:linear-gradient(135deg,#1d4e89 0%,#2b6cb0 100%);padding:28px 20px;text-align:center;border-bottom:1px solid #cbd5e1;">
  @include('emails.partials.logo-app')
  <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:700;letter-spacing:0.2px;">{{ $title ?? config('app.name') }}</h1>
  <p style="margin:6px 0 0;color:#e2e8f0;font-size:13px;">{{ $subtitle ?? config('app.name') }}</p>
</div>
