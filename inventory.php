<?php
session_start();
include_once '../../includes/database.php'; // Adjusted path

// Ensure admin is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../backend/login.php");
    exit();
}

$message = '';
$message_type = '';

// --- Handle Add New Item --- 
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_item'])) {
    $product_name = trim($_POST['product_name']);
    $stocks = filter_var(trim($_POST['stocks']), FILTER_VALIDATE_INT);
    $price = filter_var(trim($_POST['price']), FILTER_VALIDATE_FLOAT);
    
    // Handle file upload
    $image_path = '';
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/';
        
        // Create uploads directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
        
        if (in_array($file_extension, $allowed_extensions)) {
            // Generate unique filename
            $new_filename = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                $image_path = 'uploads/' . $new_filename;
            } else {
                $message = "Error uploading file. Please try again.";
                $message_type = 'error';
            }
        } else {
            $message = "Invalid file type. Allowed types: JPG, JPEG, PNG, GIF";
            $message_type = 'error';
        }
    }

    if (empty($product_name) || $stocks === false || $stocks < 0 || $price === false || $price < 0) {
        $message = "Product Name, valid Stocks (non-negative integer), and valid Price (non-negative number) are required.";
        $message_type = 'error';
    } else {
        $insert_sql = "INSERT INTO inventory (product_name, stocks, price, image_url) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        if ($stmt) {
            $stmt->bind_param("sids", $product_name, $stocks, $price, $image_path);
            if ($stmt->execute()) {
                // Redirect to avoid form resubmission
                header("Location: inventory.php?success=1");
                exit();
            } else {
                $message = "Error adding product: " . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
        } else {
            $message = "Database error (prepare insert): " . $conn->error;
            $message_type = 'error';
        }
    }
}

// Show success message if redirected
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $message = "Product added successfully.";
    $message_type = 'success';
}

// Handle Delete Item
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    
    // First get the image path to delete the file
    $get_image_sql = "SELECT image_url FROM inventory WHERE id = ?";
    $stmt = $conn->prepare($get_image_sql);
    if ($stmt) {
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            // Delete the image file if it exists
            if (!empty($row['image_url'])) {
                $image_path = '../' . $row['image_url'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
        }
        $stmt->close();
    }
    
    // Delete from database
    $delete_sql = "DELETE FROM inventory WHERE id = ?";
    $stmt = $conn->prepare($delete_sql);
    if ($stmt) {
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            $message = "Product deleted successfully.";
            $message_type = 'success';
        } else {
            $message = "Error deleting product: " . $stmt->error;
            $message_type = 'error';
        }
        $stmt->close();
    }
    
    // Redirect to remove the delete parameter from URL
    header("Location: inventory.php");
    exit();
}

