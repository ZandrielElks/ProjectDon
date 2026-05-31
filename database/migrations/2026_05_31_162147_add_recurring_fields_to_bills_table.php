<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->boolean('is_recurring')->default(false)->after('status');
            $table->enum('frequency', ['monthly', 'weekly', 'yearly'])->nullable()->after('is_recurring');
            $table->foreignId('category_id')->nullable()->after('frequency')->constrained()->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn(['is_recurring', 'frequency', 'category_id']);
        });
    }
};
