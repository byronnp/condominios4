<?php

namespace App\Http\Controllers\Api\Units;

use App\Http\Controllers\Controller;
use App\Models\Catalog;
use App\Models\Condominium;
use App\Models\Unit;
use App\Models\User;
use App\Rules\ValidCatalogItem;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

class UnitUserController extends Controller
{
    #[OA\Get(path: '/api/condominiums/{condominium}/units/{unit}/users', operationId: 'unitUsersIndex', summary: 'Listar personas de unidad', tags: ['Personas por unidad'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Personas encontradas')])]
    public function index(Condominium $condominium, Unit $unit): JsonResponse
    {
        $this->assertUnit($condominium, $unit);

        return ApiResponse::success($this->unitPeople($unit), 'Personas de la unidad encontradas.');
    }

    #[OA\Post(path: '/api/condominiums/{condominium}/units/{unit}/users', operationId: 'unitUsersStore', summary: 'Agregar persona a unidad', tags: ['Personas por unidad'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Persona agregada')])]
    public function store(Request $request, Condominium $condominium, Unit $unit): JsonResponse
    {
        $this->assertUnit($condominium, $unit);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'size:2'],
            'document_type_id' => ['required', 'integer', new ValidCatalogItem('document_types')],
            'document_number' => ['required', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:50'],
            'secondary_phone' => ['nullable', 'string', 'max:50'],
            'relationship_type_id' => ['required', 'integer', new ValidCatalogItem('resident_relationship_types')],
            'started_at' => ['nullable', 'date'],
            'ended_at' => ['nullable', 'date', 'after_or_equal:started_at'],
            'is_primary' => ['nullable', 'boolean'],
            'is_billing_responsible' => ['nullable', 'boolean'],
        ]);

        $relationshipCode = $this->relationshipCode((int) $data['relationship_type_id']);
        abort_if(! $this->canManageRelationship($request, $condominium, $unit, $relationshipCode), 403, 'No autorizado para agregar esta relación.');

        $user = DB::transaction(function () use ($unit, $data): User {
            $user = User::updateOrCreate([
                'country' => $data['country'],
                'document_type_id' => $data['document_type_id'],
                'document_number' => $data['document_number'],
            ], [
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'secondary_phone' => $data['secondary_phone'] ?? null,
                'email' => null,
                'password' => null,
                'is_access_enabled' => false,
            ]);

            if (($data['is_billing_responsible'] ?? false) === true) {
                DB::table('unit_user')
                    ->where('unit_id', $unit->id)
                    ->update(['is_billing_responsible' => false]);
            }

            DB::table('unit_user')->updateOrInsert([
                'unit_id' => $unit->id,
                'user_id' => $user->id,
                'relationship_type_id' => $data['relationship_type_id'],
            ], [
                'started_at' => $data['started_at'] ?? now()->toDateString(),
                'ended_at' => $data['ended_at'] ?? null,
                'is_primary' => $data['is_primary'] ?? false,
                'is_billing_responsible' => $data['is_billing_responsible'] ?? false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ]);

            return $user;
        });

        return ApiResponse::success([
            'user' => $user->fresh('documentType'),
            'unit_relation' => $this->unitPeople($unit)->firstWhere('id', $user->id),
        ], 'Persona agregada a la unidad correctamente.', 201);
    }

    public function deactivate(Request $request, Condominium $condominium, Unit $unit, User $user): JsonResponse
    {
        $this->assertUnit($condominium, $unit);

        $data = $request->validate([
            'ended_at' => ['required', 'date'],
            'disable_access' => ['nullable', 'boolean'],
        ]);

        DB::table('unit_user')
            ->where('unit_id', $unit->id)
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->update([
                'ended_at' => $data['ended_at'],
                'is_active' => false,
                'updated_at' => now(),
            ]);

        if (($data['disable_access'] ?? false) === true) {
            $user->update(['is_access_enabled' => false]);
        }

        return ApiResponse::success(message: 'Relación con la unidad inactivada correctamente.');
    }

    public function billingResponsible(Request $request, Condominium $condominium, Unit $unit): JsonResponse
    {
        $this->assertUnit($condominium, $unit);

        $data = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
        ]);

        $relation = DB::table('unit_user')
            ->where('unit_id', $unit->id)
            ->where('user_id', $data['user_id'])
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->first();

        abort_if(! $relation, 422, 'El usuario no tiene una relación activa con la unidad.');

        DB::transaction(function () use ($unit, $data): void {
            DB::table('unit_user')->where('unit_id', $unit->id)->update(['is_billing_responsible' => false]);
            DB::table('unit_user')
                ->where('unit_id', $unit->id)
                ->where('user_id', $data['user_id'])
                ->update(['is_billing_responsible' => true, 'updated_at' => now()]);
        });

        return ApiResponse::success($this->unitPeople($unit), 'Responsable de facturación actualizado correctamente.');
    }

    private function assertUnit(Condominium $condominium, Unit $unit): void
    {
        abort_if($unit->condominium_id !== $condominium->id, 404);
    }

    private function unitPeople(Unit $unit): Collection
    {
        return DB::table('unit_user')
            ->join('users', 'users.id', '=', 'unit_user.user_id')
            ->join('catalog_items', 'catalog_items.id', '=', 'unit_user.relationship_type_id')
            ->where('unit_user.unit_id', $unit->id)
            ->whereNull('unit_user.deleted_at')
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.phone',
                'users.secondary_phone',
                'users.is_access_enabled',
                'catalog_items.code as relationship_code',
                'catalog_items.name as relationship_name',
                'unit_user.started_at',
                'unit_user.ended_at',
                'unit_user.is_primary',
                'unit_user.is_billing_responsible',
                'unit_user.is_active',
            ])
            ->orderByDesc('unit_user.is_active')
            ->orderBy('users.name')
            ->get();
    }

    private function relationshipCode(int $relationshipTypeId): string
    {
        return Catalog::where('code', 'resident_relationship_types')
            ->firstOrFail()
            ->items()
            ->whereKey($relationshipTypeId)
            ->value('code');
    }

    private function canManageRelationship(Request $request, Condominium $condominium, Unit $unit, string $relationshipCode): bool
    {
        if ($request->user()->hasPermission('unit_users.manage_all', $condominium) || $request->user()->hasPermission('unit_users.create', $condominium)) {
            return true;
        }

        $allowedForOwner = ['inquilino', 'familiar', 'ocupante', 'autorizado'];

        return in_array($relationshipCode, $allowedForOwner, true)
            && $this->isOwnerOfUnit($request->user(), $unit);
    }

    private function isOwnerOfUnit(User $user, Unit $unit): bool
    {
        return DB::table('unit_user')
            ->join('catalog_items', 'catalog_items.id', '=', 'unit_user.relationship_type_id')
            ->where('unit_user.unit_id', $unit->id)
            ->where('unit_user.user_id', $user->id)
            ->where('unit_user.is_active', true)
            ->whereNull('unit_user.deleted_at')
            ->whereIn('catalog_items.code', ['propietario', 'copropietario'])
            ->exists();
    }
}
