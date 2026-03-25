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
        Schema::create('qris_payments', function (Blueprint $table) {
            $table->id();
            $table->string('order_id', 120)->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->decimal('gross_amount', 14, 2);
            $table->string('transaction_status', 40)->default('pending');
            $table->string('fraud_status', 40)->nullable();
            $table->string('payment_type', 40)->nullable();
            $table->text('qr_url')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_paid')->default(false)->index();
            $table->timestamp('paid_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'order_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qris_payments');
    }
};
