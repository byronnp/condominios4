<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condominium_id')->constrained('condominiums')->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('document_type_id')->nullable()->constrained('catalog_items')->nullOnDelete();
            $table->string('document_number', 30)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['condominium_id', 'document_number']);
        });

        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condominium_id')->constrained('condominiums')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('visitor_id')->constrained('visitors')->cascadeOnDelete();
            $table->foreignId('registered_by_user_id')->nullable()->constrained('users', indexName: 'visits_registered_user_fk')->nullOnDelete();
            $table->foreignId('authorized_by_user_id')->nullable()->constrained('users', indexName: 'visits_authorized_user_fk')->nullOnDelete();
            $table->string('purpose')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('valid_until')->nullable();
            $table->string('authorization_code', 32)->unique();
            $table->string('status')->default('pending')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['condominium_id', 'unit_id', 'status']);
        });

        Schema::create('visit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condominium_id')->constrained('condominiums')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('visit_id')->constrained('visits')->cascadeOnDelete();
            $table->foreignId('visitor_id')->constrained('visitors')->cascadeOnDelete();
            $table->foreignId('logged_by_user_id')->nullable()->constrained('users', indexName: 'visit_logs_logged_user_fk')->nullOnDelete();
            $table->string('type')->index();
            $table->dateTime('logged_at');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('common_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condominium_id')->constrained('condominiums')->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 100);
            $table->text('description')->nullable();
            $table->unsignedInteger('capacity')->default(1);
            $table->decimal('reservation_fee', 12, 2)->default(0);
            $table->boolean('requires_approval')->default(true);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['condominium_id', 'code']);
        });

        Schema::create('common_area_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condominium_id')->constrained('condominiums')->cascadeOnDelete();
            $table->foreignId('common_area_id')->constrained('common_areas')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->unsignedInteger('attendees_count')->default(1);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('status')->default('pending')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['common_area_id', 'starts_at', 'ends_at']);
        });

        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condominium_id')->constrained('condominiums')->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('reported_by_user_id')->nullable()->constrained('users', indexName: 'incidents_reported_user_fk')->nullOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users', indexName: 'incidents_assigned_user_fk')->nullOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('category')->default('general')->index();
            $table->string('priority')->default('medium')->index();
            $table->string('status')->default('open')->index();
            $table->dateTime('occurred_at')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condominium_id')->constrained('condominiums')->cascadeOnDelete();
            $table->foreignId('common_area_id')->nullable()->constrained('common_areas')->nullOnDelete();
            $table->foreignId('reported_by_user_id')->nullable()->constrained('users', indexName: 'maint_reported_user_fk')->nullOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users', indexName: 'maint_assigned_user_fk')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type')->default('corrective')->index();
            $table->string('priority')->default('medium')->index();
            $table->string('status')->default('scheduled')->index();
            $table->dateTime('scheduled_starts_at')->nullable();
            $table->dateTime('scheduled_ends_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->decimal('cost', 12, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('maintenance_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_id')->constrained('maintenances')->cascadeOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users', indexName: 'maint_tasks_assigned_user_fk')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('pending')->index();
            $table->dateTime('due_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_tasks');
        Schema::dropIfExists('maintenances');
        Schema::dropIfExists('incidents');
        Schema::dropIfExists('common_area_reservations');
        Schema::dropIfExists('common_areas');
        Schema::dropIfExists('visit_logs');
        Schema::dropIfExists('visits');
        Schema::dropIfExists('visitors');
    }
};
