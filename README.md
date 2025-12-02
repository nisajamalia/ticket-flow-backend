# XAMPP Setup Instructions for Ticketing System

## 📋 Prerequisites

- XAMPP installed on your system
- Browser to access phpMyAdmin

## 🚀 Setup Steps

### 1. Start XAMPP Services

```bash
# Start Apache and MySQL from XAMPP Control Panel
# Or use command line:
xampp start apache
xampp start mysql
```

### 2. Create Database

1. Open browser and go to: `http://localhost/phpmyadmin`
2. Click "Import" tab
3. Choose file: `backend/database/create_database.sql`
4. Click "Go" to execute

**OR** run this command in MySQL:

```bash
mysql -u root -p < backend/database/create_database.sql
```

### 3. Copy Backend Files to XAMPP

```bash
# Copy the backend folder to XAMPP htdocs
copy backend/* C:/xampp/htdocs/ticketing-api/
```

### 4. Test Database Connection

Visit: `http://localhost/ticketing-api/api/tickets.php`

You should see JSON response with tickets data.

## 📊 Database Structure

### Tables Created:

- **users** - User accounts (admin, agents, customers)
- **categories** - Ticket categories
- **tickets** - Main tickets table
- **comments** - Ticket comments/replies
- **attachments** - File attachments
- **audit_logs** - Change tracking

### Sample Data Included:

- 3 Users (admin, john_doe, jane_smith)
- 5 Categories (Technical Support, Billing, etc.)
- 3 Sample Tickets
- 4 Sample Comments

## 🔗 API Endpoints

### Get All Tickets:

```
GET http://localhost/ticketing-api/api/tickets.php
```

### Get Single Ticket:

```
GET http://localhost/ticketing-api/api/tickets.php?id=1
```

### Create Ticket:

```
POST http://localhost/ticketing-api/api/tickets.php
Content-Type: application/json

{
    "title": "New Issue",
    "description": "Issue description",
    "priority": "medium",
    "category_id": 1
}
```

### Update Ticket:

```
PUT http://localhost/ticketing-api/api/tickets.php?id=1
Content-Type: application/json

{
    "title": "Updated Title",
    "description": "Updated description",
    "status": "in_progress",
    "priority": "high",
    "category_id": 1
}
```

### Delete Ticket:

```
DELETE http://localhost/ticketing-api/api/tickets.php?id=1
```

### Get Categories:

```
GET http://localhost/ticketing-api/api/tickets.php?action=categories
```

## 🔧 Configure Frontend to Use API

Update your Vue.js frontend to use the PHP API instead of localStorage:

```javascript
// In your store or service file
const API_BASE_URL = "http://localhost/ticketing-api/api";

// Example API calls
async function fetchTickets() {
  const response = await fetch(`${API_BASE_URL}/tickets.php`);
  return await response.json();
}

async function createTicket(ticketData) {
  const response = await fetch(`${API_BASE_URL}/tickets.php`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(ticketData),
  });
  return await response.json();
}
```

## 🔍 View Database in phpMyAdmin

1. Go to: `http://localhost/phpmyadmin`
2. Select `ticketing_system` database
3. Browse tables to see data:
   - **ticket_details** (view with all joined data)
   - **tickets** (main table)
   - **users** (user accounts)
   - **categories** (ticket categories)

## 📈 Sample Queries

```sql
-- Get all open tickets
SELECT * FROM ticket_details WHERE status = 'open';

-- Get tickets by priority
SELECT * FROM ticket_details WHERE priority = 'high';

-- Get user's tickets
SELECT * FROM ticket_details WHERE reporter_name = 'John Doe';

-- Get ticket statistics
SELECT
    status,
    COUNT(*) as count
FROM tickets
GROUP BY status;
```

## 🛠️ Troubleshooting

### Common Issues:

1. **Connection Error**: Check XAMPP MySQL is running
2. **CORS Error**: API includes CORS headers for localhost:3000
3. **Database Not Found**: Run the SQL creation script
4. **Permission Error**: Check file permissions in htdocs

### Verify Setup:

```bash
# Check MySQL is running
netstat -an | findstr :3306

# Test API endpoint
curl http://localhost/ticketing-api/api/tickets.php
```

Now you have a full PHP/MySQL backend for your ticketing system! 🎉
