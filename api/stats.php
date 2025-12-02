<?php
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/database.php';

class Stats {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getTicketStats() {
        try {
            // Get total tickets (excluding archived)
            $query = "SELECT COUNT(*) as total FROM tickets WHERE archived = 0";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $total = $stmt->fetch()['total'];

            // Get resolved tickets (excluding archived)
            $query = "SELECT COUNT(*) as resolved FROM tickets WHERE status = 'resolved' AND archived = 0";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $resolved = $stmt->fetch()['resolved'];

            // Get in progress tickets (excluding archived)
            $query = "SELECT COUNT(*) as in_progress FROM tickets WHERE status = 'in-progress' AND archived = 0";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $in_progress = $stmt->fetch()['in_progress'];

            // Get high priority tickets (excluding archived)
            $query = "SELECT COUNT(*) as `high_priority` FROM tickets WHERE (priority = 'high' OR priority = 'urgent') AND archived = 0";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $high_priority = $stmt->fetch()['high_priority'];

            // Get open tickets (excluding archived)
            $query = "SELECT COUNT(*) as open FROM tickets WHERE status = 'open' AND archived = 0";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $open = $stmt->fetch()['open'];

            // Get closed tickets (excluding archived)
            $query = "SELECT COUNT(*) as closed FROM tickets WHERE status = 'closed' AND archived = 0";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $closed = $stmt->fetch()['closed'];

            // Get tickets from this week
            $query = "SELECT COUNT(*) as this_week FROM tickets WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $this_week = $stmt->fetch()['this_week'];

            // Get tickets from last week
            $query = "SELECT COUNT(*) as last_week FROM tickets 
                      WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) 
                      AND created_at < DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $last_week = $stmt->fetch()['last_week'];

            return [
                'success' => true,
                'data' => [
                    'total' => (int)$total,
                    'resolved' => (int)$resolved,
                    'in_progress' => (int)$in_progress,
                    'high_priority' => (int)$high_priority,
                    'open' => (int)$open,
                    'closed' => (int)$closed,
                    'this_week' => (int)$this_week,
                    'last_week' => (int)$last_week
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to fetch stats: ' . $e->getMessage()
            ];
        }
    }
}

// Handle request
try {
    $database = new Database();
    $db = $database->getConnection();
    
    $stats = new Stats($db);
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $result = $stats->getTicketStats();
        echo json_encode($result);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
