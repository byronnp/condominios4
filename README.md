# Condominios API

API Laravel para administracion de condominios. Incluye autenticacion JWT, catalogos, condominios, roles, permisos, menus, unidades, usuarios por unidad y administracion economica.

## Entorno local

Levantar contenedores:

```bash
docker compose up -d
```

Ejecutar migraciones con seeders:

```bash
docker compose exec app php artisan migrate --seed
```

Si la base ya esta migrada y solo se requiere actualizar datos de prueba:

```bash
docker compose exec app php artisan db:seed
```

## Swagger

La documentacion Swagger esta disponible en:

```text
/api/documentation
```

Regenerar documentacion OpenAPI:

```bash
docker compose exec app php artisan openapi:generate
```

Este comando genera Swagger y agrega ejemplos de payload de envio y respuesta para las operaciones documentadas.

## Usuario de pruebas

Los seeders crean un usuario local para probar los servicios desde Swagger:

```text
Email: swagger.admin@example.com
Password: Swagger123!
```

Este usuario queda asociado al condominio de prueba y tiene rol `administrador`.

## Probar servicios en Swagger

1. Ejecutar seeders si el usuario aun no existe:

```bash
docker compose exec app php artisan db:seed
```

2. Abrir Swagger en `/api/documentation`.

3. Ejecutar `POST /api/auth/login` con:

```json
{
  "email": "swagger.admin@example.com",
  "password": "Swagger123!",
  "device_name": "Swagger UI"
}
```

4. Copiar `data.access_token` de la respuesta.

5. Presionar `Authorize` en Swagger y usar:

```text
Bearer {access_token}
```

6. Probar los endpoints protegidos.

## Estado y eliminación de condominios

`PATCH /api/condominiums/{condominium}/status` modifica únicamente el campo
`condominiums.is_active`. Actualmente no inactiva usuarios o membresías y no
revoca sesiones ni tokens. El inicio de sesión depende de
`users.is_access_enabled`, por lo que un usuario puede autenticarse aunque su
condominio esté inactivo. La validación transversal que impida operar sobre un
condominio inactivo está pendiente.

`DELETE /api/condominiums/{condominium}` realiza una eliminación lógica mediante
`deleted_at`. Los usuarios asociados no se eliminan ni se inactivan y sus
sesiones no se revocan automáticamente. El condominio eliminado deja de estar
disponible en el enlace de modelo y en las consultas Eloquent normales, pero el
usuario puede iniciar sesión si `users.is_access_enabled` continúa activo.

Los administradores senior conservan su acceso global porque no dependen de una
asignación a un condominio.

## Pruebas

Ejecutar suite completa:

```bash
docker compose exec app php artisan test
```

Ejecutar pruebas especificas:

```bash
docker compose exec app php artisan test --filter=JwtAuthTest
docker compose exec app php artisan test --filter=SwaggerDocumentationTest
```

## Formato

Formatear archivos PHP modificados:

```bash
./vendor/bin/pint --dirty
```
