<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../backend/login.php");
    exit();
}

include '../../includes/database.php';

// Pagination settings
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Search functionality
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$search_condition = $search ? "AND p.product_name LIKE '%$search%'" : "";

// Get total records for pagination
$total_query = mysqli_query($conn, "
    SELECT COUNT(*) as total 
    FROM sales_log s
    JOIN inventory p ON s.product_id = p.id
    WHERE 1=1 $search_condition
");
$total_records = mysqli_fetch_assoc($total_query)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get total sales
$total_sales_query = mysqli_query($conn, "
    SELECT SUM(quantity) as total_quantity 
    FROM sales_log
");
$total = mysqli_fetch_assoc($total_sales_query);

// Get sales records with pagination
$sales_query = mysqli_query($conn, "
    SELECT s.sale_date, p.product_name, s.quantity, (s.quantity * p.price) as total_amount
    FROM sales_log s
    JOIN inventory p ON s.product_id = p.id
    WHERE 1=1 $search_condition
    ORDER BY s.sale_date DESC
    LIMIT $offset, $records_per_page
");
$sales = mysqli_fetch_all($sales_query, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales History - Admin Panel</title>
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
        .summary-box {
            background: #fff;
            color: #28a745;
            padding: 1.5rem;
            border-radius: 12px;
            margin: 1.5rem auto;
            width: 350px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            font-size: 1.3rem;
            font-weight: 500;
            gap: 15px;
            justify-content: center;
        }
        .summary-box .icon {
            font-size: 2.2rem;
            margin-right: 10px;
        }
        .sales-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 1.5rem 0;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        }
        .sales-table th {
            background: #f8f9fa;
            color: #28a745;
            font-weight: 600;
            padding: 1rem;
            text-align: left;
            border-bottom: 2px solid #e9ecef;
        }
        .sales-table td {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
            color: #495057;
        }
        .sales-table tr:last-child td {
            border-bottom: none;
        }
        .sales-table tr:hover {
            background: #f8f9fa;
        }
        .pagination {
            margin: 1.5rem 0;
            display: flex;
            justify-content: center;
            gap: 0.5rem;
        }
        .pagination a {
            display: inline-block;
            padding: 0.5rem 1rem;
            text-decoration: none;
            color: #495057;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .pagination a.active {
            background-color: #28a745;
            color: white;
            border-color: #28a745;
        }
        .pagination a:hover:not(.active) {
            background-color: #f8f9fa;
            border-color: #28a745;
            color: #28a745;
        }
        .search-box {
            margin: 1.5rem 0;
            display: flex;
            gap: 1rem;
            justify-content: center;
            align-items: center;
        }
        .search-box input {
            padding: 0.75rem;
            width: 300px;
            border-radius: 8px;
            border: 1px solid #ced4da;
            font-size: 1rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .search-box input:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
            outline: none;
        }
        .search-box button {
            padding: 0.75rem 1.5rem;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }
        .search-box button:hover {
            background: #218838;
            transform: translateY(-1px);
        }
        .export-btn {
            padding: 0.75rem 1.5rem;
            background: #2196F3;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 1rem 0;
            font-weight: 500;
            transition: all 0.2s;
        }
        .export-btn:hover {
            background: #1976D2;
            transform: translateY(-1px);
        }
        .no-records {
            text-align: center;
            color: #6c757d;
            font-size: 1.1rem;
            padding: 2rem;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        }
        @media (max-width: 991px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }
            .sales-table {
                display: block;
                overflow-x: auto;
            }
        }
        @media (max-width: 600px) {
            .settings-card {
                padding: 1.2rem;
            }
            .settings-section {
                padding: 1rem;
            }
            .search-box {
                flex-direction: column;
            }
            .search-box input {
                width: 100%;
            }
            .summary-box {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-content w-100">
        <div class="settings-card">
            <h2 class="mb-4 text-primary"><i class="fas fa-history me-2"></i>Sales History</h2>
            
            <div class="settings-section">
                <div class="summary-box">
                    <i class="fas fa-chart-line icon"></i>
                    <span>Total Sales: ₱<?php echo number_format($total['total_quantity'], 2); ?></span>
                </div>

                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search sales...">
                    <button type="button" id="searchButton">Search</button>
                </div>

                <a href="export_sales.php<?php echo $search ? "?search=$search" : ""; ?>" class="export-btn">
                    <i class="fas fa-download me-2"></i>Export to Excel
                </a>

                <?php if (empty($sales)): ?>
                    <div class="no-records">
                        <i class="fas fa-info-circle me-2"></i>No sales records found.
                    </div>
                <?php else: ?>
                    <table class="sales-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sales as $sale): ?>
                                <tr>
                                    <td><?php echo date('m-d-Y', strtotime($sale['sale_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($sale['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($sale['quantity']); ?></td>
                                    <td>₱<?php echo number_format($sale['total_amount'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?><?php echo $search ? "&search=$search" : ""; ?>" class="<?php echo $page == $i ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('searchButton').addEventListener('click', function() {
            const searchTerm = document.getElementById('searchInput').value;
            window.location.href = '?search=' + encodeURIComponent(searchTerm);
        });

        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('searchButton').click();
            }
        });
    </script>
</body>
</html>
