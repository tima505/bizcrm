<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_integrations', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 32)->unique();
            $table->boolean('enabled')->default(false);
            $table->text('settings')->nullable();
            $table->string('display_name', 191)->nullable();
            $table->string('username', 191)->nullable();
            $table->string('external_id', 191)->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_integrations');
    }
};
