<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->enum('role', ['admin', 'attendant'])->default('attendant');
        });

        if (DB::table('users')->whereNull('company_id')->exists()) {
            $companyId = DB::table('companies')->insertGetId([
                'name' => 'Existing Local Company',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('users')->whereNull('company_id')->update(['company_id' => $companyId]);
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('company_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['company_id']);
            $table->dropColumn(['company_id', 'role']);
        });
    }
};
