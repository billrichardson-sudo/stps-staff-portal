<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_policies', function (Blueprint $table) {
            $table->id();
            $table->enum('category', ['Tutorial', 'Support', 'Admin'])->unique();
            $table->decimal('casual_days', 4, 1);
            $table->decimal('medical_days', 4, 1);
            $table->unsignedTinyInteger('short_leave_free')->default(2);
            $table->decimal('short_leave_deduct', 4, 2)->default(0.5);
            $table->boolean('requires_first_approver')->default(true);
            $table->boolean('requires_headmaster')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_policies');
    }
};
