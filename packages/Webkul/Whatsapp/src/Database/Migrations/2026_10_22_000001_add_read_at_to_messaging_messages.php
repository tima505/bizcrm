<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messaging_messages', function (Blueprint $table) {
            $table->timestamp('read_at')->nullable()->after('status');
            $table->index(['chat_id', 'direction', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::table('messaging_messages', function (Blueprint $table) {
            $table->dropIndex(['chat_id', 'direction', 'read_at']);
            $table->dropColumn('read_at');
        });
    }
};
