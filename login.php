<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include_once '../includes/database.php';

// Admin credentials are now fetched from the 'admin' table
// $admin_user = 'admin';
// $admin_pass = '123456'; 

$error = '';
$errorType = '';
$success = '';

// Check for registration success message
if (isset($_SESSION['register_success'])) {
    $success = $_SESSION['register_success'];
    $errorType = 'success';
    // Clear the session message
    unset($_SESSION['register_success']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']); // This can be admin username, mechanic mech_username, or user username
    $password = $_POST['password'];

    // Input validation
    if (empty($username) || empty($password)) {
        $error = "Username and password are required.";
        $errorType = 'error';
    } else {
        // 1. Check for Admin
        $stmt_admin = $conn->prepare("SELECT id, username, password FROM admin WHERE username = ?");
        if ($stmt_admin) {
            $stmt_admin->bind_param("s", $username);
            $stmt_admin->execute();
            $result_admin = $stmt_admin->get_result();

            if ($result_admin->num_rows == 1) {
                $admin_data = $result_admin->fetch_assoc();
                if (password_verify($password, $admin_data['password'])) {
                    $_SESSION['user_id'] = $admin_data['id'];
                    $_SESSION['username'] = $admin_data['username'];
                    $_SESSION['role'] = 'admin';
                    header("Location: ../frontend/admin_page/admin_dashboard.php");
                    exit();
                }
            }
            $stmt_admin->close();
        }

        // 2. Check for Mechanic
        $stmt_mech = $conn->prepare("SELECT id, mech_username, password FROM mechanics WHERE mech_username = ?");
        if ($stmt_mech) {
            $stmt_mech->bind_param("s", $username);
            $stmt_mech->execute();
            $result_mech = $stmt_mech->get_result();

            if ($result_mech->num_rows == 1) {
                $mechanic = $result_mech->fetch_assoc();
                if (password_verify($password, $mechanic['password'])) {
                    $_SESSION['user_id'] = $mechanic['id'];
                    $_SESSION['username'] = $mechanic['mech_username'];
                    $_SESSION['role'] = 'mechanic';
                    header("Location: ../frontend/mechanic_page/mech_dashboard.php");
                    exit();
                }
            }
            $stmt_mech->close();
        }

        // 3. Check for User (Customer)
        $stmt_user = $conn->prepare("SELECT user_id, cus_username, password, firstname FROM users WHERE cus_username = ?");
        if ($stmt_user) {
            $stmt_user->bind_param("s", $username);
            $stmt_user->execute();
            $result_user = $stmt_user->get_result();

            if ($result_user->num_rows == 1) {
                $user = $result_user->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    $_SESSION['customer_id'] = $user['user_id'];
                    $_SESSION['customer_name'] = $user['firstname'];
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['cus_username'];
                    $_SESSION['firstname'] = $user['firstname'];
                    $_SESSION['role'] = 'user';
                    header("Location: ../frontend/customer_page/cus_dashboard.php");
                    exit();
                }
            }
            $stmt_user->close();
        }
        
        // If we get here, login failed
        $error = "Invalid username or password.";
        $errorType = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="../css/login.css">
</head>
<style>
    .login-panel a.back-btn {
    background: #fff !important;
    color: #2F97C1 !important;
    border: 2px solid #2F97C1 !important;
    border-radius: 24px !important;
    font-size: 1.1rem !important;
    font-weight: 600 !important;
    text-align: center !important;
    display: block !important;
    width: 100% !important;
    margin: 8px 0 0 0 !important;
    padding: 12px 0 !important;
    text-decoration: none !important;
    transition: background 0.3s, color 0.3s, border-color 0.3s !important;
    cursor: pointer !important;
}

.login-panel a.back-btn:hover {
    background: rgb(255, 255, 255) !important;
    color: #333 !important;
    border-color: #1769aa !important;
}

/* Page transition animation */
.page-transition {
    opacity: 1;
    transition: opacity 0.5s ease-in-out;
}

.page-transition.fade-out {
    opacity: 0;
}
</style>
<body>
    <div class="container page-transition">
        <div class="login-panel">
            <h1>Login to Your Account</h1>
            <p>Login using social networks</p>
            <div class="social-login">
                <button class="social-btn" title="Login with Facebook"><i class="fab fa-facebook-f"></i></button>
                <button class="social-btn" title="Login with Google"><i class="fab fa-google-plus-g"></i></button>
                <button class="social-btn" title="Login with LinkedIn"><i class="fab fa-linkedin-in"></i></button>
            </div>
            <div class="divider">OR</div>
            <?php if (!empty($error)): ?>
                <div class="message <?php echo $errorType; ?>">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($success)): ?>
                <div class="message success">
                    <span class="success-icon">&#10003;</span>
                    <strong>Registration successful!</strong><br>
                    You can now log in with your username and password.
                </div>
            <?php endif; ?>
            <form class="login-form" action="login.php" method="POST">
                <input type="text" name="username" placeholder="Username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                <input type="password" name="password" placeholder="Password">
                <button type="submit">Sign In</button>
            </form>
            <a href="../start_up_page/start_up.php" class="back-btn">Back</a>
        </div>
        <div class="right-panel">
            <img src="../images/johntech.jpg" alt="Logo" class="logo-img">
            <h2>New Here?</h2>
            <p>Sign up and discover the best motorcycle repair services</p>
            <a href="register.php"><button class="signup-btn">Sign Up</button></a>
        </div>
    </div>
    <!-- Font Awesome for social icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script>
        document.querySelector('.back-btn').addEventListener('click', function(e) {
            e.preventDefault();
            const container = document.querySelector('.container');
            container.classList.add('fade-out');
            
            setTimeout(() => {
                window.location.href = this.href;
            }, 500);
        });
    </script>
</body>
</html>
