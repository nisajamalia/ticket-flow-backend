<?php
// api/tickets.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

class TicketAPI {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function getAllTickets() {
        // Get pagination parameters
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = ($page - 1) * $limit;
        
        // Get order by parameter
        $orderBy = isset($_GET['orderBy']) ? urldecode($_GET['orderBy']) : 't.created_at DESC';
        
        // Validate orderBy to prevent SQL injection
        $allowedOrders = [
            't.created_at DESC',
            't.created_at ASC',
            "FIELD(t.priority, 'urgent', 'high', 'medium', 'low'), t.created_at DESC",
            't.status ASC, t.created_at DESC'
        ];
        
        if (!in_array($orderBy, $allowedOrders)) {
            $orderBy = 't.created_at DESC'; // Default to newest first
        }
        
        // Build WHERE clause for filters
        $whereConditions = [];
        $params = [];
        
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $searchTerm = '%' . $_GET['search'] . '%';
            $whereConditions[] = "(t.title LIKE :search OR t.description LIKE :search2 OR u1.name LIKE :search3)";
            $params[':search'] = $searchTerm;
            $params[':search2'] = $searchTerm;
            $params[':search3'] = $searchTerm;
        }
        
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $whereConditions[] = "t.status = :status";
            $params[':status'] = $_GET['status'];
        }
        
        if (isset($_GET['priority']) && !empty($_GET['priority'])) {
            $whereConditions[] = "t.priority = :priority";
            $params[':priority'] = $_GET['priority'];
        }
        
        if (isset($_GET['category']) && !empty($_GET['category'])) {
            $whereConditions[] = "c.name = :category";
            $params[':category'] = $_GET['category'];
        }
        
        // Filter archived tickets (exclude by default unless specifically requested)
        if (isset($_GET['archived']) && $_GET['archived'] === 'true') {
            $whereConditions[] = "t.archived = 1";
        } else if (!isset($_GET['archived']) || $_GET['archived'] !== 'all') {
            $whereConditions[] = "t.archived = 0";
        }
        
        $whereClause = '';
        if (!empty($whereConditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        }
        
        // Get total count with filters
        $countQuery = "SELECT COUNT(*) as total FROM tickets t 
                       LEFT JOIN categories c ON t.category_id = c.id 
                       LEFT JOIN users u1 ON t.created_by = u1.id 
                       $whereClause";
        $countStmt = $this->conn->prepare($countQuery);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $totalCount = $countStmt->fetch()['total'];
        
        // Get paginated tickets with filters
        $query = "SELECT t.*, 
                         c.name as category_name, 
                         c.color as category_color,
                         u1.name as created_by_name,
                         u2.name as assigned_to_name
                  FROM tickets t 
                  LEFT JOIN categories c ON t.category_id = c.id 
                  LEFT JOIN users u1 ON t.created_by = u1.id 
                  LEFT JOIN users u2 ON t.assigned_to = u2.id 
                  $whereClause
                  ORDER BY " . $orderBy . "
                  LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $tickets = $stmt->fetchAll();
        
        return [
            'success' => true,
            'data' => $tickets,
            'pagination' => [
                'total' => (int)$totalCount,
                'page' => $page,
                'limit' => $limit,
                'totalPages' => ceil($totalCount / $limit)
            ]
        ];
    }
    
    public function getTicket($id) {
        $query = "SELECT t.*, 
                         c.name as category_name, 
                         c.color as category_color,
                         u1.name as created_by_name,
                         u2.name as assigned_to_name
                  FROM tickets t 
                  LEFT JOIN categories c ON t.category_id = c.id 
                  LEFT JOIN users u1 ON t.created_by = u1.id 
                  LEFT JOIN users u2 ON t.assigned_to = u2.id 
                  WHERE t.id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $ticket = $stmt->fetch();
        
        if ($ticket) {
            // Get comments for this ticket
            $commentQuery = "SELECT c.*, u.name as author_name 
                           FROM comments c 
                           LEFT JOIN users u ON c.user_id = u.id 
                           WHERE c.ticket_id = :id 
                           ORDER BY c.created_at ASC";
            $commentStmt = $this->conn->prepare($commentQuery);
            $commentStmt->bindParam(':id', $id);
            $commentStmt->execute();
            $ticket['comments'] = $commentStmt->fetchAll();
            
            return [
                'success' => true,
                'data' => $ticket
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Ticket not found'
            ];
        }
    }
    
    public function createTicket($data) {
        $query = "INSERT INTO tickets (title, description, priority, category_id, reporter_id) 
                  VALUES (:title, :description, :priority, :category_id, :reporter_id)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':priority', $data['priority']);
        $stmt->bindParam(':category_id', $data['category_id']);
        $stmt->bindParam(':reporter_id', $data['reporter_id'] ?? 2); // Default to user ID 2
        
        if ($stmt->execute()) {
            $ticketId = $this->conn->lastInsertId();
            return [
                'success' => true,
                'data' => ['id' => $ticketId],
                'message' => 'Ticket created successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to create ticket'
            ];
        }
    }
    
    public function updateTicket($id, $data) {
        $query = "UPDATE tickets SET 
                  title = :title, 
                  description = :description, 
                  status = :status, 
                  priority = :priority, 
                  category_id = :category_id,
                  updated_at = CURRENT_TIMESTAMP
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':priority', $data['priority']);
        $stmt->bindParam(':category_id', $data['category_id']);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Ticket updated successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to update ticket'
            ];
        }
    }
    
    public function deleteTicket($id) {
        $query = "DELETE FROM tickets WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Ticket deleted successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to delete ticket'
            ];
        }
    }
    
    public function archiveTicket($id) {
        $query = "UPDATE tickets SET archived = 1 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Ticket archived successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to archive ticket'
            ];
        }
    }
    
    public function unarchiveTicket($id) {
        $query = "UPDATE tickets SET archived = 0 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Ticket unarchived successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to unarchive ticket'
            ];
        }
    }
    
    public function getCategories() {
        $query = "SELECT * FROM categories ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return [
            'success' => true,
            'data' => $stmt->fetchAll()
        ];
    }
}

// Handle the request
$api = new TicketAPI();
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['action']) && $_GET['action'] === 'categories') {
                echo json_encode($api->getCategories());
            } elseif (isset($_GET['id'])) {
                echo json_encode($api->getTicket($_GET['id']));
            } else {
                echo json_encode($api->getAllTickets());
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode($api->createTicket($data));
            break;
            
        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $_GET['id'] ?? null;
            $action = $_GET['action'] ?? null;
            
            if ($id) {
                if ($action === 'archive') {
                    echo json_encode($api->archiveTicket($id));
                } elseif ($action === 'unarchive') {
                    echo json_encode($api->unarchiveTicket($id));
                } else {
                    echo json_encode($api->updateTicket($id, $data));
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'ID required for update']);
            }
            break;
            
        case 'DELETE':
            $id = $_GET['id'] ?? null;
            if ($id) {
                echo json_encode($api->deleteTicket($id));
            } else {
                echo json_encode(['success' => false, 'message' => 'ID required for delete']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>