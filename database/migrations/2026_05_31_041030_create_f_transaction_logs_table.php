<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transaction_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('simulation_id')->constrained('simulations')->onDelete('cascade');
            $table->foreignId('trigger_creator_id')->constrained('flow_objects')->onDelete('cascade');
            $table->foreignId('source_rule_id')->nullable()->constrained('flow_objects')->onDelete('set null');
            $table->foreignId('target_outcome_id')->constrained('flow_objects')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->decimal('running_balance', 15, 2);
            $table->json('meta_json')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_logs');
    }
};
