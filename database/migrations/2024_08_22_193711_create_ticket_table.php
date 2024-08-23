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
        Schema::create('ticket', function (Blueprint $table) {
            $table->uuid('ticket_id')->primary();
            $table->uuid('purchase_id');
            $table->uuid('event_id');
            $table->string('seat_number');
            $table->integer('price');
            $table->string('ticket_type');
            $table->timestamps();

            // Foreign keys
            $table->foreign('purchase_id')->references('purchase_id')->on('purchase')->onDelete('cascade');
            $table->foreign('event_id')->references('event_id')->on('event')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket');
    }
};
