<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\LeavePolicy;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with the default leave policy and
     * settings for each staff category, matching the school's current rules.
     */
    public function run(): void
    {
        $defaults = [
            'Tutorial' => ['casual_days' => 21, 'medical_days' => 21],
            'Support' => ['casual_days' => 14, 'medical_days' => 7],
            'Admin' => ['casual_days' => 14, 'medical_days' => 7],
        ];

        foreach ($defaults as $category => $days) {
            LeavePolicy::updateOrCreate(
                ['category' => $category],
                [
                    'casual_days' => $days['casual_days'],
                    'medical_days' => $days['medical_days'],
                    'short_leave_free' => 2,
                    'short_leave_deduct' => 0.5,
                    'requires_first_approver' => $category !== 'Admin',
                    'requires_headmaster' => true,
                ]
            );
        }

        AppSetting::query()->exists() || AppSetting::create([]);
    }
}
