<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('google_id')->nullable()->unique();
                $table->string('avatar_url')->nullable();
                $table->timestamp('last_login_at')->nullable();
                $table->char('last_login_ip_hash', 64)->nullable();
                $table->boolean('is_active')->default(true);
                $table->rememberToken();
                $table->timestamps();
            });
        }

        Schema::create('payouts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->uuid('request_id')->unique();
            $table->string('reference', 32)->unique();
            $table->text('phone_encrypted');
            $table->char('phone_hash', 64)->index();
            $table->string('phone_masked', 20);
            $table->unsignedInteger('amount');
            $table->string('command_id', 40);
            $table->string('remarks', 100);
            $table->string('occasion', 100)->nullable();
            $table->string('status', 20)->index();
            $table->string('conversation_id')->nullable()->unique();
            $table->string('originator_conversation_id')->nullable()->index();
            $table->string('transaction_id', 64)->nullable();
            $table->string('result_code', 20)->nullable();
            $table->string('result_description')->nullable();
            $table->foreignId('initiated_by')->constrained('users');
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('callback_received_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('timeout_at')->nullable();
            $table->timestamps();
        });

        Schema::create('mpesa_balance_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('conversation_id')->nullable()->index();
            $table->string('originator_conversation_id')->nullable()->index();
            $table->string('request_status', 20)->index();
            $table->string('result_code', 20)->nullable();
            $table->string('result_description')->nullable();
            $table->bigInteger('working_account_balance')->nullable();
            $table->bigInteger('utility_account_balance')->nullable();
            $table->bigInteger('charges_paid_account_balance')->nullable();
            $table->text('other_balances')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('received_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 80)->index();
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->text('metadata')->nullable();
            $table->char('ip_hash', 64)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('mpesa_balance_snapshots');
        Schema::dropIfExists('payouts');
        Schema::dropIfExists('users');
    }
};
