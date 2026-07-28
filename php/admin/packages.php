<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include "../connection.php";

$error = '';
$success = '';
$edit_package = null;

// Handle Delete
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $package_id = (int)$_GET['delete'];
    $sql = "DELETE FROM packages WHERE package_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $package_id);
    if ($stmt->execute()) {
        $success = "Package deleted successfully!";
    } else {
        $error = "Error deleting package: " . $conn->error;
    }
    $stmt->close();
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $package_name = $conn->real_escape_string($_POST['package_name']);
    $destination = $conn->real_escape_string($_POST['destination']);
    $description = $conn->real_escape_string($_POST['description']);
    $price = (float)$_POST['price'];
    $duration_days = (int)$_POST['duration_days'];
    $available_from = $_POST['available_from'];
    $available_to = $_POST['available_to'];
    $available_slots = (int)$_POST['available_slots'];
    $status = $_POST['status'];

    if (isset($_POST['package_id']) && !empty($_POST['package_id'])) {
        // Update existing package
        $package_id = (int)$_POST['package_id'];
        $sql = "UPDATE packages SET package_name=?, destination=?, description=?, price=?, duration_days=?, 
                available_from=?, available_to=?, available_slots=?, status=? WHERE package_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssdissisi", $package_name, $destination, $description, $price, $duration_days, 
                         $available_from, $available_to, $available_slots, $status, $package_id);
        
        if ($stmt->execute()) {
            $success = "Package updated successfully!";
            $edit_package = null;
        } else {
            $error = "Error updating package: " . $conn->error;
        }
    } else {
        // Add new package
        $sql = "INSERT INTO packages (package_name, destination, description, price, duration_days, 
                available_from, available_to, available_slots, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssdissis", $package_name, $destination, $description, $price, $duration_days, 
                         $available_from, $available_to, $available_slots, $status);
        
        if ($stmt->execute()) {
            $success = "Package added successfully!";
        } else {
            $error = "Error adding package: " . $conn->error;
        }
    }
    $stmt->close();
}

// Get package for editing
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $package_id = (int)$_GET['edit'];
    $sql = "SELECT * FROM packages WHERE package_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $package_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_package = $result->fetch_assoc();
    }
    $stmt->close();
}

// Get all packages
$packages_sql = "SELECT * FROM packages ORDER BY package_id DESC";
$packages_result = $conn->query($packages_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Packages - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', Arial, sans-serif;
            background: #f5f7fb;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }

        .header-right {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5a6edc;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }

        .alert-error {
            background: #fee2e2;
            color: #7f1d1d;
            border: 1px solid #fca5a5;
        }

        .form-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,.1);
            margin-bottom: 30px;
        }

        .form-section h2 {
            color: #667eea;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #667eea;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            padding: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 15px;
            font-family: inherit;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .form-actions button {
            flex: 1;
        }

        .packages-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,.1);
        }

        .packages-section h2 {
            color: #667eea;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #667eea;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table thead {
            background: #f9fafb;
        }

        table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e5e7eb;
        }

        table td {
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
        }

        table tr:hover {
            background: #f9fafb;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .status-active {
            background: #d1fae5;
            color: #065f46;
        }

        .status-inactive {
            background: #fee2e2;
            color: #7f1d1d;
        }

        .action-btns {
            display: flex;
            gap: 8px;
        }

        .action-btns a {
            padding: 8px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-edit {
            background: #3b82f6;
            color: white;
        }

        .btn-edit:hover {
            background: #2563eb;
        }

        .btn-delete {
            background: #ef4444;
            color: white;
        }

        .btn-delete:hover {
            background: #dc2626;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #9ca3af;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .form-full-width {
            grid-column: 1 / -1;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>

        <div class="header">
            <div>
                <h1><i class="fas fa-box"></i> Manage Packages</h1>
                <p>Add, edit, or delete travel packages</p>
            </div>
            <div class="header-right">
                <a href="logout.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Add/Edit Package Form -->
        <div class="form-section">
            <h2>
                <i class="fas fa-plus-circle"></i>
                <?php echo $edit_package ? 'Edit Package' : 'Add New Package'; ?>
            </h2>

            <form method="POST" action="">
                <?php if ($edit_package): ?>
                    <input type="hidden" name="package_id" value="<?php echo $edit_package['package_id']; ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Package Name *</label>
                        <input type="text" name="package_name" required
                               value="<?php echo $edit_package ? htmlspecialchars($edit_package['package_name']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Destination *</label>
                        <input type="text" name="destination" required
                               value="<?php echo $edit_package ? htmlspecialchars($edit_package['destination']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Price (NPR) *</label>
                        <input type="number" name="price" step="0.01" required
                               value="<?php echo $edit_package ? $edit_package['price'] : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Duration (Days) *</label>
                        <input type="number" name="duration_days" min="1" required
                               value="<?php echo $edit_package ? $edit_package['duration_days'] : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Available From *</label>
                        <input type="date" name="available_from" required
                               value="<?php echo $edit_package ? $edit_package['available_from'] : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Available To *</label>
                        <input type="date" name="available_to" required
                               value="<?php echo $edit_package ? $edit_package['available_to'] : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Available Slots *</label>
                        <input type="number" name="available_slots" min="1" required
                               value="<?php echo $edit_package ? $edit_package['available_slots'] : '50'; ?>">
                    </div>

                    <div class="form-group">
                        <label>Status *</label>
                        <select name="status" required>
                            <option value="active" <?php echo ($edit_package && $edit_package['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($edit_package && $edit_package['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="form-group form-full-width">
                        <label>Description *</label>
                        <textarea name="description" required><?php echo $edit_package ? htmlspecialchars($edit_package['description']) : ''; ?></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i>
                        <?php echo $edit_package ? 'Update Package' : 'Add Package'; ?>
                    </button>
                    <?php if ($edit_package): ?>
                        <a href="packages.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Packages List -->
        <div class="packages-section">
            <h2><i class="fas fa-list"></i> All Packages</h2>

            <?php if ($packages_result && $packages_result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Package Name</th>
                                <th>Destination</th>
                                <th>Price</th>
                                <th>Duration</th>
                                <th>Slots</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($package = $packages_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $package['package_id']; ?></td>
                                    <td><?php echo htmlspecialchars($package['package_name']); ?></td>
                                    <td><?php echo htmlspecialchars($package['destination']); ?></td>
                                    <td>NPR <?php echo number_format($package['price'], 2); ?></td>
                                    <td><?php echo $package['duration_days']; ?> days</td>
                                    <td><?php echo $package['available_slots']; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $package['status']; ?>">
                                            <?php echo ucfirst($package['status']); ?>
                                        </span>
                                    </td>
                                    <td class="action-btns">
                                        <a href="?edit=<?php echo $package['package_id']; ?>" class="btn-edit">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="?delete=<?php echo $package['package_id']; ?>" class="btn-delete"
                                           onclick="return confirm('Are you sure you want to delete this package?');">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-inbox" style="font-size: 48px; color: #d1d5db; margin-bottom: 10px;"></i>
                    <p>No packages found. Create your first package above.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php $conn->close(); ?>
</body>
</html>