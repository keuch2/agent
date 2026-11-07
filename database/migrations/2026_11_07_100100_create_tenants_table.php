<?php

use App\Support\Database\RowLevelSecurity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        RowLevelSecurity::createCurrentTenantFunction();

        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('ruc', 20)->nullable()->unique();
            $table->string('timezone')->default('America/Asuncion');
            $table->string('status')->default('onboarding')->index();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->jsonb('settings')->default('{}');
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        RowLevelSecurity::enableForTable('tenants', column: 'id');
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
        RowLevelSecurity::dropCurrentTenantFunction();
    }
};
