<h1>¡Bienvenido!</h1>
@if($nombreEmpresa)
<p>Gracias por registrar <strong>{{ $nombreEmpresa }}</strong> en nuestra plataforma.</p>
@else
<p>Gracias por registrarte en nuestra plataforma.</p>
@endif

<p>Para completar tu registro y crear tu contraseña, haz clic en el siguiente enlace:</p>
<a href="{{ $url }}">Crear mi contraseña</a>

<p>Si no solicitaste este registro, puedes ignorar este correo.</p>
