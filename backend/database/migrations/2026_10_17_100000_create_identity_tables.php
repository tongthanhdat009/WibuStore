<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('public_id')->unique()->default(DB::raw('gen_random_uuid()'));
            $table->string('phone', 32)->nullable();
            $table->string('avatar', 512)->nullable();
            $table->string('status', 32)->default('active');
            $table->timestamptz('last_login_at')->nullable();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestampsTz();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestampsTz();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unique(['role_id', 'user_id']);
            $table->timestampsTz();
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->unique(['permission_id', 'role_id']);
            $table->timestampsTz();
        });

        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('recipient_name');
            $table->string('phone', 32);
            $table->string('line1');
            $table->string('line2')->nullable();
            $table->string('ward')->nullable();
            $table->string('district')->nullable();
            $table->string('province')->nullable();
            $table->string('country', 8)->default('VN');
            $table->string('postal_code', 16)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestampsTz();
            $table->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['public_id', 'phone', 'avatar', 'status', 'last_login_at']);
        });
    }
};
