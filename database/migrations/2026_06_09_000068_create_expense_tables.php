<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condominium_id')->constrained('condominiums')->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['condominium_id', 'code']);
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condominium_id')->constrained('condominiums')->cascadeOnDelete();
            $table->foreignId('expense_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('condominium_payment_method_id')->nullable()->constrained('condominium_payment_methods', indexName: 'expenses_cpm_fk')->nullOnDelete();
            $table->string('supplier_name');
            $table->string('supplier_document')->nullable();
            $table->text('description');
            $table->decimal('amount', 12, 2);
            $table->date('expense_date');
            $table->timestamp('paid_at')->nullable();
            $table->string('reference')->nullable();
            $table->string('voucher_number')->nullable();
            $table->string('status')->default('pending')->index();
            $table->foreignId('registered_by_user_id')->nullable()->constrained('users', indexName: 'expenses_user_fk')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('expense_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users', indexName: 'expense_attach_user_fk')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_attachments');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }
};
