<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odoo_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('direction', 16)->default('push');
            $table->string('event_id', 128)->nullable();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('endpoint', 256);
            $table->string('status', 16)->default('pending');
            $table->integer('http_status')->nullable();
            $table->jsonb('payload')->default('{}');
            $table->text('response_body')->nullable();
            $table->text('error')->nullable();
            $table->integer('attempts')->default(1);
            $table->integer('duration_ms')->nullable();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('request_id', 64)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestampsTz();
            $table->index(['status', 'created_at']);
            $table->index(['event_id']);
            $table->index(['order_id', 'created_at']);
        });
        DB::statement("ALTER TABLE odoo_sync_logs ADD CONSTRAINT odoo_sync_logs_direction CHECK (direction IN ('push','pull','export','test','retry'))");
        DB::statement("ALTER TABLE odoo_sync_logs ADD CONSTRAINT odoo_sync_logs_status CHECK (status IN ('pending','success','rejected','failed'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('odoo_sync_logs');
    }
};