// Fetch all inventory items
$inventory_items = [];
$fetch_sql = "SELECT id, product_name, stocks, price, image_url, last_updated FROM inventory ORDER BY stocks ASC";
$result = $conn->query($fetch_sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $inventory_items[] = $row;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Inventory - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background: #f4f6fa;
        }
        .main-content {
            margin-left: 20px;
            min-height: 100vh;
            padding: 40px;
        }
        .settings-card {
            width: 100%;
            max-width: 100%;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            padding: 2rem;
        }
        .settings-card h2 {
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
        .settings-section {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .inventory-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 1.5rem 0;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        }
        .inventory-table th {
            background: #f8f9fa;
            color: #28a745;
            font-weight: 600;
            padding: 1rem;
            text-align: left;
            border-bottom: 2px solid #e9ecef;
        }
        .inventory-table td {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
            color: #495057;
            vertical-align: middle;
        }
        .inventory-table tr:last-child td {
            border-bottom: none;
        }
        .inventory-table tr:hover {
            background: #f8f9fa;
        }
        .inventory-table img {
            max-width: 80px;
            max-height: 80px;
            border-radius: 8px;
            object-fit: cover;
        }
        .stock-status {
            font-weight: bold;
            padding: 4px 10px;
            border-radius: 12px;
            display: inline-block;
            font-size: 0.98em;
        }
        .stock-out { background: #f8d7da; color: #c82333; border: 1px solid #f5c6cb; }
        .stock-low { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .stock-in { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .action-links {
            display: flex;
            gap: 0.5rem;
        }
        .message {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            text-align: center;
            font-size: 1.05rem;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #ced4da;
            padding: 0.75rem;
            transition: all 0.2s;
        }
        .form-control:focus, .form-select:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }
        .btn-primary {
            background: #28a745;
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .btn-primary:hover {
            background: #218838;
            transform: translateY(-1px);
        }
        .btn-danger {
            background: #dc3545;
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-1px);
        }
        .preview-image {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            margin-top: 1rem;
            display: none;
        }
        @media (max-width: 991px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }
            .inventory-table {
                display: block;
                overflow-x: auto;
            }
            .action-links {
                flex-direction: column;
            }
        }
        @media (max-width: 600px) {
            .settings-card {
                padding: 1.2rem;
            }
            .settings-section {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-content w-100">
        <div class="settings-card">
            <h2 class="mb-4 text-primary"><i class="fas fa-boxes me-2"></i>Manage Parts & Accessories Inventory</h2>
            
            <div class="settings-section">
                <?php if (!empty($message)): ?>
                    <div class="message <?php echo $message_type; ?> position-relative" id="validationMessage">
                        <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close position-absolute top-0 end-0 m-2" aria-label="Close" style="background:none;border:none;font-size:1.2rem;" onclick="document.getElementById('validationMessage').style.display='none';">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <script>
                    if (window.location.search.includes('success=1')) {
                        window.history.replaceState({}, document.title, window.location.pathname);
                    }
                    </script>
                <?php endif; ?>

                <form action="inventory.php" method="POST" enctype="multipart/form-data" class="mb-4">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="product_name" class="form-label">Product Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="product_name" name="product_name" value="<?php echo isset($_POST['product_name']) ? htmlspecialchars($_POST['product_name']) : ''; ?>" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="stocks" class="form-label">Stocks <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="stocks" name="stocks" value="<?php echo isset($_POST['stocks']) ? htmlspecialchars($_POST['stocks']) : '0'; ?>" min="0" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="price" class="form-label">Price (PHP) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>" required>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="product_image" class="form-label">Product Image</label>
                            <input type="file" class="form-control" id="product_image" name="product_image" accept="image/*" onchange="previewImage(this)">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <img id="imagePreview" class="preview-image" src="#" alt="Image Preview">
                            <button type="submit" name="add_item" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Add Item to Inventory
                            </button>
                        </div>
                    </div>
                </form>

                <?php if (!empty($inventory_items)): ?>
                    <table class="inventory-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Product Name</th>
                                <th>Stocks</th>
                                <th>Status</th>
                                <th>Price (PHP)</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inventory_items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['id']); ?></td>
                                    <td>
                                        <?php if (!empty($item['image_url'])): ?>
                                            <img src="../<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                        <?php else: ?>
                                            <span class="text-muted">No Image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['stocks']); ?></td>
                                    <td>
                                        <?php
                                        if ($item['stocks'] == 0) {
                                            echo '<span class="stock-status stock-out">Out of Stock</span>';
                                        } elseif ($item['stocks'] > 0 && $item['stocks'] <= 5) {
                                            echo '<span class="stock-status stock-low">Low Stock</span>';
                                        } else {
                                            echo '<span class="stock-status stock-in">In Stock</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                    <td><?php echo date('M d, Y h:i A', strtotime($item['last_updated'])); ?></td>
                                    <td>
                                        <div class="action-links">
                                            <a href="edit_inventory_item.php?id=<?php echo $item['id']; ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-edit me-1"></i>Edit
                                            </a>
                                            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $item['id']; ?>">
                                                <i class="fas fa-trash me-1"></i>Delete
                                            </button>
                                        </div>

                                        <!-- Delete Modal -->
                                        <div class="modal fade" id="deleteModal<?php echo $item['id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Delete Inventory Item</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>Are you sure you want to delete this item?</p>
                                                        <p><strong>Product:</strong> <?php echo htmlspecialchars($item['product_name']); ?></p>
                                                        <p><strong>Current Stock:</strong> <?php echo htmlspecialchars($item['stocks']); ?></p>
                                                        <p><strong>Price:</strong> ₱<?php echo number_format($item['price'], 2); ?></p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <a href="inventory.php?delete=<?php echo $item['id']; ?>" class="btn btn-danger">Delete Item</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No inventory items found. Use the form above to add items.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
            }
        }
    </script>
</body>
</html>
