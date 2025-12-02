<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Bug Report',
                'slug' => 'bug-report',
                'description' => 'Issues related to software bugs or errors',
                'color' => '#EF4444',
            ],
            [
                'name' => 'Feature Request',
                'slug' => 'feature-request',
                'description' => 'Requests for new features or enhancements',
                'color' => '#3B82F6',
            ],
            [
                'name' => 'Technical Support',
                'slug' => 'technical-support',
                'description' => 'General technical support and help',
                'color' => '#10B981',
            ],
            [
                'name' => 'Account Issues',
                'slug' => 'account-issues',
                'description' => 'Issues related to user accounts and authentication',
                'color' => '#F59E0B',
            ],
            [
                'name' => 'Performance',
                'slug' => 'performance',
                'description' => 'Performance related issues and optimizations',
                'color' => '#8B5CF6',
            ],
            [
                'name' => 'Documentation',
                'slug' => 'documentation',
                'description' => 'Issues with documentation or requests for documentation improvements',
                'color' => '#06B6D4',
            ],
            [
                'name' => 'Security',
                'slug' => 'security',
                'description' => 'Security related concerns and vulnerabilities',
                'color' => '#DC2626',
            ],
            [
                'name' => 'Other',
                'slug' => 'other',
                'description' => 'General inquiries and other issues',
                'color' => '#6B7280',
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}