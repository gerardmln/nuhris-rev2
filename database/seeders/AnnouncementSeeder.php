<?php

namespace Database\Seeders;

use App\Models\Announcement;
use Illuminate\Database\Seeder;

class AnnouncementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $announcements = [
            [
                'title' => 'System Maintenance Scheduled',
                'content' => 'The HR system will be under maintenance on March 31st from 2 PM to 4 PM. Please plan accordingly.',
                'priority' => 'high',
                'target_user_type' => null,
                'is_published' => true,
                'published_at' => now(),
                'expires_at' => now()->addDays(10),
                'created_by' => 1,
            ],
            [
                'title' => 'New Leave Policy Effective April 1',
                'content' => 'Please review the updated leave policy that goes into effect on April 1st. Key changes include extended vacation time.',
                'priority' => 'medium',
                'target_user_type' => null,
                'is_published' => true,
                'published_at' => now(),
                'expires_at' => now()->addDays(30),
                'created_by' => 1,
            ],
            [
                'title' => 'Employee Recognition Program',
                'content' => 'Nominations are now open for our quarterly employee recognition program. Recognize outstanding colleagues!',
                'priority' => 'low',
                'target_user_type' => null,
                'is_published' => true,
                'published_at' => now(),
                'expires_at' => now()->addDays(21),
                'created_by' => 1,
            ],
        ];

        foreach ($announcements as $announcement) {
            Announcement::create($announcement);
        }
    }
}
