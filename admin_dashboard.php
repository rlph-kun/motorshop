<?php
session_start(); // Start the session at the very beginning

// Ensure the user is logged in and is an admin.
// The sidebar also does this, but it's good practice for main pages too.
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../backend/login.php"); // Adjust path to your login page
    exit();
}

$admin_username = htmlspecialchars($_SESSION['username']);

include_once '../../includes/database.php'; // Add DB connection for stats

// Fetch stats
$mechanics_count = $inventory_count = $appointments_count = 0;

// Mechanics count
$result = $conn->query("SELECT COUNT(*) as cnt FROM mechanics");
if ($result) { $mechanics_count = $result->fetch_assoc()['cnt']; }
// Inventory count
$result = $conn->query("SELECT COUNT(*) as cnt FROM inventory");
if ($result) { $inventory_count = $result->fetch_assoc()['cnt']; }
// Appointments count
$result = $conn->query("SELECT COUNT(*) as cnt FROM appointments");
if ($result) { $appointments_count = $result->fetch_assoc()['cnt']; }

// Recent mechanics
$recent_mechanics = [];
$result = $conn->query("SELECT firstname, lastname, mech_username, registration_date FROM mechanics ORDER BY registration_date DESC LIMIT 5");
if ($result) { while ($row = $result->fetch_assoc()) { $recent_mechanics[] = $row; } }
// Recent inventory
$recent_inventory = [];
$result = $conn->query("SELECT product_name, stocks, price, last_updated FROM inventory ORDER BY last_updated DESC LIMIT 5");
if ($result) { while ($row = $result->fetch_assoc()) { $recent_inventory[] = $row; } }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Motorshop</title>
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
        .dashboard-header {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            padding: 2rem 2rem 1.5rem 2rem;
            margin-bottom: 2rem;
        }
        .dashboard-header h1 {
            font-weight: 700;
            color: #28a745;
            margin: 0;
            font-size: 2.2rem;
        }
        .dashboard-cards {
            display: flex;
            gap: 32px;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        .dashboard-card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            padding: 2rem 1.5rem;
            min-width: 220px;
            flex: 1 1 220px;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }
        .dashboard-card .icon {
            font-size: 2.5rem;
            color: #28a745;
            margin-bottom: 0.5rem;
        }
        .dashboard-card h2 {
            margin: 0 0 8px 0;
            font-size: 2.2rem;
            color: #222;
            font-weight: 700;
        }
        .dashboard-card p {
            margin: 0;
            color: #495057;
            font-size: 1.1rem;
        }
        .content-area {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .content-area h2 {
            color: #28a745;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .recent-section {
            margin-top: 32px;
        }
        .recent-section h3 {
            margin-bottom: 12px;
            color: #218838;
            font-weight: 600;
        }
        .recent-list {
            list-style: none;
            padding: 0;
            margin: 0 0 16px 0;
        }
        .recent-list li {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            font-size: 1.05rem;
        }
        .recent-list li:last-child { border-bottom: none; }
        @media (max-width: 991px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }
            .dashboard-cards { flex-direction: column; gap: 18px; }
        }
        @media (max-width: 600px) {
            .dashboard-header, .content-area {
                padding: 1.2rem;
            }
            .dashboard-cards { gap: 12px; }
        }
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-content w-100">
        <div class="dashboard-header">
            <h1><i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard</h1>
        </div>
        <div class="dashboard-cards">
            <div class="dashboard-card">
                <span class="icon"><i class="fas fa-user-cog"></i></span>
                <h2><?php echo $mechanics_count; ?></h2>
                <p>Mechanics</p>
            </div>
            <div class="dashboard-card">
                <span class="icon"><i class="fas fa-boxes"></i></span>
                <h2><?php echo $inventory_count; ?></h2>
                <p>Inventory Items</p>
            </div>
            <div class="dashboard-card">
                <span class="icon"><i class="fas fa-calendar-check"></i></span>
                <h2><?php echo $appointments_count; ?></h2>
                <p>Appointments</p>
            </div>
        </div>
        <div class="content-area">
            <h2>Welcome, <?php echo $admin_username; ?>!</h2>
            <p>This is your central hub for managing the Motorshop operations.</p>
            <div class="recent-section">
                <h3><i class="fas fa-users me-2"></i>Recent Mechanics</h3>
                <ul class="recent-list">
                    <?php if (count($recent_mechanics)): foreach ($recent_mechanics as $mech): ?>
                        <li>
                            <?php echo htmlspecialchars($mech['firstname'] . ' ' . $mech['lastname']); ?>
                            <small style="color:#888;"> (<?php echo htmlspecialchars($mech['mech_username']); ?>, <?php echo htmlspecialchars($mech['registration_date']); ?>)</small>
                        </li>
                    <?php endforeach; else: ?>
                        <li>No recent mechanics.</li>
                    <?php endif; ?>
                </ul>
                <h3><i class="fas fa-boxes me-2"></i>Recent Inventory Items</h3>
                <ul class="recent-list">
                    <?php if (count($recent_inventory)): foreach ($recent_inventory as $item): ?>
                        <li>
                            <?php echo htmlspecialchars($item['product_name']); ?>
                            <small style="color:#888;"> (Stocks: <?php echo htmlspecialchars($item['stocks']); ?>, ₱<?php echo number_format($item['price'],2); ?>, <?php echo htmlspecialchars($item['last_updated']); ?>)</small>
                        </li>
                    <?php endforeach; else: ?>
                        <li>No recent inventory items.</li>
                    <?php endif; ?>
                </ul>
            </div>
            <p style="margin-top:32px;">Select an option from the sidebar to get started.</p>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
