<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->date('date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->boolean('late')->nullable();
            $table->enum('source', ['fingerprint', 'manual'])->default('fingerprint');
            $table->string('reason')->nullable();
            $table->foreignId('corrected_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('import_id')->nullable()->constrained('attendance_imports')->nullOnDelete();
            $table->timestamps();

            $table->unique(['staff_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance');
    }
};
