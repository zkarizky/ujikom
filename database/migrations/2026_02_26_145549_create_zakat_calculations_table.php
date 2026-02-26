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
        Schema::create('zakat_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->enum('type', ['mal', 'profesi']);
            $table->enum('salary_type', ['kotor', 'bersih'])->nullable();
            $table->enum('period', ['bulanan', 'tahunan'])->nullable();

            $table->decimal('income', 15, 2);
            $table->decimal('nisab', 15, 2);
            $table->decimal('zakat_amount', 15, 2);
            $table->boolean('is_eligible');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zakat_calculations');
    }
};
