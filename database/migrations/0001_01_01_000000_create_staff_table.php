<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->string('emp_id')->unique();
            $table->string('bio_id')->nullable()->unique();
            $table->string('name');
            $table->string('designation')->nullable();
            $table->enum('category', ['Tutorial', 'Support', 'Admin'])->default('Tutorial');
            $table->string('department')->nullable();
            $table->enum('role', ['staff', 'sectional_head', 'supervisor', 'headmaster', 'hr'])->default('staff');
            $table->foreignId('first_approver_id')->nullable();
            $table->date('appointment_date')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile')->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->string('password');
            $table->boolean('must_change_password')->default(false);
            $table->foreignId('delegate_id')->nullable();
            $table->date('delegate_until')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::table('staff', function (Blueprint $table) {
            $table->foreign('first_approver_id')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('delegate_id')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('staff');
    }
};
