<?php
// session_start(); // Already started in including page typically

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../backend/login.php"); // Adjust path as needed
    exit();
}
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar bg-dark text-white" style="min-height: 100vh; width: 300px; position: fixed; left: 0; top: 0;">
    <div class="p-3">
        <div class="text-center mb-4">
            <div class="logo-container mb-3">
                <img src="../../images/johntech.jpg" alt="Motorshop Logo" class="rounded-circle" style="width: 100px; height: 100px; object-fit: cover;">
            </div>
            <h4 class="text-white mb-1">Admin Panel</h4>
            <small class="text-white-50">Motorshop Management</small>
            <div class="mt-2">
                <span class="badge bg-primary">Admin Portal</span>
            </div>
        </div>
        <div class="user-profile mb-4">
            <div class="d-flex align-items-center p-2 bg-dark-light rounded">
                <i class="fas fa-user-shield fa-2x me-2 text-primary"></i>
                <div>
                    <h6 class="mb-0"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></h6>
                    <small class="text-white-50">Administrator</small>
                </div>
            </div>
        </div>
        <hr class="bg-light opacity-25">
        <ul class="nav flex-column">
            <li class="nav-item mb-2">
                <a href="admin_dashboard.php" class="nav-link text-white <?php echo $current_page == 'admin_dashboard.php' ? 'active bg-primary' : ''; ?>">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="add_mechanic.php" class="nav-link text-white <?php echo $current_page == 'add_mechanic.php' ? 'active bg-primary' : ''; ?>">
                    <i class="fas fa-user-plus me-2"></i>
                    Add Mechanic
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="inventory.php" class="nav-link text-white <?php echo $current_page == 'inventory.php' ? 'active bg-primary' : ''; ?>">
                    <i class="fas fa-boxes me-2"></i>
                    Manage Inventory
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="manage_appointments.php" class="nav-link text-white <?php echo $current_page == 'manage_appointments.php' ? 'active bg-primary' : ''; ?>">
                    <i class="fas fa-calendar-check me-2"></i>
                    Manage Appointments
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="record_sale.php" class="nav-link text-white <?php echo $current_page == 'record_sale.php' ? 'active bg-primary' : ''; ?>">
                    <i class="fas fa-cash-register me-2"></i>
                    Record Sale
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="sales_history.php" class="nav-link text-white <?php echo $current_page == 'sales_history.php' ? 'active bg-primary' : ''; ?>">
                    <i class="fas fa-history me-2"></i>
                    Sale History
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="send_notification.php" class="nav-link text-white <?php echo $current_page == 'send_notification.php' ? 'active bg-primary' : ''; ?>">
                    <i class="fas fa-bell me-2"></i>
                    Send Notification
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="admin_settings.php" class="nav-link text-white <?php echo $current_page == 'admin_settings.php' ? 'active bg-primary' : ''; ?>">
                    <i class="fas fa-cog me-2"></i>
                    Settings
                </a>
            </li>
        </ul>
        <hr class="bg-light opacity-25">
        <div class="mt-4">
            <a href="../../backend/logout.php" class="btn btn-danger w-100">
                <i class="fas fa-sign-out-alt me-2"></i>
                Logout
            </a>
        </div>
    </div>
</div>

<style>
.sidebar {
    box-shadow: 2px 0 5px rgba(0,0,0,0.1);
    background: linear-gradient(to bottom, #1a1a1a, #2d2d2d);
}
.sidebar .nav-link {
    border-radius: 5px;
    transition: all 0.3s;
    padding: 0.8rem 1rem;
}
.sidebar .nav-link:hover {
    background-color: rgba(255,255,255,0.1);
    transform: translateX(5px);
}
.sidebar .nav-link.active {
    background-color: #0d6efd;
}
.bg-dark-light {
    background-color: rgba(255,255,255,0.05);
}
.logo-container {
    padding: 1rem;
    background: rgba(255,255,255,0.05);
    border-radius: 50%;
    width: 120px;
    height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-left: auto;
    margin-right: auto;
}
body {
    padding-left: 300px;
}
@media (max-width: 768px) {
    .sidebar {
        width: 100%;
        position: relative;
        min-height: auto;
    }
    body {
        padding-left: 0;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const currentPage = '<?php echo $current_page; ?>';
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        if (link.getAttribute('href') === currentPage) {
            link.classList.add('active');
        }
    });
    // Add logout animation
    const logoutBtn = document.querySelector('.btn-danger');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            document.body.classList.add('logout-animation');
            setTimeout(() => {
                window.location.href = this.href;
            }, 500);
        });
    }
});
</script>
