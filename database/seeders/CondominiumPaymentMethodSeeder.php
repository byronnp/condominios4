<?php

namespace Database\Seeders;

use App\Models\Catalog;
use App\Models\Condominium;
use Illuminate\Database\Seeder;

class CondominiumPaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $condominium = Condominium::where('slug', 'condominio-los-cedros')->first();
        $paymentMethods = Catalog::where('code', 'payment_methods')->first()?->items()->get()->keyBy('code');

        if (! $condominium || ! $paymentMethods) {
            return;
        }

        $condominium->paymentMethods()->updateOrCreate([
            'catalog_item_id' => $paymentMethods->get('transferencia')?->id,
            'account_number' => '2200123456',
        ], [
            'account_holder' => 'Condominio Los Cedros',
            'bank_name' => 'Banco Pichincha',
            'account_type' => 'Ahorros',
            'identification' => '1799999999001',
            'email' => 'pagos@loscedros.ec',
            'phone' => '0999999999',
            'instructions' => 'Enviar comprobante al correo de pagos.',
            'is_default' => true,
            'is_active' => true,
        ]);

        $condominium->paymentMethods()->updateOrCreate([
            'catalog_item_id' => $paymentMethods->get('efectivo')?->id,
            'account_holder' => 'Administración Condominio Los Cedros',
        ], [
            'instructions' => 'Pago en oficina administrativa de lunes a viernes de 09:00 a 17:00.',
            'is_default' => false,
            'is_active' => true,
        ]);
    }
}
