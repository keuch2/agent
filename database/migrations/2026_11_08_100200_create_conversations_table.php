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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('bot');
            $table->timestamp('started_at');
            $table->timestamp('closed_at')->nullable();
            $table->boolean('is_billable')->default(false);
            $table->timestamp('billable_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'started_at']);
        });

        DB::statement("CREATE UNIQUE INDEX conversations_one_open_per_contact ON conversations (contact_id, channel_id) WHERE status <> 'closed'");

        RowLevelSecurity::enableForTable('conversations');
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
