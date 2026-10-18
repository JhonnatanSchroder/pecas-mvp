<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'cancelled']);
            $table->timestamps();
            $table->index(['company_id', 'status']);
        });

        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('part_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('unit_price_cents');
            $table->timestamps();
            $table->unique(['order_id', 'part_id']);
            $table->index(['company_id', 'order_id']);
        });

        Schema::table('inventory_movements', function (Blueprint $table): void {
            $table->foreignId('order_id')->nullable()->after('part_id')->constrained()->restrictOnDelete();
            $table->string('type')->change();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table): void {
            $table->dropForeign(['order_id']);
            $table->dropColumn('order_id');
            $table->enum('type', ['initial', 'adjustment'])->change();
        });
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
