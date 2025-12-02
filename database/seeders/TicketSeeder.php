<?php

namespace Database\Seeders;

use App\Models\Ticket;
use App\Models\User;
use App\Models\Category;
use App\Models\ActivityLog;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::where('role', 'user')->get();
        $admins = User::where('role', 'admin')->get();
        $categories = Category::all();

        $tickets = [
            [
                'title' => 'Login page not working properly',
                'description' => 'I am unable to log in to my account. The page keeps showing an error message even with correct credentials.',
                'priority' => 'high',
                'status' => 'open',
            ],
            [
                'title' => 'Add dark mode feature',
                'description' => 'It would be great to have a dark mode option for better user experience during night time usage.',
                'priority' => 'medium',
                'status' => 'in_progress',
            ],
            [
                'title' => 'Dashboard loading very slowly',
                'description' => 'The dashboard takes more than 10 seconds to load. This is affecting productivity.',
                'priority' => 'high',
                'status' => 'resolved',
            ],
            [
                'title' => 'Cannot upload files larger than 5MB',
                'description' => 'When trying to upload files larger than 5MB, I get an error. Please increase the upload limit.',
                'priority' => 'medium',
                'status' => 'open',
            ],
            [
                'title' => 'Password reset email not received',
                'description' => 'I requested a password reset but did not receive any email. Please check the email system.',
                'priority' => 'high',
                'status' => 'in_progress',
            ],
            [
                'title' => 'Add export functionality for reports',
                'description' => 'We need the ability to export reports in PDF and Excel formats for better data sharing.',
                'priority' => 'low',
                'status' => 'open',
            ],
            [
                'title' => 'Mobile app crashes on startup',
                'description' => 'The mobile application crashes immediately after opening. This happens on both iOS and Android.',
                'priority' => 'high',
                'status' => 'resolved',
            ],
            [
                'title' => 'Improve search functionality',
                'description' => 'The current search is not very accurate. Please improve the search algorithm to return more relevant results.',
                'priority' => 'medium',
                'status' => 'closed',
            ],
            [
                'title' => 'Add two-factor authentication',
                'description' => 'For better security, please implement two-factor authentication for user accounts.',
                'priority' => 'medium',
                'status' => 'open',
            ],
            [
                'title' => 'Update user manual',
                'description' => 'The current user manual is outdated and needs to be updated with the latest features.',
                'priority' => 'low',
                'status' => 'in_progress',
            ],
        ];

        foreach ($tickets as $ticketData) {
            $user = $users->random();
            $category = $categories->random();
            $assignedTo = rand(0, 1) ? $admins->random()->id : null;

            $ticket = Ticket::create([
                'title' => $ticketData['title'],
                'description' => $ticketData['description'],
                'priority' => $ticketData['priority'],
                'status' => $ticketData['status'],
                'category_id' => $category->id,
                'user_id' => $user->id,
                'assigned_to' => $assignedTo,
                'created_at' => now()->subDays(rand(1, 30)),
                'updated_at' => now()->subDays(rand(0, 10)),
            ]);

            // Create activity log for ticket creation
            ActivityLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'action' => 'created',
                'description' => 'Ticket created',
                'created_at' => $ticket->created_at,
            ]);

            // Add some random status changes for variety
            if (rand(0, 1)) {
                ActivityLog::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $assignedTo ?? $admins->random()->id,
                    'action' => 'status_changed',
                    'old_values' => ['status' => 'open'],
                    'new_values' => ['status' => $ticket->status],
                    'description' => "Status changed to {$ticket->status}",
                    'created_at' => $ticket->created_at->addHours(rand(1, 24)),
                ]);
            }
        }
    }
}