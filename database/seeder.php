<?php
/**
 * Database Seeder for Ticketing System
 * Run this file to populate the database with sample data
 */

require_once '../config/database.php';

class DatabaseSeeder {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function run() {
        echo "🌱 Starting database seeding...\n\n";
        
        try {
            $this->conn->beginTransaction();
            
            $this->seedUsers();
            $this->seedCategories();
            $this->seedTickets();
            $this->seedComments();
            $this->seedAuditLogs();
            
            $this->conn->commit();
            echo "✅ Database seeding completed successfully!\n";
            
        } catch (Exception $e) {
            $this->conn->rollback();
            echo "❌ Error seeding database: " . $e->getMessage() . "\n";
        }
    }
    
    private function seedUsers() {
        echo "👥 Seeding users...\n";
        
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@ticket.com',
                'password' => password_hash('password', PASSWORD_DEFAULT),
                'role' => 'admin'
            ],
            [
                'name' => 'John Agent',
                'email' => 'agent@ticket.com',
                'password' => password_hash('password', PASSWORD_DEFAULT),
                'role' => 'agent'
            ],
            [
                'name' => 'Jane Customer',
                'email' => 'customer@ticket.com',
                'password' => password_hash('password', PASSWORD_DEFAULT),
                'role' => 'user'
            ],
            [
                'name' => 'Mike Wilson',
                'email' => 'mike@example.com',
                'password' => password_hash('password', PASSWORD_DEFAULT),
                'role' => 'user'
            ],
            [
                'name' => 'Sarah Davis',
                'email' => 'sarah@example.com',
                'password' => password_hash('password', PASSWORD_DEFAULT),
                'role' => 'agent'
            ]
        ];
        
        $query = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        
        foreach ($users as $user) {
            $stmt->execute([$user['name'], $user['email'], $user['password'], $user['role']]);
        }
        
        echo "   ✓ Created " . count($users) . " users\n";
    }
    
    private function seedCategories() {
        echo "📂 Seeding categories...\n";
        
        $categories = [
            [
                'name' => 'Technical Support',
                'description' => 'Technical issues, bugs, and system problems',
                'color' => '#EF4444'
            ],
            [
                'name' => 'Feature Request',
                'description' => 'New feature requests and enhancements',
                'color' => '#10B981'
            ],
            [
                'name' => 'General Inquiry',
                'description' => 'General questions and inquiries',
                'color' => '#3B82F6'
            ],
            [
                'name' => 'Billing',
                'description' => 'Billing, payment, and subscription issues',
                'color' => '#F59E0B'
            ],
            [
                'name' => 'Bug Report',
                'description' => 'Software bugs and unexpected behavior',
                'color' => '#EF4444'
            ],
            [
                'name' => 'Account',
                'description' => 'Account-related issues and requests',
                'color' => '#8B5CF6'
            ]
        ];
        
        $query = "INSERT INTO categories (name, description, color) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        
        foreach ($categories as $category) {
            $stmt->execute([$category['name'], $category['description'], $category['color']]);
        }
        
        echo "   ✓ Created " . count($categories) . " categories\n";
    }
    
    private function seedTickets() {
        echo "🎫 Seeding tickets...\n";
        
        $tickets = [
            [
                'title' => 'Login issue with mobile app',
                'description' => 'Cannot login to the mobile application. Getting "Invalid credentials" error even with correct password. This issue started after the recent app update.',
                'status' => 'open',
                'priority' => 'high',
                'category_id' => 1,
                'created_by' => 3,
                'assigned_to' => 2
            ],
            [
                'title' => 'Add dark mode feature',
                'description' => 'Would like to have a dark mode option in the application for better user experience during night time usage. Many users have requested this feature.',
                'status' => 'in-progress',
                'priority' => 'medium',
                'category_id' => 2,
                'created_by' => 3,
                'assigned_to' => 2
            ],
            [
                'title' => 'How to reset password?',
                'description' => 'I forgot my password and cannot find the reset option. Can you guide me through the process? The forgot password link seems to be missing.',
                'status' => 'open',
                'priority' => 'low',
                'category_id' => 3,
                'created_by' => 3,
                'assigned_to' => null
            ],
            [
                'title' => 'Payment not processed',
                'description' => 'My payment was deducted from bank but subscription is not activated. Transaction ID: TXN123456789. This happened yesterday at 2 PM.',
                'status' => 'open',
                'priority' => 'urgent',
                'category_id' => 4,
                'created_by' => 3,
                'assigned_to' => 1
            ],
            [
                'title' => 'App crashes on startup',
                'description' => 'The application crashes immediately after opening. This started happening after the latest update. Error message: "Unexpected error occurred".',
                'status' => 'closed',
                'priority' => 'high',
                'category_id' => 1,
                'created_by' => 3,
                'assigned_to' => 2
            ],
            [
                'title' => 'Email notifications not working',
                'description' => 'I am not receiving email notifications for ticket updates. My email preferences are set correctly but no emails are coming through.',
                'status' => 'open',
                'priority' => 'medium',
                'category_id' => 1,
                'created_by' => 4,
                'assigned_to' => 5
            ],
            [
                'title' => 'Request for API documentation',
                'description' => 'Could you provide comprehensive API documentation? We need to integrate with our existing system and need detailed endpoint information.',
                'status' => 'open',
                'priority' => 'low',
                'category_id' => 3,
                'created_by' => 4,
                'assigned_to' => null
            ],
            [
                'title' => 'Slow page loading times',
                'description' => 'The dashboard takes too long to load (over 10 seconds). This is affecting our productivity. The issue seems to be getting worse over time.',
                'status' => 'in-progress',
                'priority' => 'high',
                'category_id' => 1,
                'created_by' => 4,
                'assigned_to' => 2
            ]
        ];
        
        $query = "INSERT INTO tickets (title, description, status, priority, category_id, created_by, assigned_to) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        
        foreach ($tickets as $ticket) {

            $stmt->execute([
                $ticket['title'],
                $ticket['description'],
                $ticket['status'],
                $ticket['priority'],
                $ticket['category_id'],
                $ticket['created_by'],
                $ticket['assigned_to']
            ]);
        }
        
        echo "   ✓ Created " . count($tickets) . " tickets\n";
    }
    
    private function seedComments() {
        echo "💬 Seeding comments...\n";
        
        $comments = [
            [
                'ticket_id' => 1,
                'user_id' => 2,
                'comment' => 'I have investigated this issue. It seems to be related to the recent authentication system update. Working on a fix now.',
                'is_internal' => false
            ],
            [
                'ticket_id' => 1,
                'user_id' => 3,
                'comment' => 'Thank you for looking into this. When can I expect a fix? This is blocking my daily work.',
                'is_internal' => false
            ],
            [
                'ticket_id' => 1,
                'user_id' => 2,
                'comment' => 'Internal note: Need to rollback auth changes and test thoroughly before next deployment.',
                'is_internal' => true
            ],
            [
                'ticket_id' => 2,
                'user_id' => 2,
                'comment' => 'Dark mode feature is currently in development. Expected completion by next week. Will include system theme detection.',
                'is_internal' => false
            ],
            [
                'ticket_id' => 4,
                'user_id' => 1,
                'comment' => 'Payment issue resolved. Subscription has been activated. Refund processed for the duplicate charge.',
                'is_internal' => false
            ],
            [
                'ticket_id' => 5,
                'user_id' => 2,
                'comment' => 'Issue was caused by a memory leak in the previous version. Fixed in v2.1.3. Please update your app.',
                'is_internal' => false
            ],
            [
                'ticket_id' => 6,
                'user_id' => 5,
                'comment' => 'Checking email service configuration. Will update you within 24 hours.',
                'is_internal' => false
            ],
            [
                'ticket_id' => 8,
                'user_id' => 2,
                'comment' => 'Performance analysis completed. Identified database query optimization opportunities. Implementing fixes.',
                'is_internal' => false
            ]
        ];
        
        $query = "INSERT INTO comments (ticket_id, user_id, comment, is_internal) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        
        foreach ($comments as $comment) {
            $stmt->execute([
                $comment['ticket_id'],
                $comment['user_id'],
                $comment['comment'],
                $comment['is_internal']
            ]);
        }
        
        echo "   ✓ Created " . count($comments) . " comments\n";
    }
    
    private function seedAuditLogs() {
        echo "📋 Seeding audit logs...\n";
        
        $logs = [
            [
                'ticket_id' => 1,
                'user_id' => 2,
                'action' => 'assigned',
                'old_values' => json_encode(['assigned_to' => null]),
                'new_values' => json_encode(['assigned_to' => 2])
            ],
            [
                'ticket_id' => 1,
                'user_id' => 2,
                'action' => 'status_changed',
                'old_values' => json_encode(['status' => 'open']),
                'new_values' => json_encode(['status' => 'in-progress'])
            ],
            [
                'ticket_id' => 4,
                'user_id' => 1,
                'action' => 'assigned',
                'old_values' => json_encode(['assigned_to' => null]),
                'new_values' => json_encode(['assigned_to' => 1])
            ],
            [
                'ticket_id' => 4,
                'user_id' => 1,
                'action' => 'priority_changed',
                'old_values' => json_encode(['priority' => 'high']),
                'new_values' => json_encode(['priority' => 'urgent'])
            ],
            [
                'ticket_id' => 5,
                'user_id' => 2,
                'action' => 'status_changed',
                'old_values' => json_encode(['status' => 'in-progress']),
                'new_values' => json_encode(['status' => 'closed'])
            ],
            [
                'ticket_id' => 6,
                'user_id' => 5,
                'action' => 'assigned',
                'old_values' => json_encode(['assigned_to' => null]),
                'new_values' => json_encode(['assigned_to' => 5])
            ],
            [
                'ticket_id' => 8,
                'user_id' => 2,
                'action' => 'status_changed',
                'old_values' => json_encode(['status' => 'open']),
                'new_values' => json_encode(['status' => 'in-progress'])
            ]
        ];
        
        $query = "INSERT INTO audit_logs (ticket_id, user_id, action, old_values, new_values) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        
        foreach ($logs as $log) {
            $stmt->execute([
                $log['ticket_id'],
                $log['user_id'],
                $log['action'],
                $log['old_values'],
                $log['new_values']
            ]);
        }
        
        echo "   ✓ Created " . count($logs) . " audit log entries\n";
    }
    
    public function clearData() {
        echo "🗑️  Clearing existing data...\n";
        
        // Disable foreign key checks temporarily
        $this->conn->exec('SET FOREIGN_KEY_CHECKS = 0');
        
        $tables = ['audit_logs', 'comments', 'attachments', 'tickets', 'categories', 'users'];
        
        foreach ($tables as $table) {
            $this->conn->exec("DELETE FROM $table");
            $this->conn->exec("ALTER TABLE $table AUTO_INCREMENT = 1");
        }
        
        // Re-enable foreign key checks
        $this->conn->exec('SET FOREIGN_KEY_CHECKS = 1');
        
        echo "   ✓ All data cleared\n\n";
    }
}

// Run the seeder
if (php_sapi_name() === 'cli') {
    $seeder = new DatabaseSeeder();
    
    // Clear existing data first
    $seeder->clearData();
    
    // Seed new data
    $seeder->run();
    
    echo "\n🎉 Database seeding completed!\n";
    echo "📊 Summary:\n";
    echo "   • 5 Users (1 admin, 2 agents, 2 customers)\n";
    echo "   • 6 Categories\n";
    echo "   • 8 Sample tickets\n";
    echo "   • 8 Comments\n";
    echo "   • 7 Audit log entries\n\n";
    echo "🔑 Login credentials (all users):\n";
    echo "   Password: password\n";
    echo "   Emails: admin@ticket.com, agent@ticket.com, customer@ticket.com\n\n";
} else {
    echo "This script must be run from the command line.";
}
?>