<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->enum('type', ['Casual', 'Medical', 'Short', 'Other']);
            $table->date('from_date');
            $table->date('to_date');
            $table->decimal('days', 4, 1);
            $table->text('reason');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('certificate_path')->nullable();
            $table->enum('status', ['pending', 'clarify', 'granted', 'rejected', 'cancelled'])->default('pending');
            $table->unsignedTinyInteger('current_step')->default(0);
            $table->timestamps();

            $table->index(['staff_id', 'from_date', 'to_date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_applications');
    }
};
