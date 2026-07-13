<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Activar acceso</title></head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.5;">
    <p>Hola {{ $invitation->user->first_name }},</p>
    @if ($invitation->condominium)
        <p>Has sido invitado a administrar <strong>{{ $invitation->condominium->name }}</strong>.</p>
    @else
        <p>Has sido invitado como <strong>Administrador Senior de la plataforma</strong>.</p>
    @endif
    <p><a href="{{ $activationUrl }}">Definir contraseña y activar acceso</a></p>
    <p>Este enlace es de un solo uso y expira en {{ $expiresHours }} horas.</p>
    <p>Si no reconoces esta invitación, ignora este correo.</p>
</body>
</html>
