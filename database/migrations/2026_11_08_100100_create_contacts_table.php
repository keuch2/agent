<?php

use App\Support\Database\RowLevelSecurity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('wa_id', 40)->nullable();
            $table->jsonb('tags')->default('[]');
            $table->text('notes')->nullable();
            $table->timestamp('first_contact_at')->nullable();
            $table->timestamp('last_contact_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'wa_id']);
        });

        RowLevelSecurity::enableForTable('contacts');
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
