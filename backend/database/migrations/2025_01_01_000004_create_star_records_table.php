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
        Schema::create('star_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained()->onDelete('cascade');
            $table->integer('amount'); // Positive for add, negative for subtract/redeem
            $table->enum('type', ['add', 'subtract', 'redeem']);
            $table->foreignId('reward_id')->nullable()->constrained()->onDelete('set null'); // Only set when type is 'redeem'
            $table->string('reason')->nullable(); // Reason for this star change
            $table->timestamps();

            // Index for fast query by child and date
            $table->index(['child_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('star_records');
    }
};
