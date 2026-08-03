<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_clients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('key_prefix', 24)->index();
            $table->char('api_key_hash', 64)->unique();
            $table->unsignedInteger('daily_message_limit')->default(60);
            $table->unsignedInteger('min_delay_seconds')->default(30);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->foreignUuid('api_client_id')
                ->nullable()
                ->after('id')
                ->constrained('api_clients')
                ->nullOnDelete();
            $table->index(['api_client_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropForeign(['api_client_id']);
            $table->dropIndex(['api_client_id', 'status']);
            $table->dropColumn('api_client_id');
        });

        Schema::dropIfExists('api_clients');
    }
};
