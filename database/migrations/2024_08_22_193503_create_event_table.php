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
        Schema::create('event', function (Blueprint $table) {
            $table->uuid('event_id')->primary(); // Primary Key
            $table->string('event_name');
            $table->string('organizer_name')->nullable();
            $table->text('description')->nullable();
            $table->text('description_details')->nullable();
            $table->dateTime('event_date');
            $table->string('location');
            $table->integer('ticket_price');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event');
    }
};
