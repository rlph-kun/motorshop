<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../backend/login.php");
    exit();
}

include '../../includes/database.php';

// Input validation
$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

if (!$product_id || !$quantity || $quantity < 1) {
    echo "<script>alert('❌ Invalid input data.'); window.location.href='record_sale.php';</script>";
    exit();
}

// Get current stock and price using prepared statement
$stmt = $conn->prepare("SELECT stocks, price FROM inventory WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    echo "<script>alert('❌ Product not found.'); window.location.href='record_sale.php';</script>";
    exit();
}

if ($product['stocks'] >= $quantity) {
    $new_stock = $product['stocks'] - $quantity;
    $price = $product['price'];
    $total_amount = $quantity * $price;

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert into sales_log using prepared statement
        $stmt = $conn->prepare("INSERT INTO sales_log (product_id, quantity, total_amount, sale_date) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iid", $product_id, $quantity, $total_amount);
        $stmt->execute();

        // Update product stock using prepared statement
        $stmt = $conn->prepare("UPDATE inventory SET stocks = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_stock, $product_id);
        $stmt->execute();

        $conn->commit();
        echo "<script>alert('✅ Sale recorded successfully.'); window.location.href='record_sale.php';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('❌ Error recording sale: " . addslashes($e->getMessage()) . "'); window.location.href='record_sale.php';</script>";
    }
} else {
    echo "<script>alert('❌ Not enough stock.'); window.location.href='record_sale.php';</script>";
}
?>
