<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('whatsapp_number');
            $table->timestamps();
            $table->unique(['company_id', 'whatsapp_number']);
        });
        Schema::create('conversations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->restrictOnDelete();
            $table->enum('status', ['open', 'closed']);
            $table->enum('automation_state', ['automated', 'human_paused']);
            $table->timestamp('human_paused_until')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('ai_summary')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'status', 'automation_state']);
        });
        Schema::create('messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->enum('direction', ['inbound', 'outbound']);
            $table->enum('sender', ['customer', 'bot', 'human']);
            $table->string('provider_message_id')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('contacts');
    }
};
