<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `users` is the identity table read before any tenant is known (login,
 * password reset, 2FA challenge), so it is isolated by the Eloquent scope and
 * policies only, not by Row Level Security.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->boolean('is_superadmin')->default(false)->after('tenant_id');
            $table->softDeletes();
            $table->dropUnique(['email']);
        });

        DB::statement('CREATE UNIQUE INDEX users_email_active_unique ON users (lower(email)) WHERE deleted_at IS NULL');
        DB::statement('ALTER TABLE users ADD CONSTRAINT users_tenant_or_superadmin CHECK ((tenant_id IS NULL) = is_superadmin)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_tenant_or_superadmin');
        DB::statement('DROP INDEX IF EXISTS users_email_active_unique');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropColumn(['is_superadmin', 'deleted_at']);
            $table->unique('email');
        });
    }
};
