<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('headmaster_approver_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->time('late_tutorial')->default('08:00:00');
            $table->time('late_support')->default('07:00:00');
            $table->time('late_admin')->default('08:00:00');
            $table->time('half_day_before')->default('11:30:00');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
