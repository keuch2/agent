<?php

use App\Support\Database\RowLevelSecurity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->string('direction', 10);
            $table->string('author', 10);
            $table->string('type', 20);
            $table->text('content')->nullable();
            $table->string('external_id')->nullable();
            $table->string('status', 20);
            $table->text('error')->nullable();
            $table->jsonb('payload')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->unique(['tenant_id', 'external_id']);
            $table->index(['conversation_id', 'occurred_at']);
        });

        RowLevelSecurity::enableForTable('messages');
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
