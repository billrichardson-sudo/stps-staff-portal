<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_approval_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_application_id')->constrained('leave_applications')->cascadeOnDelete();
            $table->unsignedTinyInteger('step_order');
            $table->enum('kind', ['first', 'headmaster', 'person', 'hr']);
            $table->string('label');
            $table->foreignId('approver_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->enum('status', ['waiting', 'approved', 'rejected'])->default('waiting');
            $table->foreignId('acted_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamp('acted_at')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['leave_application_id', 'step_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_approval_steps');
    }
};
