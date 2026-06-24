<?php

namespace App\OpenApi;

class OpenApiExampleEnricher
{
    /**
     * @param  array<string, mixed>  $documentation
     * @return array<string, mixed>
     */
    public function enrich(array $documentation): array
    {
        foreach ($documentation['paths'] ?? [] as $path => $pathItem) {
            foreach ($pathItem as $method => $operation) {
                if (! in_array($method, ['get', 'post', 'put', 'patch', 'delete'], true)) {
                    continue;
                }

                $operationKey = strtoupper($method).' '.$path;

                if ($requestExample = $this->requestExamples()[$operationKey] ?? null) {
                    $operation['requestBody'] = $this->withRequestExample($operation['requestBody'] ?? [], $requestExample);
                } elseif (isset($operation['requestBody'])) {
                    $operation['requestBody'] = $this->withRequestExample(
                        $operation['requestBody'],
                        $this->exampleFromSchema($operation['requestBody']['content']['application/json']['schema'] ?? []),
                    );
                }

                foreach ($operation['responses'] ?? [] as $status => $response) {
                    if (! is_numeric((string) $status)) {
                        continue;
                    }

                    $operation['responses'][$status] = $this->withResponseExample(
                        $response,
                        (int) $status,
                        $this->responseExample($operationKey, (int) $status),
                    );
                }

                $documentation['paths'][$path][$method] = $operation;
            }
        }

        return $documentation;
    }

    /**
     * @param  array<string, mixed>  $requestBody
     * @param  array<string, mixed>  $example
     * @return array<string, mixed>
     */
    private function withRequestExample(array $requestBody, array $example): array
    {
        $requestBody['required'] ??= true;
        $contentType = isset($requestBody['content']['multipart/form-data'])
            ? 'multipart/form-data'
            : 'application/json';

        $requestBody['content'][$contentType]['schema'] ??= [
            'type' => 'object',
        ];
        $requestBody['content'][$contentType]['example'] = $example;

        return $requestBody;
    }

