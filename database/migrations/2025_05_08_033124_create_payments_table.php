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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resident_id')->references('id')->on('residents')->onDelete('cascade');
            $table->date('payment_date');
            $table->string('payment_month',18);
            $table->enum('payment_type',['thr','iuran']);
            $table->enum('payment_method',['cash','transfer','e-wallet']);
            $table->integer('contribution');
            $table->foreignId('recorded_by')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
