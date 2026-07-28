<?php
// admin/customers.php - Customers Management
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../connection.php';
$conn->select_db('travel_agency');

$success = '';
$error = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $customer_id = (int)$_GET['delete'];
    
    // Check if customer has bookings
    $check_sql = "SELECT COUNT(*) as count FROM bookings WHERE customer_id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    $stmt->close();
    
    if ($count > 0) {
        $error = "Cannot delete customer with existing bookings. Delete bookings first.";
    } else {
        $delete_sql = "DELETE FROM customers WHERE customer_id = ?";
        $stmt = $conn->prepare($delete_sql);
        $stmt->bind_param("i", $customer_id);
        
        if ($stmt->execute()) {
            $success = "Customer deleted successfully!";
        } else {
            $error = "Error deleting customer: " . $conn->error;
        }
        $stmt->close();
    }
}

// Handle Status Update
if (isset($_POST['update_status'])) {
    $customer_id = (int)$_POST['customer_id'];
    $status = $_POST['status'];
    
    $update_sql = "UPDATE customers SET status=? WHERE customer_id=?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $status, $customer_id);
    
    if ($stmt->execute()) {
        $success = "Customer status updated successfully!";
    } else {
        $error = "Error updating status: " . $conn->error;
    }
    $stmt->close();
}

// Search functionality
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$where_clause = "";
if (!empty($search)) {
    $where_clause = "WHERE full_name LIKE '%$search%' OR email LIKE '%$search%' OR phone LIKE '%$search%'";
}

// Get all customers
$customers_sql = "SELECT c.*, 
                  COUNT(b.booking_id) as total_bookings,
                  COALESCE(SUM(b.total_amount), 0) as total_spent
                  FROM customers c
                  LEFT JOIN bookings b ON c.customer_id = b.customer_id
                  $where_clause
                  GROUP BY c.customer_id
                  ORDER BY c.created_at DESC";
$customers_result = $conn->query($customers_sql);

// Get statistics
$stats_sql = "SELECT 
    COUNT(*) as total_customers,
    SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) as active_customers,
    SUM(CASE WHEN status='inactive' THEN 1 ELSE 0 END) as inactive_customers
    FROM customers";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Customers - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: #f5f6fa;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 1.8rem;
        }

        .header-links a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            padding: 8px 16px;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .header-links a:hover {
            background: rgba(255,255,255,0.2);
        }

        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #667eea;
        }

        .stat-card h3 {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .stat-card p {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .search-bar {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .search-bar form {
            display: flex;
            gap: 10px;
        }

        .search-bar input {
            flex: 1;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
        }

        .search-bar button {
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }

        .customers-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .customers-table h2 {
            padding: 20px 30px;
            background: #f8f9fa;
            color: #333;
            border-bottom: 2px solid #e0e0e0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f8f9fa;
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #555;
            border-bottom: 2px solid #e0e0e0;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .action-btns {
            display: flex;
            gap: 5px;
        }

        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9rem;
            display: inline-block;
        }

        .btn-edit {
            background: #ffc107;
            color: #000;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-edit:hover { background: #e0a800; }
        .btn-delete:hover { background: #c82333; }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }

        .modal-content {
            background: white;
            margin: 10% auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
        }

        .close {
            font-size: 2rem;
            cursor: pointer;
            color: #999;
        }

        .close:hover {
            color: #333;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }

        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
        }

        .customer-details {
            line-height: 1.6;
        }

        .customer-details strong {
            display: inline-block;
            width: 120px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-users"></i> Manage Customers</h1>
        <div class="header-links">
            <a href="index.php"><i class="fas fa-dashboard"></i> Dashboard</a>
            <a href="packages.php"><i class="fas fa-box"></i> Packages</a>
            <a href="bookings.php"><i class="fas fa-calendar"></i> Bookings</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><i class="fas fa-users"></i> Total Customers</h3>
                <p><?php echo $stats['total_customers']; ?></p>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-check-circle"></i> Active Customers</h3>
                <p><?php echo $stats['active_customers']; ?></p>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-ban"></i> Inactive Customers</h3>
                <p><?php echo $stats['inactive_customers']; ?></p>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="search-bar">
            <form method="GET" action="">
                <input type="text" name="search" placeholder="Search by name, email, or phone..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit"><i class="fas fa-search"></i> Search</button>
                <?php if (!empty($search)): ?>
                    <a href="customers.php" style="padding: 12px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 8px;">
                        <i class="fas fa-times"></i> Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Customers Table -->
        <div class="customers-table">
            <h2>All Customers (<?php echo $customers_result->num_rows; ?>)</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Total Bookings</th>
                        <th>Total Spent</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            
                <tbody>
                    <?php if ($customers_result->num_rows > 0): ?>
                        <?php while ($customer = $customers_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $customer['customer_id']; ?></td>
                                <td><?php echo htmlspecialchars(trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')) ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                <td><?php echo htmlspecialchars($customer['phone'] ?? 'N/A'); ?></td>
                                <td><?php echo $customer['total_bookings']; ?></td>
                                <td>NPR <?php echo number_format($customer['total_spent'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $customer['status']; ?>">
                                        <?php echo ucfirst($customer['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($customer['created_at'])); ?></td>
                                <td class="action-btns">
                                    <button class="btn btn-edit" onclick="editCustomer(<?php echo $customer['customer_id']; ?>, '<?php echo $customer['status']; ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?delete=<?php echo $customer['customer_id']; ?>" class="btn btn-delete"
                                       onclick="return confirm('Are you sure you want to delete this customer?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 30px;">
                                <?php echo empty($search) ? 'No customers found.' : 'No customers match your search.'; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Update Customer Status</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="customer_id" id="edit_customer_id">
                
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="edit_status" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <button type="submit" name="update_status" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Status
                </button>
            </form>
        </div>
    </div>

    <script>
        function editCustomer(id, status) {
            document.getElementById('edit_customer_id').value = id;
            document.getElementById('edit_status').value = status;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>