    /**
     * @param  array<string, mixed>  $response
     * @param  array<string, mixed>  $example
     * @return array<string, mixed>
     */
    private function withResponseExample(array $response, int $status, array $example): array
    {
        $response['content']['application/json']['schema'] ??= [
            'type' => 'object',
        ];
        $response['content']['application/json']['example'] = $example;

        if (! isset($response['description']) || $response['description'] === '') {
            $response['description'] = $status < 400 ? 'Operación exitosa' : 'Error de la API';
        }

        return $response;
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    private function exampleFromSchema(array $schema): array
    {
        $example = [];

        foreach ($schema['properties'] ?? [] as $property => $definition) {
            if (array_key_exists('example', $definition)) {
                $example[$property] = $definition['example'];

                continue;
            }

            $example[$property] = match ($definition['type'] ?? 'string') {
                'integer' => 1,
                'number' => 10.5,
                'boolean' => true,
                'array' => [],
                default => $definition['nullable'] ?? false ? null : 'string',
            };
        }

        return $example;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function requestExamples(): array
    {
        $login = [
            'email' => 'swagger.admin@example.com',
            'password' => 'Swagger123!',
            'device_name' => 'Swagger UI',
        ];

        $register = [
            'first_name' => 'Usuario',
            'last_name' => 'Demo',
            'email' => 'usuario.demo@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'country' => 'EC',
            'document_type_id' => 1,
            'document_number' => '1711111111',
            'device_name' => 'Swagger UI',
        ];

        return [
            'POST /api/auth/register' => $register,
            'POST /api/register' => $register,
            'POST /api/auth/login' => $login,
            'POST /api/login' => $login,
            'POST /api/auth/refresh' => ['refresh_token' => 'refresh-token'],
            'POST /api/auth/logout' => ['refresh_token' => 'refresh-token'],
            'POST /api/logout' => ['refresh_token' => 'refresh-token'],
            'POST /api/condominiums' => [
                'name' => 'Condominio Vista Verde',
                'slug' => 'condominio-vista-verde',
                'ruc' => '0999999999001',
                'type' => 'Residencial',
                'description' => 'Condominio residencial con áreas comunes y seguridad privada.',
                'status' => 'Activo',
                'email' => 'administracion@vistaverde.example.com',
                'phone' => '+593 4 555 0101',
                'country_code' => 'EC',
                'province_id' => 9,
                'city_id' => 101,
                'address' => 'Av. Principal 123 y Calle Secundaria',
                'reference' => 'Frente al parque central',
                'latitude' => -2.170998,
                'longitude' => -79.922359,
                'currency' => 'USD',
                'towers' => 4,
                'houses' => 120,
                'total_units' => 120,
                'is_active' => true,
                'characteristics' => [1, 2, 3],
                'admin_name' => 'Carlos',
                'admin_last_name' => 'Ramírez',
                'admin_document_type' => 'Cédula',
                'admin_id_number' => '0912345678',
                'admin_email' => 'carlos.ramirez@example.com',
                'admin_phone' => '+593 99 123 4567',
                'admin_status' => 'Activo',
                'logo' => 'logo.png',
            ],
            'PUT /api/condominiums/{condominium}' => [
                'name' => 'Condominio Vista Verde Renovado',
                'address' => 'Av. Principal 456 y Calle Secundaria',
                'reference' => 'Frente al parque renovado',
                'characteristics' => [1, 2, 3],
                'currency' => 'USD',
            ],
            'POST /api/condominiums/{condominium}/boards' => [
                'name' => 'Directiva 2026',
                'starts_on' => '2026-01-01',
                'ends_on' => '2026-12-31',
                'members' => [
                    ['user_id' => 1, 'position' => 'Presidente', 'starts_on' => '2026-01-01'],
                ],
            ],
            'POST /api/condominiums/{condominium}/payment-methods' => [
                'payment_method_type_id' => 1,
                'name' => 'Cuenta corriente principal',
                'account_number' => '2200123456',
                'bank_name' => 'Banco Demo',
                'account_holder' => 'Condominio Demo',
                'instructions' => 'Transferir y registrar comprobante.',
                'is_default' => true,
            ],
            'POST /api/menus' => [
                'parent_id' => null,
                'permission_ids' => [1, 2],
                'name' => 'Administración',
                'code' => 'administracion',
                'icon' => 'settings',
                'route' => '/admin',
                'sort_order' => 1,
                'is_active' => true,
            ],
            'POST /api/permissions' => [
                'module' => 'reports',
                'action' => 'view',
                'name' => 'Ver reportes',
                'code' => 'reports.view',
                'description' => 'Permite consultar reportes.',
                'is_active' => true,
            ],
            'POST /api/condominiums/{condominium}/roles' => [
                'name' => 'Administrador operativo',
                'code' => 'administrador_operativo',
                'description' => 'Rol operativo de administración.',
                'permission_ids' => [1, 2, 3],
            ],
            'PUT /api/condominiums/{condominium}/roles/{role}/permissions' => [
                'permission_ids' => [1, 2, 3],
            ],
            'POST /api/condominiums/{condominium}/blocks' => [
                'name' => 'Torre B',
                'code' => 'TORRE-B',
                'description' => 'Bloque residencial',
                'sort_order' => 2,
                'is_active' => true,
            ],
            'POST /api/condominiums/{condominium}/units' => [
                'condominium_block_id' => 1,
                'parent_unit_id' => null,
                'unit_type_id' => 1,
                'number' => '102',
                'code' => 'A-102',
                'floor' => '1',
                'area_m2' => 82.5,
                'current_aliquot_percentage' => 3.5,
                'is_assignable' => true,
                'is_active' => true,
                'parking_units' => [
                    ['number' => '13', 'code' => 'P-13', 'area_m2' => 12.5],
                ],
            ],
            'POST /api/condominiums/{condominium}/units/{unit}/users' => [
                'first_name' => 'Persona',
                'last_name' => 'Demo',
                'country' => 'EC',
                'document_type_id' => 1,
                'document_number' => '1722222222',
                'phone' => '0988888888',
                'secondary_phone' => null,
                'relationship_type_id' => 1,
                'started_at' => '2026-06-01',
                'ended_at' => null,
                'is_primary' => true,
                'is_billing_responsible' => true,
            ],
            'POST /api/condominiums/{condominium}/units/{unit}/users/{user}/access-invitations' => [
                'email' => 'persona.demo@example.com',
            ],
            'POST /api/access-invitations/{token}/accept' => [
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ],
            'POST /api/users/{user}/billing-profiles' => [
                'document_type_id' => 1,
                'document_number' => '1711111111',
                'business_name' => 'Usuario Demo',
                'trade_name' => null,
                'billing_email' => 'facturacion@example.com',
                'phone' => '0999999999',
                'address' => 'Quito',
                'city' => 'Quito',
                'province' => 'Pichincha',
                'country' => 'EC',
                'is_default' => true,
            ],
            'POST /api/condominiums/{condominium}/monthly-fees/generate' => [
                'period_year' => 2026,
                'period_month' => 6,
                'due_on' => '2026-06-10',
                'concept_id' => 1,
            ],
            'PATCH /api/condominiums/{condominium}/units/{unit}/users/{user}/deactivate' => [
                'ended_at' => '2026-06-30',
                'disable_access' => true,
            ],
            'PATCH /api/condominiums/{condominium}/units/{unit}/billing-responsible' => [
                'user_id' => 1,
            ],
            'PATCH /api/condominiums/{condominium}/units/{unit}/access-invitations/{invitation}/cancel' => [
                'cancel_reason' => 'Solicitud del administrador',
            ],
            'POST /api/tenants' => ['name' => 'Tenant Demo'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function responseExample(string $operationKey, int $status): array
    {
        if (str_contains($operationKey, '/api/tenants')) {
            return $status < 400
                ? ['tenants' => [$this->tenant()]]
                : ['message' => 'No se pudo procesar la solicitud.'];
        }

        if ($operationKey === 'GET /api/roles') {
            return ['roles' => [$this->role()]];
        }

        if ($status >= 400) {
            return [
                'success' => false,
                'message' => $status === 422 ? 'Los datos enviados no son válidos.' : 'No se pudo procesar la solicitud.',
                'errors' => $status === 422 ? ['field' => ['El campo es obligatorio.']] : (object) [],
                'code' => $status === 422 ? 'validation_failed' : 'request_error',
                'meta' => (object) [],
            ];
        }

        return [
            'success' => true,
            'message' => 'Operación realizada correctamente.',
            'data' => $this->responseData($operationKey),
            'meta' => (object) [],
        ];
    }

    private function responseData(string $operationKey): mixed
    {
        return match (true) {
            str_contains($operationKey, '/auth/login'),
            str_contains($operationKey, '/auth/register'),
            $operationKey === 'POST /api/login',
            $operationKey === 'POST /api/register',
            str_contains($operationKey, '/auth/refresh') => $this->tokens(),
            str_contains($operationKey, '/auth/me'),
            $operationKey === 'GET /api/user' => [
                'user' => $this->user(),
                'platform_role' => [
                    'id' => 1,
                    'name' => 'Administrador Senior',
                    'code' => 'administrador_senior',
                ],
                'is_platform_admin' => true,
                'condominium' => $this->condominium(),
                'roles' => [$this->role()],
                'permissions' => ['condominiums.view', 'condominiums.create', 'roles.manage'],
                'auth_session' => $this->authSession(),
            ],
            str_contains($operationKey, '/auth/sessions') => [$this->authSession()],
            str_contains($operationKey, '/catalogs/{catalog}/items') => [$this->catalogItem()],
            str_contains($operationKey, '/catalogs') => str_contains($operationKey, '/catalogs/{catalog}')
                ? $this->catalog()
                : [$this->catalog()],
            str_contains($operationKey, '/countries/{country}/provinces') => [$this->province()],
            str_contains($operationKey, '/countries/{country}') => $this->country(),
            str_contains($operationKey, '/countries') => [$this->country()],
            str_contains($operationKey, '/provinces/{province}/cities') => [$this->city()],
            str_contains($operationKey, '/health') => [
                'status' => 'ok',
                'service' => 'condominios-api',
                'timestamp' => '2026-06-17T12:00:00Z',
            ],
            str_contains($operationKey, '/condominiums/{condominium}/units/{unit}/users/{user}/access-invitations') => $this->accessInvitation(),
            str_contains($operationKey, '/access-invitations') => null,
            str_contains($operationKey, '/billing-profiles') => str_starts_with($operationKey, 'GET')
                ? [$this->billingProfile()]
                : $this->billingProfile(),
            str_contains($operationKey, '/account-opening-balances') => $this->collectionOrItem($operationKey, $this->accountOpeningBalance()),
            str_contains($operationKey, '/bank-account-movements') => $this->collectionOrItem($operationKey, $this->bankAccountMovement()),
            str_contains($operationKey, '/bank-statement-imports') => $this->collectionOrItem($operationKey, $this->bankStatementImport()),
            str_contains($operationKey, '/bank-reconciliations') => $this->collectionOrItem($operationKey, $this->bankReconciliation()),
            str_contains($operationKey, '/billing-settings') => $this->billingSetting(),
            str_contains($operationKey, '/billing-concepts') => $this->collectionOrItem($operationKey, $this->billingConcept()),
            str_contains($operationKey, '/expense-categories') => $this->collectionOrItem($operationKey, $this->expenseCategory()),
            str_contains($operationKey, '/expenses') => $this->collectionOrItem($operationKey, $this->expense()),
            str_contains($operationKey, '/extraordinary-fees') => $this->collectionOrItem($operationKey, $this->extraordinaryFee()),
            str_contains($operationKey, '/monthly-fees') => [$this->monthlyFee()],
            str_contains($operationKey, '/payments') => $this->collectionOrItem($operationKey, $this->payment()),
            str_contains($operationKey, '/payment-orders') => $this->collectionOrItem($operationKey, $this->paymentOrder()),
            str_contains($operationKey, '/treasury-handovers/calculate') => $this->treasuryCalculation(),
            str_contains($operationKey, '/treasury-handovers') => $this->collectionOrItem($operationKey, $this->treasuryHandover()),
            str_contains($operationKey, '/account-movements') => [$this->unitAccountMovement()],
            str_contains($operationKey, '/blocks') => $this->collectionOrItem($operationKey, $this->block()),
            str_contains($operationKey, '/boards') => $this->collectionOrItem($operationKey, $this->board()),
            str_contains($operationKey, '/payment-methods') => $this->collectionOrItem($operationKey, $this->paymentMethod()),
            str_contains($operationKey, '/units/{unit}/users') => str_starts_with($operationKey, 'GET')
                ? [$this->unitUser()]
                : ['user' => $this->user(), 'unit_relation' => $this->unitUser()],
            str_contains($operationKey, '/units/{unit}') => $this->unit(),
            str_contains($operationKey, '/units'),
            str_contains($operationKey, '/my/units') => [$this->unit()],
            str_contains($operationKey, '/menus') || str_contains($operationKey, '/auth/menu') => [$this->menu()],
            str_contains($operationKey, '/permissions') => $this->collectionOrItem($operationKey, $this->permission()),
            str_contains($operationKey, '/roles') => $this->collectionOrItem($operationKey, $this->role()),
            str_contains($operationKey, '/condominiums/options') => [
                ['key' => 1, 'value' => 'Condominio Los Cedros'],
            ],
            str_contains($operationKey, '/condominiums/{condominium}') => $this->condominium(),
            str_contains($operationKey, '/condominiums') => $this->collectionOrItem($operationKey, $this->condominium()),
            default => null,
        };
    }

    private function collectionOrItem(string $operationKey, array $item): array
    {
        return str_starts_with($operationKey, 'GET') ? [$item] : $item;
    }

    /**
     * @return array<string, mixed>
     */
    private function tokens(): array
    {
        return [
            'token_type' => 'Bearer',
            'access_token' => 'jwt-access-token',
            'refresh_token' => 'refresh-token',
            'expires_in' => 3600,
            'access_token_expires_at' => '2026-06-17T13:00:00Z',
            'refresh_token_expires_at' => '2026-07-17T12:00:00Z',
        ];
    }

    private function user(): array
    {
        return [
            'id' => 1,
            'name' => 'SWAGGER ADMIN',
            'first_name' => 'SWAGGER',
            'last_name' => 'ADMIN',
            'email' => 'swagger.admin@example.com',
            'country' => 'EC',
            'document_type' => $this->catalogItem(),
            'document_number' => '1799999999',
            'phone' => '0999999998',
            'secondary_phone' => null,
            'is_access_enabled' => true,
        ];
    }

    private function authSession(): array
    {
        return [
            'id' => 1,
            'device_name' => 'Swagger UI',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'last_activity_at' => '2026-06-17T12:00:00Z',
            'ended_at' => null,
            'is_active' => true,
        ];
    }

    private function catalog(): array
    {
        return [
            'id' => 1,
            'code' => 'document_types',
            'name' => 'Tipos de documento',
            'description' => 'Catálogo de tipos de documento',
            'items' => [$this->catalogItem()],
        ];
    }

    private function catalogItem(): array
    {
        return [
            'id' => 1,
            'catalog_id' => 1,
            'code' => 'cedula',
            'name' => 'Cédula',
            'description' => 'Documento de identidad',
            'sort_order' => 1,
            'is_active' => true,
        ];
    }

    private function country(): array
    {
        return [
            'id' => 1,
            'code' => 'EC',
            'iso3' => 'ECU',
            'name' => 'Ecuador',
            'phone_code' => '+593',
            'currency_code' => 'USD',
            'is_active' => true,
        ];
    }

    private function province(): array
    {
        return [
            'id' => 1,
            'country_id' => 1,
            'code' => 'EC-P',
            'name' => 'Pichincha',
            'is_active' => true,
        ];
    }

    private function city(): array
    {
        return [
            'id' => 1,
            'province_id' => 1,
            'code' => 'EC-P-QUITO',
            'name' => 'Quito',
            'is_active' => true,
        ];
    }

    private function condominium(): array
    {
        return [
            'id' => 1,
            'name' => 'Condominio Los Cedros',
            'slug' => 'condominio-los-cedros',
            'ruc' => '1799999999001',
            'type' => [
                'id' => 1,
                'code' => 'residencial',
                'name' => 'Residencial',
            ],
            'description' => 'Condominio residencial con áreas comunes y seguridad privada.',
            'email' => 'administracion@loscedros.ec',
            'phone' => '0999999999',
            'address' => 'Av. Amazonas N34-120 y Atahualpa',
            'address_reference' => 'Frente al parque central',
            'country_code' => 'EC',
            'province_id' => 1,
            'city_id' => 1,
            'country' => $this->country(),
            'province' => $this->province(),
            'city' => $this->city(),
            'latitude' => -2.170998,
            'longitude' => -79.922359,
            'currency' => 'USD',
            'towers_count' => 4,
            'houses_count' => 120,
            'total_units' => 24,
            'features' => [
                ['id' => 1, 'code' => 'piscina', 'name' => 'Piscina'],
                ['id' => 2, 'code' => 'gimnasio', 'name' => 'Gimnasio'],
            ],
            'administrator' => [
                'id' => 1,
                'name' => 'Carlos Ramírez',
                'first_name' => 'Carlos',
                'last_name' => 'Ramírez',
                'email' => 'carlos.ramirez@example.com',
                'document_type' => $this->catalogItem(),
                'document_number' => '0912345678',
                'phone' => '+593 99 123 4567',
                'is_access_enabled' => true,
                'is_active' => true,
            ],
            'logo_path' => 'condominiums/logos/logo.png',
            'logo_url' => 'https://cdn.example.com/condominiums/logos/logo.png',
            'is_active' => true,
        ];
    }

    private function block(): array
    {
        return ['id' => 1, 'condominium_id' => 1, 'name' => 'Torre A', 'code' => 'TORRE-A', 'sort_order' => 1, 'is_active' => true];
    }

    private function unit(): array
    {
        return ['id' => 1, 'condominium_id' => 1, 'code' => 'CASA-01', 'number' => '01', 'floor' => null, 'area_m2' => 120, 'current_aliquot_percentage' => 5, 'is_active' => true];
    }

    private function unitUser(): array
    {
        return ['id' => 1, 'name' => 'SWAGGER ADMIN', 'first_name' => 'SWAGGER', 'last_name' => 'ADMIN', 'email' => 'swagger.admin@example.com', 'relationship_code' => 'propietario', 'is_primary' => true, 'is_billing_responsible' => true, 'is_active' => true];
    }

    private function menu(): array
    {
        return ['id' => 1, 'name' => 'Administración', 'code' => 'administracion', 'icon' => 'settings', 'route' => '/admin', 'children' => []];
    }

    private function permission(): array
    {
        return ['id' => 1, 'module' => 'roles', 'action' => 'manage', 'code' => 'roles.manage', 'name' => 'Administrar roles', 'is_active' => true];
    }

    private function role(): array
    {
        return ['id' => 1, 'condominium_id' => 1, 'name' => 'Administrador', 'code' => 'administrador', 'permissions' => [$this->permission()]];
    }

    private function board(): array
    {
        return ['id' => 1, 'condominium_id' => 1, 'name' => 'Directiva 2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'members' => []];
    }

    private function paymentMethod(): array
    {
        return ['id' => 1, 'condominium_id' => 1, 'name' => 'Cuenta corriente principal', 'bank_name' => 'Banco Demo', 'account_number' => '2200123456', 'is_default' => true];
    }

    private function billingProfile(): array
    {
        return ['id' => 1, 'user_id' => 1, 'business_name' => 'SWAGGER ADMIN', 'billing_email' => 'swagger.admin@example.com', 'document_number' => '1799999999', 'is_default' => true];
    }

    private function billingConcept(): array
    {
        return ['id' => 1, 'name' => 'Alícuota ordinaria', 'code' => 'monthly_fee', 'is_active' => true];
    }

    private function billingSetting(): array
    {
        return ['id' => 1, 'condominium_id' => 1, 'due_day' => 10, 'grace_days' => 3, 'late_fee_type' => 'percentage', 'late_fee_value' => 2.5, 'currency' => 'USD'];
    }

    private function monthlyFee(): array
    {
        return ['id' => 1, 'condominium_id' => 1, 'unit_id' => 1, 'period_year' => 2026, 'period_month' => 6, 'amount' => 50, 'status' => 'pending'];
    }

    private function extraordinaryFee(): array
    {
        return ['id' => 1, 'condominium_id' => 1, 'name' => 'Reparación de ascensor', 'amount' => 25, 'starts_on' => '2026-06-01', 'ends_on' => '2026-06-30', 'apply_to' => 'all_units'];
    }

    private function payment(): array
    {
        return ['id' => 1, 'condominium_id' => 1, 'unit_id' => 1, 'user_id' => 1, 'amount' => 50, 'paid_at' => '2026-06-16T12:00:00Z', 'status' => 'confirmed'];
    }

    private function unitAccountMovement(): array
    {
        return ['id' => 1, 'unit_id' => 1, 'type' => 'monthly_fee', 'direction' => 'debit', 'amount' => 50, 'balance_after' => 50];
    }

    private function accountOpeningBalance(): array
    {
        return ['id' => 1, 'condominium_id' => 1, 'condominium_payment_method_id' => 1, 'opening_balance' => 1500.25, 'opened_on' => '2026-06-01'];
    }

    private function bankAccountMovement(): array
    {
        return ['id' => 1, 'condominium_id' => 1, 'direction' => 'credit', 'amount' => 125.75, 'movement_date' => '2026-06-15', 'description' => 'Depósito registrado'];
    }

    private function bankStatementImport(): array
    {
        return ['id' => 1, 'condominium_id' => 1, 'period_year' => 2026, 'period_month' => 6, 'rows' => [['id' => 1, 'amount' => 50, 'direction' => 'credit']]];
    }

    private function bankReconciliation(): array
    {
        return ['id' => 1, 'condominium_id' => 1, 'period_year' => 2026, 'period_month' => 6, 'bank_statement_balance' => 1750.25, 'items' => []];
    }

    private function expenseCategory(): array
    {
        return ['id' => 1, 'condominium_id' => 1, 'name' => 'Mantenimiento', 'code' => 'maintenance'];
    }

    private function expense(): array
    {
        return ['id' => 1, 'condominium_id' => 1, 'supplier_name' => 'Servicios Generales Quito', 'description' => 'Mantenimiento de bomba de agua', 'amount' => 120.5, 'status' => 'paid'];
    }

    private function paymentOrder(): array
    {
        return ['id' => 1, 'condominium_id' => 1, 'unit_id' => 1, 'user_id' => 1, 'amount' => 50, 'status' => 'pending', 'expires_at' => '2026-06-30T23:59:59Z'];
    }

    private function treasuryCalculation(): array
    {
        return ['income_total' => 1500, 'expense_total' => 500, 'bank_balance' => 1000, 'cash_balance' => 0];
    }

    private function treasuryHandover(): array
    {
        return ['id' => 1, 'condominium_id' => 1, 'type' => 'handover', 'period_starts_on' => '2026-06-01', 'period_ends_on' => '2026-06-30', 'bank_balance' => 1000];
    }

    private function accessInvitation(): array
    {
        return ['id' => 1, 'user_id' => 1, 'email' => 'persona.demo@example.com', 'token' => 'invitation-token', 'accept_url' => 'http://localhost/api/access-invitations/invitation-token/accept', 'expires_at' => '2026-06-24T12:00:00Z'];
    }

    private function tenant(): array
    {
        return ['id' => 1, 'name' => 'Tenant Demo', 'slug' => 'tenant-demo', 'users_count' => 1];
    }
}
