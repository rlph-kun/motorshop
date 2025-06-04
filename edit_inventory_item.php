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
$item = null;
$item_id = null;

// Check if ID is provided for editing
if (isset($_GET['id'])) {
    $item_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if (!$item_id) {
        $message = "Invalid Item ID.";
        $message_type = 'error';
    }
} else {
    $message = "No Item ID provided.";
    $message_type = 'error';
}

// Fetch item details if ID is valid and no critical error message set for ID yet
if ($item_id && ($message_type !== 'error' || $message === '')) {
    $sql = "SELECT * FROM inventory WHERE id = ?";
    $stmt_fetch = $conn->prepare($sql);
    if ($stmt_fetch) {
        $stmt_fetch->bind_param("i", $item_id);
        $stmt_fetch->execute();
        $result = $stmt_fetch->get_result();
        if ($result->num_rows === 1) {
            $item = $result->fetch_assoc();
        } else {
            $message = "Inventory item not found.";
            $message_type = 'error';
            $item = null;
        }
        $stmt_fetch->close();
    } else {
        $message = "Database error (fetch item): " . $conn->error;
        $message_type = 'error';
        $item = null;
    }
}

// --- Handle Item Update --- 
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_item_details']) && $item_id) {
    $product_name = trim($_POST['product_name']);
    $stocks = filter_var(trim($_POST['stocks']), FILTER_VALIDATE_INT);
    $price = filter_var(trim($_POST['price']), FILTER_VALIDATE_FLOAT);
    $image_path = (isset($item) && isset($item['image_url'])) ? $item['image_url'] : '';

    // Handle file upload if a new file is provided
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_extension = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
        if (in_array($file_extension, $allowed_extensions)) {
            $new_filename = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                // Delete old image if it exists and is a file
                if (isset($item) && !empty($item['image_url'])) {
                    $old_path = '../' . $item['image_url'];
                    if (file_exists($old_path)) {
                        unlink($old_path);
                    }
                }
                $image_path = 'uploads/' . $new_filename;
            } else {
                $message = "Error uploading new image.";
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
        $update_sql = "UPDATE inventory SET 
                        product_name = ?, 
                        stocks = ?, 
                        price = ?, 
                        image_url = ?,
                        last_updated = CURRENT_TIMESTAMP
                      WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        if ($stmt) {
            $stmt->bind_param("sidsi", $product_name, $stocks, $price, $image_path, $item_id);
            if ($stmt->execute()) {
                $message = "Item '" . htmlspecialchars($product_name) . "' (ID: {$item_id}) updated successfully.";
                $message_type = 'success';
                // Refresh item details from the database to ensure all fields are up-to-date
                $sql = "SELECT * FROM inventory WHERE id = ?";
                $stmt_fetch = $conn->prepare($sql);
                if ($stmt_fetch) {
                    $stmt_fetch->bind_param("i", $item_id);
                    $stmt_fetch->execute();
                    $result = $stmt_fetch->get_result();
                    if ($result->num_rows === 1) {
                        $item = $result->fetch_assoc();
                    }
                    $stmt_fetch->close();
                }
            } else {
                $message = "Error updating item: " . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
        } else {
            $message = "Database error (prepare update): " . $conn->error;
            $message_type = 'error';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Inventory Item - Admin Panel</title>
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
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        .form-control {
            border-radius: 8px;
            border: 1px solid #ced4da;
            padding: 0.75rem;
            transition: all 0.2s;
        }
        .form-control:focus {
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
        .btn-secondary {
            background: #6c757d;
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-1px);
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
        .current-image {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            margin-bottom: 1rem;
            object-fit: cover;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            color: #28a745;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 1.5rem;
            transition: all 0.2s;
        }
        .back-link:hover {
            color: #218838;
            transform: translateX(-5px);
        }
        @media (max-width: 991px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 20px;
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
            <h2 class="mb-4 text-primary"><i class="fas fa-edit me-2"></i>Edit Inventory Item</h2>
            
            <a href="inventory.php" class="back-link">
                <i class="fas fa-arrow-left me-2"></i>Back to Inventory
            </a>

            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?> position-relative" id="validationMessage">
                    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close position-absolute top-0 end-0 m-2" aria-label="Close" style="background:none;border:none;font-size:1.2rem;" onclick="document.getElementById('validationMessage').style.display='none';">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>

            <?php if ($item): ?>
                <div class="settings-section">
                    <form action="edit_inventory_item.php?id=<?php echo $item_id; ?>" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="item_id_hidden" value="<?php echo $item_id; ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="product_name" class="form-label">Product Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="product_name" name="product_name" value="<?php echo htmlspecialchars($item['product_name']); ?>" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="stocks" class="form-label">Stocks <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="stocks" name="stocks" value="<?php echo htmlspecialchars($item['stocks']); ?>" min="0" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="price" class="form-label">Price (PHP) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" value="<?php echo htmlspecialchars($item['price']); ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="product_image" class="form-label">Product Image</label>
                                <div class="mb-3">
                                    <img 
                                        src="<?php echo !empty($item['image_url']) ? '../' . htmlspecialchars($item['image_url']) : '#'; ?>" 
                                        alt="Current Image" 
                                        class="current-image" 
                                        id="imagePreview"
                                        style="<?php echo !empty($item['image_url']) ? '' : 'display:none;'; ?>"
                                    >
                                </div>
                                <input type="file" class="form-control" id="product_image" name="product_image" accept="image/*" onchange="previewImage(this)">
                                <small class="text-muted">Allowed formats: JPG, JPEG, PNG, GIF</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <button type="submit" name="update_item_details" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                                <a href="inventory.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            <?php elseif(empty($message)): ?>
                <div class="settings-section">
                    <div class="text-center py-4">
                        <i class="fas fa-exclamation-circle fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Loading item details or item not specified.</p>
                    </div>
                </div>
            <?php endif; ?>
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