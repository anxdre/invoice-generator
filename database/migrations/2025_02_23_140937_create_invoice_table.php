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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->string('payment_number')->nullable();
            $table->string('payment_type')->nullable();
            $table->time('payment_date')->nullable();
            $table->string('to');
            $table->string('recipient_address');
            $table->decimal('total');
            $table->decimal('total_payment');
            $table->integer('tax')->default(0);
            $table->boolean('paid')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice');
    }
};
