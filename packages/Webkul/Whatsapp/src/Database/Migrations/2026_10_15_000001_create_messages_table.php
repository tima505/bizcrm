<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messaging_messages', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 32)->index();
            $table->string('external_id', 191)->nullable()->index();
            $table->string('chat_id', 191)->index();
            $table->string('from_number', 64)->nullable();
            $table->string('contact_name', 191)->nullable();
            $table->text('content');
            $table->enum('direction', ['incoming', 'outgoing']);
            $table->string('status', 32)->default('pending');
            $table->timestamp('sent_at')->nullable();

            $table->unsignedInteger('person_id')->nullable();
            $table->unsignedInteger('lead_id')->nullable();

            $table->timestamps();

            $table->foreign('person_id')->references('id')->on('persons')->nullOnDelete();
            $table->foreign('lead_id')->references('id')->on('leads')->nullOnDelete();

            $table->index(['channel', 'chat_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messaging_messages');
    }
};
