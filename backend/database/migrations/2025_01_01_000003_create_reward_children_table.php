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
        Schema::create('reward_children', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reward_id')->constrained()->onDelete('cascade');
            $table->foreignId('child_id')->constrained()->onDelete('cascade');
            $table->integer('deduction_amount')->nullable(); // Actual stars deducted when redeemed (null before redemption)
            $table->timestamps();

            // Unique constraint: each child can only be linked once to a reward
            $table->unique(['reward_id', 'child_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reward_children');
    }
};
