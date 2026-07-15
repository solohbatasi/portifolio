<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coffee_payments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->uuid('request_id')->unique();
            $table->string('reference', 32)->unique();
            $table->unsignedInteger('amount');
            $table->unsignedInteger('callback_amount')->nullable();
            $table->text('phone_encrypted');
            $table->char('phone_hash', 64)->index();
            $table->string('phone_masked', 20);
            $table->string('status', 20)->index();
            $table->string('merchant_request_id')->nullable();
            $table->string('checkout_request_id')->nullable()->unique();
            $table->string('response_code', 20)->nullable();
            $table->string('response_description', 255)->nullable();
            $table->string('customer_message', 255)->nullable();
            $table->string('result_code', 20)->nullable();
            $table->string('result_description', 255)->nullable();
            $table->string('mpesa_receipt', 64)->nullable();
            $table->timestamp('transaction_date')->nullable();
            $table->timestamp('callback_received_at')->nullable();
            $table->timestamp('last_status_query_at')->nullable();
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('reconciliation_warning', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coffee_payments');
    }
};
