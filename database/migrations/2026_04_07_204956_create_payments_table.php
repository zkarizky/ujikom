<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('payments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->foreignId('zakat_id')->constrained('zakat_calculations')->cascadeOnDelete();

        $table->string('order_id')->unique();
        $table->integer('amount');

        $table->string('payment_type')->nullable(); // gopay, bank_transfer dll
        $table->string('transaction_status')->default('pending'); // pending, settlement, expire
        $table->string('snap_token')->nullable();

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
