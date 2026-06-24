<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\OpenApi(
    info: new OA\Info(
        version: '1.0.0',
        title: 'Condominios API',
        description: "API para administración de condominios.\n\nCredenciales locales para probar en Swagger:\n- Email: swagger.admin@example.com\n- Password: Swagger123!\n\nUsa /api/auth/login para obtener el access_token y luego el botón Authorize con el valor Bearer {access_token}."
    ),
    servers: [
        new OA\Server(url: '/', description: 'Servidor actual'),
    ],
    tags: [
        new OA\Tag(name: 'Sistema', description: 'Endpoints técnicos de la API'),
        new OA\Tag(name: 'Autenticación', description: 'Registro, login y sesiones'),
        new OA\Tag(name: 'Catálogos', description: 'Catálogos generales del sistema'),
        new OA\Tag(name: 'Ubicaciones', description: 'Países, provincias y ciudades'),
        new OA\Tag(name: 'Condominios', description: 'Administración multi-condominio'),
        new OA\Tag(name: 'Administradores', description: 'Gestión de administradores por condominio'),
        new OA\Tag(name: 'Roles y permisos', description: 'Control de acceso'),
        new OA\Tag(name: 'Economía', description: 'Administración económica del condominio'),
        new OA\Tag(name: 'Gastos', description: 'Categorías y registro de gastos'),
        new OA\Tag(name: 'Pagos', description: 'Pagos y órdenes de pago'),
        new OA\Tag(name: 'Tesorería', description: 'Entregas y recepción de tesorería'),
        new OA\Tag(name: 'Unidades', description: 'Bloques, unidades y movimientos por unidad'),
        new OA\Tag(name: 'Personas por unidad', description: 'Relaciones entre usuarios y unidades'),
        new OA\Tag(name: 'Invitaciones', description: 'Invitaciones de acceso para usuarios'),
        new OA\Tag(name: 'Tenants', description: 'Endpoints legacy protegidos por API token'),
    ]
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    bearerFormat: 'JWT',
    scheme: 'bearer'
)]
#[OA\SecurityScheme(
    securityScheme: 'apiToken',
    type: 'http',
    bearerFormat: 'API token',
    scheme: 'bearer'
)]
class ApiSpecification {}
