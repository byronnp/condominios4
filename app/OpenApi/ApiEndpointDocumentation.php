<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

class ApiEndpointDocumentation
{
    #[OA\Post(
        path: '/api/billing-concepts',
        operationId: 'billingConceptsStore',
        summary: 'Crear concepto de cobro',
        tags: ['Economía'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Alícuota ordinaria'),
                    new OA\Property(property: 'code', type: 'string', nullable: true, example: 'monthly_fee'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Cuota ordinaria mensual'),
                    new OA\Property(property: 'is_active', type: 'boolean', nullable: true, example: true),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Concepto creado'),
            new OA\Response(response: 422, description: 'Datos inválidos'),
        ]
    )]
    public function billingConceptsStore(): void {}

    #[OA\Post(
        path: '/api/condominiums/{condominium}/billing-settings',
        operationId: 'billingSettingsStore',
        summary: 'Guardar configuración económica',
        tags: ['Economía'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['due_day', 'grace_days', 'late_fee_type', 'late_fee_value', 'late_fee_frequency'],
                properties: [
                    new OA\Property(property: 'due_day', type: 'integer', example: 10),
                    new OA\Property(property: 'grace_days', type: 'integer', example: 3),
                    new OA\Property(property: 'late_fee_type', type: 'string', enum: ['percentage', 'fixed'], example: 'percentage'),
                    new OA\Property(property: 'late_fee_value', type: 'number', format: 'float', example: 2.5),
                    new OA\Property(property: 'late_fee_frequency', type: 'string', enum: ['daily', 'monthly', 'once'], example: 'monthly'),
                    new OA\Property(property: 'apply_late_fee_automatically', type: 'boolean', nullable: true, example: true),
                    new OA\Property(property: 'currency', type: 'string', nullable: true, example: 'USD'),
                    new OA\Property(property: 'rounding_mode', type: 'string', nullable: true, example: 'none'),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Configuración guardada'),
            new OA\Response(response: 422, description: 'Datos inválidos'),
        ]
    )]
    public function billingSettingsStore(): void {}

    #[OA\Get(path: '/api/condominiums/{condominium}/account-opening-balances', operationId: 'accountOpeningBalancesIndex', summary: 'Listar saldos iniciales bancarios', tags: ['Economía'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Saldos iniciales encontrados')])]
    public function accountOpeningBalancesIndex(): void {}

    #[OA\Post(
        path: '/api/condominiums/{condominium}/account-opening-balances',
        operationId: 'accountOpeningBalancesStore',
        summary: 'Registrar saldo inicial bancario',
        tags: ['Economía'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['condominium_payment_method_id', 'opening_balance', 'opened_on'],
                properties: [
                    new OA\Property(property: 'condominium_payment_method_id', type: 'integer', example: 1),
                    new OA\Property(property: 'opening_balance', type: 'number', format: 'float', example: 1500.25),
                    new OA\Property(property: 'opened_on', type: 'string', format: 'date', example: '2026-06-01'),
                    new OA\Property(property: 'notes', type: 'string', nullable: true, example: 'Saldo inicial de cuenta bancaria'),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Saldo inicial registrado'),
            new OA\Response(response: 422, description: 'Datos inválidos'),
        ]
    )]
    public function accountOpeningBalancesStore(): void {}

    #[OA\Get(path: '/api/condominiums/{condominium}/bank-account-movements', operationId: 'bankAccountMovementsIndex', summary: 'Listar movimientos bancarios', tags: ['Economía'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Movimientos bancarios encontrados')])]
    public function bankAccountMovementsIndex(): void {}

    #[OA\Post(
        path: '/api/condominiums/{condominium}/bank-account-movements',
        operationId: 'bankAccountMovementsStore',
        summary: 'Registrar movimiento bancario',
        tags: ['Economía'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['condominium_payment_method_id', 'type', 'direction', 'amount', 'movement_date', 'description'],
                properties: [
                    new OA\Property(property: 'condominium_payment_method_id', type: 'integer', example: 1),
                    new OA\Property(property: 'type', type: 'string', example: 'manual_adjustment'),
                    new OA\Property(property: 'direction', type: 'string', enum: ['credit', 'debit'], example: 'credit'),
                    new OA\Property(property: 'amount', type: 'number', format: 'float', example: 125.75),
                    new OA\Property(property: 'movement_date', type: 'string', format: 'date', example: '2026-06-15'),
                    new OA\Property(property: 'reference', type: 'string', nullable: true, example: 'REF-001'),
                    new OA\Property(property: 'voucher_number', type: 'string', nullable: true, example: 'VCH-001'),
                    new OA\Property(property: 'description', type: 'string', example: 'Depósito registrado manualmente'),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Movimiento bancario registrado'),
            new OA\Response(response: 422, description: 'Datos inválidos'),
        ]
    )]
    public function bankAccountMovementsStore(): void {}

    #[OA\Get(path: '/api/condominiums/{condominium}/bank-statement-imports', operationId: 'bankStatementImportsIndex', summary: 'Listar importaciones de estado bancario', tags: ['Economía'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Importaciones encontradas')])]
    public function bankStatementImportsIndex(): void {}

    #[OA\Post(
        path: '/api/condominiums/{condominium}/bank-statement-imports',
        operationId: 'bankStatementImportsStore',
        summary: 'Importar estado bancario',
        tags: ['Economía'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['condominium_payment_method_id', 'period_year', 'period_month', 'rows'],
                properties: [
                    new OA\Property(property: 'condominium_payment_method_id', type: 'integer', example: 1),
                    new OA\Property(property: 'period_year', type: 'integer', example: 2026),
                    new OA\Property(property: 'period_month', type: 'integer', example: 6),
                    new OA\Property(property: 'original_file_name', type: 'string', nullable: true, example: 'estado-junio.csv'),
                    new OA\Property(
                        property: 'rows',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'transaction_date', type: 'string', format: 'date', example: '2026-06-16'),
                                new OA\Property(property: 'reference', type: 'string', nullable: true, example: 'TRX-100'),
                                new OA\Property(property: 'voucher_number', type: 'string', nullable: true, example: 'DEP-100'),
                                new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Pago alícuota'),
                                new OA\Property(property: 'amount', type: 'number', format: 'float', example: 50),
                                new OA\Property(property: 'direction', type: 'string', enum: ['credit', 'debit'], example: 'credit'),
                            ],
                            type: 'object'
                        )
                    ),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Estado bancario importado'),
            new OA\Response(response: 422, description: 'Datos inválidos'),
        ]
    )]
    public function bankStatementImportsStore(): void {}

    #[OA\Get(path: '/api/condominiums/{condominium}/bank-reconciliations', operationId: 'bankReconciliationsIndex', summary: 'Listar conciliaciones bancarias', tags: ['Economía'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Conciliaciones encontradas')])]
    public function bankReconciliationsIndex(): void {}

    #[OA\Post(
        path: '/api/condominiums/{condominium}/bank-reconciliations',
        operationId: 'bankReconciliationsStore',
        summary: 'Crear conciliación bancaria',
        tags: ['Economía'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['condominium_payment_method_id', 'period_year', 'period_month', 'bank_statement_balance'],
                properties: [
                    new OA\Property(property: 'condominium_payment_method_id', type: 'integer', example: 1),
                    new OA\Property(property: 'bank_statement_import_id', type: 'integer', nullable: true, example: 1),
                    new OA\Property(property: 'period_year', type: 'integer', example: 2026),
                    new OA\Property(property: 'period_month', type: 'integer', example: 6),
                    new OA\Property(property: 'bank_statement_balance', type: 'number', format: 'float', example: 1750.25),
                    new OA\Property(property: 'notes', type: 'string', nullable: true, example: 'Conciliación mensual'),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Conciliación creada'),
            new OA\Response(response: 422, description: 'Datos inválidos'),
        ]
    )]
    public function bankReconciliationsStore(): void {}

    #[OA\Get(path: '/api/condominiums/{condominium}/expense-categories', operationId: 'expenseCategoriesIndex', summary: 'Listar categorías de gasto', tags: ['Gastos'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Categorías encontradas')])]
    public function expenseCategoriesIndex(): void {}

    #[OA\Post(
        path: '/api/condominiums/{condominium}/expense-categories',
        operationId: 'expenseCategoriesStore',
        summary: 'Crear categoría de gasto',
        tags: ['Gastos'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Mantenimiento'),
                    new OA\Property(property: 'code', type: 'string', nullable: true, example: 'maintenance'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Gastos de mantenimiento general'),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Categoría creada'),
            new OA\Response(response: 422, description: 'Datos inválidos'),
        ]
    )]
    public function expenseCategoriesStore(): void {}

    #[OA\Get(path: '/api/condominiums/{condominium}/expenses', operationId: 'expensesIndex', summary: 'Listar gastos', tags: ['Gastos'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Gastos encontrados')])]
    public function expensesIndex(): void {}

    #[OA\Post(
        path: '/api/condominiums/{condominium}/expenses',
        operationId: 'expensesStore',
        summary: 'Registrar gasto',
        tags: ['Gastos'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['expense_category_id', 'supplier_name', 'description', 'amount', 'expense_date'],
                properties: [
                    new OA\Property(property: 'expense_category_id', type: 'integer', example: 1),
                    new OA\Property(property: 'condominium_payment_method_id', type: 'integer', nullable: true, example: 1),
                    new OA\Property(property: 'supplier_name', type: 'string', example: 'Servicios Generales Quito'),
                    new OA\Property(property: 'supplier_document', type: 'string', nullable: true, example: '1790012345001'),
                    new OA\Property(property: 'description', type: 'string', example: 'Mantenimiento de bomba de agua'),
                    new OA\Property(property: 'amount', type: 'number', format: 'float', example: 120.5),
                    new OA\Property(property: 'expense_date', type: 'string', format: 'date', example: '2026-06-14'),
                    new OA\Property(property: 'paid_at', type: 'string', format: 'date-time', nullable: true, example: '2026-06-15T10:00:00Z'),
                    new OA\Property(property: 'reference', type: 'string', nullable: true, example: 'FAC-001'),
                    new OA\Property(property: 'voucher_number', type: 'string', nullable: true, example: 'EG-001'),
                    new OA\Property(property: 'status', type: 'string', enum: ['pending', 'paid', 'cancelled', 'rejected'], nullable: true, example: 'paid'),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Gasto registrado'),
            new OA\Response(response: 422, description: 'Datos inválidos'),
        ]
    )]
    public function expensesStore(): void {}

    #[OA\Get(path: '/api/condominiums/{condominium}/extraordinary-fees', operationId: 'extraordinaryFeesIndex', summary: 'Listar cuotas extraordinarias', tags: ['Economía'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Cuotas extraordinarias encontradas')])]
    public function extraordinaryFeesIndex(): void {}

    #[OA\Post(
        path: '/api/condominiums/{condominium}/extraordinary-fees',
        operationId: 'extraordinaryFeesStore',
        summary: 'Crear cuota extraordinaria',
        tags: ['Economía'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'amount', 'starts_on', 'ends_on', 'apply_to'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Reparación de ascensor'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Pago extraordinario para reparación'),
                    new OA\Property(property: 'amount', type: 'number', format: 'float', example: 25),
                    new OA\Property(property: 'starts_on', type: 'string', format: 'date', example: '2026-06-01'),
                    new OA\Property(property: 'ends_on', type: 'string', format: 'date', example: '2026-06-30'),
                    new OA\Property(property: 'apply_to', type: 'string', enum: ['all_units', 'selected_units'], example: 'all_units'),
                    new OA\Property(property: 'unit_ids', type: 'array', nullable: true, items: new OA\Items(type: 'integer'), example: [1, 2]),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Cuota extraordinaria creada'),
            new OA\Response(response: 422, description: 'Datos inválidos'),
        ]
    )]
    public function extraordinaryFeesStore(): void {}

    #[OA\Get(path: '/api/condominiums/{condominium}/payments', operationId: 'paymentsIndex', summary: 'Listar pagos', tags: ['Pagos'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Pagos encontrados')])]
    public function paymentsIndex(): void {}

    #[OA\Post(
        path: '/api/condominiums/{condominium}/payments',
        operationId: 'paymentsStore',
        summary: 'Registrar pago',
        tags: ['Pagos'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['unit_id', 'amount'],
                properties: [
                    new OA\Property(property: 'unit_id', type: 'integer', example: 1),
                    new OA\Property(property: 'user_id', type: 'integer', nullable: true, example: 1),
                    new OA\Property(property: 'condominium_payment_method_id', type: 'integer', nullable: true, example: 1),
                    new OA\Property(property: 'amount', type: 'number', format: 'float', example: 50),
                    new OA\Property(property: 'paid_at', type: 'string', format: 'date-time', nullable: true, example: '2026-06-16T12:00:00Z'),
                    new OA\Property(property: 'reference', type: 'string', nullable: true, example: 'DEP-001'),
                    new OA\Property(property: 'voucher_number', type: 'string', nullable: true, example: 'VCH-001'),
                    new OA\Property(property: 'notes', type: 'string', nullable: true, example: 'Pago mensual'),
                    new OA\Property(property: 'monthly_fee_ids', type: 'array', nullable: true, items: new OA\Items(type: 'integer'), example: [1]),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Pago registrado'),
            new OA\Response(response: 422, description: 'Datos inválidos'),
        ]
    )]
    public function paymentsStore(): void {}

    #[OA\Get(path: '/api/condominiums/{condominium}/payment-orders', operationId: 'paymentOrdersIndex', summary: 'Listar órdenes de pago', tags: ['Pagos'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Órdenes de pago encontradas')])]
    public function paymentOrdersIndex(): void {}

    #[OA\Post(
        path: '/api/condominiums/{condominium}/payment-orders',
        operationId: 'paymentOrdersStore',
        summary: 'Crear orden de pago',
        tags: ['Pagos'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['unit_id', 'amount'],
                properties: [
                    new OA\Property(property: 'unit_id', type: 'integer', example: 1),
                    new OA\Property(property: 'user_id', type: 'integer', nullable: true, example: 1),
                    new OA\Property(property: 'amount', type: 'number', format: 'float', example: 50),
                    new OA\Property(property: 'expires_at', type: 'string', format: 'date-time', nullable: true, example: '2026-06-30T23:59:59Z'),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Orden de pago creada'),
            new OA\Response(response: 422, description: 'Datos inválidos'),
        ]
    )]
    public function paymentOrdersStore(): void {}

    #[OA\Get(path: '/api/condominiums/{condominium}/treasury-handovers', operationId: 'treasuryHandoversIndex', summary: 'Listar entregas de tesorería', tags: ['Tesorería'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Entregas encontradas')])]
    public function treasuryHandoversIndex(): void {}

    #[OA\Post(
        path: '/api/condominiums/{condominium}/treasury-handovers/calculate',
        operationId: 'treasuryHandoversCalculate',
        summary: 'Calcular entrega de tesorería',
        tags: ['Tesorería'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['period_starts_on', 'period_ends_on', 'condominium_payment_method_id'],
                properties: [
                    new OA\Property(property: 'period_starts_on', type: 'string', format: 'date', example: '2026-06-01'),
                    new OA\Property(property: 'period_ends_on', type: 'string', format: 'date', example: '2026-06-30'),
                    new OA\Property(property: 'condominium_payment_method_id', type: 'integer', example: 1),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Cálculo generado'),
            new OA\Response(response: 422, description: 'Datos inválidos'),
        ]
    )]
    public function treasuryHandoversCalculate(): void {}

    #[OA\Post(
        path: '/api/condominiums/{condominium}/treasury-handovers',
        operationId: 'treasuryHandoversStore',
        summary: 'Registrar entrega de tesorería',
        tags: ['Tesorería'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['type', 'period_starts_on', 'condominium_payment_method_id', 'bank_balance'],
                properties: [
                    new OA\Property(property: 'type', type: 'string', enum: ['reception', 'handover'], example: 'handover'),
                    new OA\Property(property: 'period_starts_on', type: 'string', format: 'date', example: '2026-06-01'),
                    new OA\Property(property: 'period_ends_on', type: 'string', format: 'date', nullable: true, example: '2026-06-30'),
                    new OA\Property(property: 'condominium_payment_method_id', type: 'integer', example: 1),
                    new OA\Property(property: 'delivered_by_user_id', type: 'integer', nullable: true, example: 1),
                    new OA\Property(property: 'received_by_user_id', type: 'integer', nullable: true, example: 2),
                    new OA\Property(property: 'bank_balance', type: 'number', format: 'float', example: 1750.25),
                    new OA\Property(property: 'cash_balance', type: 'number', format: 'float', nullable: true, example: 100),
                    new OA\Property(property: 'notes', type: 'string', nullable: true, example: 'Entrega mensual'),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Entrega registrada'),
            new OA\Response(response: 422, description: 'Datos inválidos'),
        ]
    )]
    public function treasuryHandoversStore(): void {}

    #[OA\Get(path: '/api/condominiums/{condominium}/units/{unit}/account-movements', operationId: 'unitAccountMovementsIndex', summary: 'Listar movimientos de cuenta de unidad', tags: ['Unidades'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Movimientos de cuenta encontrados')])]
    public function unitAccountMovementsIndex(): void {}

    #[OA\Get(path: '/api/my/units', operationId: 'myUnitsIndex', summary: 'Listar mis unidades', tags: ['Unidades'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Unidades del usuario autenticado')])]
    public function myUnitsIndex(): void {}

    #[OA\Patch(
        path: '/api/condominiums/{condominium}/units/{unit}/users/{user}/deactivate',
        operationId: 'unitUsersDeactivate',
        summary: 'Inactivar relación de persona con unidad',
        tags: ['Personas por unidad'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['ended_at'],
                properties: [
                    new OA\Property(property: 'ended_at', type: 'string', format: 'date', example: '2026-06-30'),
                    new OA\Property(property: 'disable_access', type: 'boolean', nullable: true, example: true),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Relación inactivada'),
            new OA\Response(response: 422, description: 'Datos inválidos'),
        ]
    )]
    public function unitUsersDeactivate(): void {}

    #[OA\Patch(
        path: '/api/condominiums/{condominium}/units/{unit}/billing-responsible',
        operationId: 'unitUsersBillingResponsible',
        summary: 'Actualizar responsable de facturación de unidad',
        tags: ['Personas por unidad'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_id'],
                properties: [
                    new OA\Property(property: 'user_id', type: 'integer', example: 1),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Responsable actualizado'),
            new OA\Response(response: 422, description: 'Datos inválidos'),
        ]
    )]
    public function unitUsersBillingResponsible(): void {}

    #[OA\Patch(
        path: '/api/condominiums/{condominium}/units/{unit}/access-invitations/{invitation}/cancel',
        operationId: 'accessInvitationsCancel',
        summary: 'Cancelar invitación de acceso',
        tags: ['Invitaciones'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'cancel_reason', type: 'string', nullable: true, example: 'Solicitud del administrador'),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Invitación cancelada'),
            new OA\Response(response: 404, description: 'Invitación no encontrada'),
        ]
    )]
    public function accessInvitationsCancel(): void {}

    #[OA\Post(path: '/api/register', operationId: 'legacyAuthRegister', summary: 'Registrar usuario (ruta legacy)', tags: ['Autenticación'], responses: [new OA\Response(response: 201, description: 'Usuario registrado'), new OA\Response(response: 422, description: 'Datos inválidos')])]
    public function legacyAuthRegister(): void {}

    #[OA\Post(path: '/api/login', operationId: 'legacyAuthLogin', summary: 'Iniciar sesión (ruta legacy)', tags: ['Autenticación'], responses: [new OA\Response(response: 200, description: 'Sesión iniciada'), new OA\Response(response: 401, description: 'Credenciales inválidas')])]
    public function legacyAuthLogin(): void {}

    #[OA\Post(path: '/api/logout', operationId: 'legacyAuthLogout', summary: 'Cerrar sesión (ruta legacy)', tags: ['Autenticación'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Sesión cerrada'), new OA\Response(response: 401, description: 'No autenticado')])]
    public function legacyAuthLogout(): void {}

    #[OA\Get(path: '/api/user', operationId: 'legacyAuthUser', summary: 'Obtener usuario autenticado (ruta legacy)', tags: ['Autenticación'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Usuario autenticado')])]
    public function legacyAuthUser(): void {}

    #[OA\Get(path: '/api/tenants', operationId: 'tenantsIndex', summary: 'Listar tenants legacy', tags: ['Tenants'], security: [['apiToken' => []]], responses: [new OA\Response(response: 200, description: 'Tenants encontrados')])]
    public function tenantsIndex(): void {}

    #[OA\Post(
        path: '/api/tenants',
        operationId: 'tenantsStore',
        summary: 'Crear tenant legacy',
        tags: ['Tenants'],
        security: [['apiToken' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Tenant Demo'),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Tenant creado'),
            new OA\Response(response: 422, description: 'Datos inválidos'),
        ]
    )]
    public function tenantsStore(): void {}

    #[OA\Get(path: '/api/roles', operationId: 'tenantRolesIndex', summary: 'Listar roles legacy', tags: ['Tenants'], security: [['apiToken' => []]], responses: [new OA\Response(response: 200, description: 'Roles encontrados')])]
    public function tenantRolesIndex(): void {}

    #[OA\Get(path: '/api/condominiums/{condominium}/visitors', operationId: 'visitorsIndex', summary: 'Listar visitantes', tags: ['Operación diaria'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Visitantes encontrados')])]
    public function visitorsIndex(): void {}

    #[OA\Post(path: '/api/condominiums/{condominium}/visitors', operationId: 'visitorsStore', summary: 'Registrar visitante', tags: ['Operación diaria'], security: [['bearerAuth' => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['name'], properties: [new OA\Property(property: 'name', type: 'string', example: 'Carlos Visitante'), new OA\Property(property: 'document_type_id', type: 'integer', nullable: true, example: 1), new OA\Property(property: 'document_number', type: 'string', nullable: true, example: '1700000001'), new OA\Property(property: 'phone', type: 'string', nullable: true, example: '0991112222'), new OA\Property(property: 'email', type: 'string', nullable: true, example: 'visitante@example.com'), new OA\Property(property: 'notes', type: 'string', nullable: true, example: 'Visitante frecuente')], type: 'object')), responses: [new OA\Response(response: 201, description: 'Visitante registrado'), new OA\Response(response: 422, description: 'Datos inválidos')])]
    public function visitorsStore(): void {}

    #[OA\Get(path: '/api/condominiums/{condominium}/visits', operationId: 'visitsIndex', summary: 'Listar visitas', tags: ['Operación diaria'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Visitas encontradas')])]
    public function visitsIndex(): void {}

    #[OA\Post(path: '/api/condominiums/{condominium}/visits', operationId: 'visitsStore', summary: 'Registrar visita', tags: ['Operación diaria'], security: [['bearerAuth' => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['unit_id', 'visitor_id'], properties: [new OA\Property(property: 'unit_id', type: 'integer', example: 1), new OA\Property(property: 'visitor_id', type: 'integer', example: 1), new OA\Property(property: 'purpose', type: 'string', nullable: true, example: 'Entrega de documentos'), new OA\Property(property: 'scheduled_at', type: 'string', format: 'date-time', nullable: true, example: '2026-06-20 10:00:00'), new OA\Property(property: 'valid_until', type: 'string', format: 'date-time', nullable: true, example: '2026-06-20 18:00:00'), new OA\Property(property: 'status', type: 'string', nullable: true, example: 'authorized')], type: 'object')), responses: [new OA\Response(response: 201, description: 'Visita registrada'), new OA\Response(response: 422, description: 'Datos inválidos')])]
    public function visitsStore(): void {}

    #[OA\Patch(path: '/api/condominiums/{condominium}/visits/{visit}/authorize', operationId: 'visitsAuthorize', summary: 'Autorizar visita', tags: ['Operación diaria'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Visita autorizada'), new OA\Response(response: 404, description: 'Visita no encontrada')])]
    public function visitsAuthorize(): void {}

    #[OA\Post(path: '/api/condominiums/{condominium}/visits/{visit}/entry', operationId: 'visitsEntry', summary: 'Registrar ingreso de visita', tags: ['Operación diaria'], security: [['bearerAuth' => []]], requestBody: new OA\RequestBody(required: false, content: new OA\JsonContent(properties: [new OA\Property(property: 'logged_at', type: 'string', format: 'date-time', nullable: true, example: '2026-06-20 10:05:00'), new OA\Property(property: 'notes', type: 'string', nullable: true, example: 'Ingreso por garita principal')], type: 'object')), responses: [new OA\Response(response: 201, description: 'Ingreso registrado'), new OA\Response(response: 422, description: 'Visita no autorizada')])]
    public function visitsEntry(): void {}

    #[OA\Post(path: '/api/condominiums/{condominium}/visits/{visit}/exit', operationId: 'visitsExit', summary: 'Registrar salida de visita', tags: ['Operación diaria'], security: [['bearerAuth' => []]], requestBody: new OA\RequestBody(required: false, content: new OA\JsonContent(properties: [new OA\Property(property: 'logged_at', type: 'string', format: 'date-time', nullable: true, example: '2026-06-20 11:05:00'), new OA\Property(property: 'notes', type: 'string', nullable: true, example: 'Salida normal')], type: 'object')), responses: [new OA\Response(response: 201, description: 'Salida registrada'), new OA\Response(response: 422, description: 'Visita no autorizada')])]
    public function visitsExit(): void {}

    #[OA\Get(path: '/api/condominiums/{condominium}/common-areas', operationId: 'commonAreasIndex', summary: 'Listar áreas comunes', tags: ['Operación diaria'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Áreas comunes encontradas')])]
    public function commonAreasIndex(): void {}

    #[OA\Post(path: '/api/condominiums/{condominium}/common-areas', operationId: 'commonAreasStore', summary: 'Crear área común', tags: ['Operación diaria'], security: [['bearerAuth' => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['name'], properties: [new OA\Property(property: 'name', type: 'string', example: 'Sala comunal'), new OA\Property(property: 'code', type: 'string', nullable: true, example: 'sala_comunal'), new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Área social'), new OA\Property(property: 'capacity', type: 'integer', nullable: true, example: 40), new OA\Property(property: 'reservation_fee', type: 'number', nullable: true, example: 15), new OA\Property(property: 'is_reservable', description: 'Indica si el área admite reservas.', type: 'boolean', nullable: true, example: true), new OA\Property(property: 'requires_approval', type: 'boolean', nullable: true, example: true), new OA\Property(property: 'is_active', type: 'boolean', nullable: true, example: true)], type: 'object')), responses: [new OA\Response(response: 201, description: 'Área común creada'), new OA\Response(response: 422, description: 'Datos inválidos')])]
    public function commonAreasStore(): void {}

    #[OA\Get(path: '/api/condominiums/{condominium}/common-area-reservations', operationId: 'commonAreaReservationsIndex', summary: 'Listar reservas de áreas comunes', tags: ['Operación diaria'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Reservas encontradas')])]
    public function commonAreaReservationsIndex(): void {}

    #[OA\Post(path: '/api/condominiums/{condominium}/common-area-reservations', operationId: 'commonAreaReservationsStore', summary: 'Crear reserva de área común', description: 'Solo admite áreas activas con is_reservable=true.', tags: ['Operación diaria'], security: [['bearerAuth' => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['common_area_id', 'unit_id', 'starts_at', 'ends_at'], properties: [new OA\Property(property: 'common_area_id', type: 'integer', example: 1), new OA\Property(property: 'unit_id', type: 'integer', example: 1), new OA\Property(property: 'starts_at', type: 'string', format: 'date-time', example: '2026-06-25 18:00:00'), new OA\Property(property: 'ends_at', type: 'string', format: 'date-time', example: '2026-06-25 21:00:00'), new OA\Property(property: 'attendees_count', type: 'integer', nullable: true, example: 12), new OA\Property(property: 'notes', type: 'string', nullable: true, example: 'Reunión familiar')], type: 'object')), responses: [new OA\Response(response: 201, description: 'Reserva creada'), new OA\Response(response: 422, description: 'Datos inválidos, área no reservable o cruce de horarios')])]
    public function commonAreaReservationsStore(): void {}

    #[OA\Patch(path: '/api/condominiums/{condominium}/common-area-reservations/{reservation}/approve', operationId: 'commonAreaReservationsApprove', summary: 'Aprobar reserva', tags: ['Operación diaria'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Reserva aprobada'), new OA\Response(response: 404, description: 'Reserva no encontrada')])]
    public function commonAreaReservationsApprove(): void {}

    #[OA\Patch(path: '/api/condominiums/{condominium}/common-area-reservations/{reservation}/cancel', operationId: 'commonAreaReservationsCancel', summary: 'Cancelar reserva', tags: ['Operación diaria'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Reserva cancelada'), new OA\Response(response: 404, description: 'Reserva no encontrada')])]
    public function commonAreaReservationsCancel(): void {}

    #[OA\Get(path: '/api/condominiums/{condominium}/incidents', operationId: 'incidentsIndex', summary: 'Listar incidentes', tags: ['Operación diaria'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Incidentes encontrados')])]
    public function incidentsIndex(): void {}

    #[OA\Post(path: '/api/condominiums/{condominium}/incidents', operationId: 'incidentsStore', summary: 'Registrar incidente', tags: ['Operación diaria'], security: [['bearerAuth' => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['title', 'description'], properties: [new OA\Property(property: 'unit_id', type: 'integer', nullable: true, example: 1), new OA\Property(property: 'assigned_to_user_id', type: 'integer', nullable: true, example: 1), new OA\Property(property: 'title', type: 'string', example: 'Luminaria dañada'), new OA\Property(property: 'description', type: 'string', example: 'No enciende en el ingreso'), new OA\Property(property: 'category', type: 'string', nullable: true, example: 'security'), new OA\Property(property: 'priority', type: 'string', nullable: true, example: 'medium'), new OA\Property(property: 'occurred_at', type: 'string', format: 'date-time', nullable: true, example: '2026-06-18 19:00:00')], type: 'object')), responses: [new OA\Response(response: 201, description: 'Incidente registrado'), new OA\Response(response: 422, description: 'Datos inválidos')])]
    public function incidentsStore(): void {}

    #[OA\Get(path: '/api/condominiums/{condominium}/maintenances', operationId: 'maintenancesIndex', summary: 'Listar mantenimientos', tags: ['Operación diaria'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Mantenimientos encontrados')])]
    public function maintenancesIndex(): void {}

    #[OA\Post(path: '/api/condominiums/{condominium}/maintenances', operationId: 'maintenancesStore', summary: 'Registrar mantenimiento', tags: ['Operación diaria'], security: [['bearerAuth' => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['title'], properties: [new OA\Property(property: 'common_area_id', type: 'integer', nullable: true, example: 1), new OA\Property(property: 'assigned_to_user_id', type: 'integer', nullable: true, example: 1), new OA\Property(property: 'title', type: 'string', example: 'Mantenimiento preventivo'), new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Revisión mensual'), new OA\Property(property: 'type', type: 'string', nullable: true, example: 'preventive'), new OA\Property(property: 'priority', type: 'string', nullable: true, example: 'high'), new OA\Property(property: 'scheduled_starts_at', type: 'string', format: 'date-time', nullable: true, example: '2026-06-28 09:00:00'), new OA\Property(property: 'scheduled_ends_at', type: 'string', format: 'date-time', nullable: true, example: '2026-06-28 12:00:00'), new OA\Property(property: 'cost', type: 'number', nullable: true, example: 80), new OA\Property(property: 'tasks', type: 'array', items: new OA\Items(properties: [new OA\Property(property: 'title', type: 'string', example: 'Revisar presión')], type: 'object'))], type: 'object')), responses: [new OA\Response(response: 201, description: 'Mantenimiento registrado'), new OA\Response(response: 422, description: 'Datos inválidos')])]
    public function maintenancesStore(): void {}

    #[OA\Post(path: '/api/condominiums/{condominium}/maintenances/{maintenance}/tasks', operationId: 'maintenanceTasksStore', summary: 'Crear tarea de mantenimiento', tags: ['Operación diaria'], security: [['bearerAuth' => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['title'], properties: [new OA\Property(property: 'title', type: 'string', example: 'Revisar presión'), new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Validar presión de bomba'), new OA\Property(property: 'assigned_to_user_id', type: 'integer', nullable: true, example: 1), new OA\Property(property: 'due_at', type: 'string', format: 'date-time', nullable: true, example: '2026-06-28 12:00:00')], type: 'object')), responses: [new OA\Response(response: 201, description: 'Tarea creada'), new OA\Response(response: 422, description: 'Datos inválidos')])]
    public function maintenanceTasksStore(): void {}

    #[OA\Patch(path: '/api/condominiums/{condominium}/maintenances/{maintenance}/tasks/{task}/complete', operationId: 'maintenanceTasksComplete', summary: 'Completar tarea de mantenimiento', tags: ['Operación diaria'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Tarea completada'), new OA\Response(response: 404, description: 'Tarea no encontrada')])]
    public function maintenanceTasksComplete(): void {}
}
