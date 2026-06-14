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
        Schema::create('email_routing_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('resend_email_id')->nullable();
            $table->string('recipient');
            $table->integer('site_id');
            $table->integer('user_id');
            $table->string('resolved_email')->nullable();
            $table->enum('status', ['received', 'resolving', 'forwarded', 'failed'])->default('received');
            $table->string('failure_reason')->nullable();
            $table->timestamp('received_at');
            $table->timestamp('forwarded_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_routing_logs');
    }
};
