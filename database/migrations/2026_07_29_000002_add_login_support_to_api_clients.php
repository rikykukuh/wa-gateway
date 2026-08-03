<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_clients', function (Blueprint $table) {
            $table->text('encrypted_api_key')->nullable()->after('api_key_hash');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignUuid('api_client_id')->nullable()->after('id')->unique()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('api_client_id');
        });
        Schema::table('api_clients', function (Blueprint $table) {
            $table->dropColumn('encrypted_api_key');
        });
    }
};
