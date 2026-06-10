<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\OpenApi(
    info: new OA\Info(
        version: '1.0.0',
        title: 'Condominios API',
        description: 'API para administración de condominios.'
    ),
    servers: [
        new OA\Server(url: '/', description: 'Servidor actual'),
    ],
    tags: [
        new OA\Tag(name: 'Sistema', description: 'Endpoints técnicos de la API'),
        new OA\Tag(name: 'Autenticación', description: 'Registro, login y sesiones'),
        new OA\Tag(name: 'Catálogos', description: 'Catálogos generales del sistema'),
        new OA\Tag(name: 'Condominios', description: 'Administración multi-condominio'),
        new OA\Tag(name: 'Roles y permisos', description: 'Control de acceso'),
    ]
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    bearerFormat: 'JWT',
    scheme: 'bearer'
)]
class ApiSpecification
{
}
