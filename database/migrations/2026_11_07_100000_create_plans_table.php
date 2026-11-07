<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->decimal('monthly_price_usd', 10, 2);
            $table->unsignedInteger('conversation_quota');
            $table->unsignedInteger('advisor_limit')->nullable()->comment('null = unlimited');
            $table->decimal('overage_price_usd', 10, 4);
            $table->jsonb('features')->default('[]');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
