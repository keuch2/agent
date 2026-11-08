<?php

use App\Support\Database\RowLevelSecurity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('phone_number_id', 64)->nullable();
            $table->string('waba_id', 64)->nullable();
            $table->string('display_phone_number', 40)->nullable();
            $table->string('verified_name')->nullable();
            $table->text('access_token')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('last_error')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'type']);
        });

        // A number routes to exactly one tenant, but can be re-linked once disconnected.
        DB::statement("CREATE UNIQUE INDEX channels_phone_number_id_unique ON channels (phone_number_id) WHERE phone_number_id IS NOT NULL AND status <> 'disconnected'");

        RowLevelSecurity::enableForTable('channels');
    }

    public function down(): void
    {
        Schema::dropIfExists('channels');
    }
};
