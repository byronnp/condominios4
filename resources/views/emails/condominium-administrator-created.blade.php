<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administrador de condominio</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; color: #1f2937; line-height: 1.5;">
    <p>Hola {{ $administrator->first_name }},</p>

    <p>Has sido asignado como administrador del condominio <strong>{{ $condominium->name }}</strong>.</p>

    <p>Datos registrados:</p>

    <ul>
        <li>Condominio: {{ $condominium->name }}</li>
        <li>Correo: {{ $administrator->email }}</li>
        <li>Estado de acceso: {{ $administrator->is_access_enabled ? 'Activo' : 'Inactivo' }}</li>
    </ul>

    <p>Si no reconoces esta asignacion, contacta al soporte de la plataforma.</p>
</body>
</html>
