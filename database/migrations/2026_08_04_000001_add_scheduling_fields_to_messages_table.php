<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->timestamp('scheduled_at')->nullable()->after('status')->index();
            $table->timestamp('last_attempt_at')->nullable()->after('scheduled_at');
            $table->unsignedTinyInteger('attempts')->default(0)->after('last_attempt_at');
            $table->index(['device_id', 'status', 'scheduled_at'], 'messages_dispatch_index');
        });

        DB::table('messages')
            ->where('status', 'pending')
            ->update(['status' => 'queued', 'scheduled_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('messages_dispatch_index');
            $table->dropColumn(['scheduled_at', 'last_attempt_at', 'attempts']);
        });
    }
};
