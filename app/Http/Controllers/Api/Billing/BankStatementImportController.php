<?php

namespace App\Http\Controllers\Api\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Billing\BankStatementImportStoreRequest;
use App\Models\BankStatementImport;
use App\Models\Condominium;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class BankStatementImportController extends Controller
{
    public function index(Condominium $condominium): JsonResponse
    {
        return ApiResponse::success(BankStatementImport::where('condominium_id', $condominium->id)->with('rows')->latest()->get(), 'Importaciones bancarias encontradas.');
    }

    public function store(BankStatementImportStoreRequest $request, Condominium $condominium): JsonResponse
    {
        $data = $request->validated();

        $import = DB::transaction(function () use ($condominium, $data, $request): BankStatementImport {
            $import = BankStatementImport::create([
                'condominium_id' => $condominium->id,
                'condominium_payment_method_id' => $data['condominium_payment_method_id'],
                'uploaded_by_user_id' => $request->user()?->id,
                'original_file_name' => $data['original_file_name'] ?? 'manual-import.json',
                'period_year' => $data['period_year'],
                'period_month' => $data['period_month'],
                'status' => 'processed',
                'total_rows' => count($data['rows']),
            ]);

            foreach ($data['rows'] as $row) {
                $match = DB::table('payments')
                    ->where('condominium_id', $condominium->id)
                    ->where(function ($query) use ($row): void {
                        $query->when($row['voucher_number'] ?? null, fn ($q, $value) => $q->orWhere('voucher_number', $value))
                            ->when($row['reference'] ?? null, fn ($q, $value) => $q->orWhere('reference', $value));
                    })
                    ->first();

                $status = $match && (float) $match->amount === (float) $row['amount'] ? 'matched' : ($match ? 'difference' : 'unmatched');

                $import->rows()->create([
                    ...$row,
                    'raw_data' => $row,
                    'matched_type' => $match ? 'payment' : null,
                    'matched_id' => $match?->id,
                    'match_status' => $status,
                    'difference_amount' => $match ? abs((float) $match->amount - (float) $row['amount']) : 0,
                ]);
            }

            $import->update([
                'matched_rows' => $import->rows()->where('match_status', 'matched')->count(),
                'unmatched_rows' => $import->rows()->where('match_status', 'unmatched')->count(),
                'difference_rows' => $import->rows()->where('match_status', 'difference')->count(),
            ]);

            return $import;
        });

        return ApiResponse::success($import->load('rows'), 'Movimientos bancarios importados correctamente.', 201);
    }
}
