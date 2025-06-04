<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../backend/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Sale - Admin Panel</title>
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
        .settings-section h4 {
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #333;
        }
        .form-label {
            font-weight: 500;
            color: #555;
            margin-bottom: 0.5rem;
        }
        select, input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ced4da;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        select:focus, input:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
            outline: none;
        }
        .product-info {
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            display: none;
            border: 1px solid #e9ecef;
        }
        .product-info.active {
            display: block;
        }
        .product-info p {
            margin-bottom: 0.5rem;
            color: #495057;
        }
        .product-info p:last-child {
            margin-bottom: 0;
        }
        button[type="submit"] {
            background: linear-gradient(90deg, #28a745 60%, #218838 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            width: 100%;
            box-shadow: 0 2px 8px rgba(40,167,69,0.08);
            transition: all 0.2s;
        }
        button[type="submit"]:hover {
            background: linear-gradient(90deg, #218838 60%, #28a745 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(40,167,69,0.12);
        }
        .error {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.5rem;
            display: none;
        }
        #totalAmount {
            font-size: 1.25rem;
            font-weight: 600;
            color: #28a745;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            margin-top: 0.5rem;
            border: 1px solid #e9ecef;
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
    <?php
    include '../../includes/database.php';
    ?>
    <div class="main-content w-100">
        <div class="settings-card">
            <h2 class="mb-4 text-primary"><i class="fas fa-cash-register me-2"></i>Record a Sale</h2>
            
            <div class="settings-section">
                <form action="save_sale.php" method="POST" id="saleForm">
                    <div class="mb-4">
                        <label for="product_id" class="form-label">Product:</label>
                        <select name="product_id" id="product_id" class="form-select" required>
                            <option value="">Select a product</option>
                            <?php
                            $products = mysqli_query($conn, "SELECT id, product_name, stocks, price FROM inventory WHERE stocks > 0");
                            while ($p = mysqli_fetch_assoc($products)) {
                                echo "<option value='{$p['id']}' data-stocks='{$p['stocks']}' data-price='{$p['price']}'>{$p['product_name']} (In Stock: {$p['stocks']})</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="product-info" id="productInfo">
                        <p>Price: ₱<span id="productPrice">0.00</span></p>
                        <p>Available Stock: <span id="availableStock">0</span></p>
                    </div>
                    
                    <div class="mb-4">
                        <label for="quantity" class="form-label">Quantity Sold:</label>
                        <input type="number" name="quantity" id="quantity" class="form-control" min="1" required>
                        <div class="error" id="quantityError">Quantity cannot exceed available stock</div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Total Amount:</label>
                        <div id="totalAmount">₱0.00</div>
                    </div>
                    
                    <button type="submit">Record Sale</button>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const productSelect = document.getElementById('product_id');
            const quantityInput = document.getElementById('quantity');
            const productInfo = document.getElementById('productInfo');
            const productPrice = document.getElementById('productPrice');
            const availableStock = document.getElementById('availableStock');
            const totalAmount = document.getElementById('totalAmount');
            const quantityError = document.getElementById('quantityError');
            const saleForm = document.getElementById('saleForm');

            function updateProductInfo() {
                const selectedOption = productSelect.options[productSelect.selectedIndex];
                if (selectedOption.value) {
                    const stocks = selectedOption.dataset.stocks;
                    const price = selectedOption.dataset.price;
                    productPrice.textContent = parseFloat(price).toFixed(2);
                    availableStock.textContent = stocks;
                    productInfo.classList.add('active');
                    quantityInput.max = stocks;
                } else {
                    productInfo.classList.remove('active');
                }
                updateTotal();
            }

            function updateTotal() {
                const quantity = parseInt(quantityInput.value) || 0;
                const price = parseFloat(productPrice.textContent) || 0;
                const total = quantity * price;
                totalAmount.textContent = '₱' + total.toFixed(2);
            }

            function validateQuantity() {
                const quantity = parseInt(quantityInput.value) || 0;
                const maxStock = parseInt(availableStock.textContent) || 0;
                if (quantity > maxStock) {
                    quantityError.style.display = 'block';
                    return false;
                }
                quantityError.style.display = 'none';
                return true;
            }

            productSelect.addEventListener('change', updateProductInfo);
            quantityInput.addEventListener('input', function() {
                updateTotal();
                validateQuantity();
            });

            saleForm.addEventListener('submit', function(e) {
                if (!validateQuantity()) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>
