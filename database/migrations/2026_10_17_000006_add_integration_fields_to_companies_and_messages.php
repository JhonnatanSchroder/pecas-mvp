<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->string('whatsapp_instance_id')->nullable()->unique()->after('name');
            $table->char('integration_token_hash', 64)->nullable()->unique()->after('whatsapp_instance_id');
        });

        Schema::table('messages', function (Blueprint $table): void {
            $table->unique(['company_id', 'provider_message_id']);
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table): void {
            $table->dropUnique(['company_id', 'provider_message_id']);
        });

        Schema::table('companies', function (Blueprint $table): void {
            $table->dropUnique(['whatsapp_instance_id']);
            $table->dropUnique(['integration_token_hash']);
            $table->dropColumn(['whatsapp_instance_id', 'integration_token_hash']);
        });
    }
};
