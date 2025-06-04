<?php
session_start();
include_once '../includes/database.php';

$message = '';
$messageType = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $username = isset($_POST['cus_username']) ? trim($_POST['cus_username']) : '';
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Enhanced validation
    if (empty($firstname) || empty($lastname) || empty($email) || empty($username) || empty($password) || empty($confirm_password)) {
        $message = "All fields are required.";
        $messageType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
        $messageType = 'error';
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters long.";
        $messageType = 'error';
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
        $messageType = 'error';
    } elseif (strlen($username) < 3) {
        $message = "Username must be at least 3 characters long.";
        $messageType = 'error';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $message = "Username can only contain letters, numbers, and underscores.";
        $messageType = 'error';
    } else {
        try {
            // Check if username or email already exists
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE cus_username = ? OR email = ?");
            if ($stmt) {
                $stmt->bind_param("ss", $username, $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    // Check which one is duplicate
                    $stmt_check = $conn->prepare("SELECT cus_username, email FROM users WHERE cus_username = ? OR email = ?");
                    $stmt_check->bind_param("ss", $username, $email);
                    $stmt_check->execute();
                    $check_result = $stmt_check->get_result();
                    $duplicate = $check_result->fetch_assoc();
                    
                    if ($duplicate['cus_username'] === $username) {
                        $message = "Username already taken.";
                    } else {
                        $message = "Email already registered.";
                    }
                    $messageType = 'error';
                    $stmt_check->close();
                } else {
                    // Hash the password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    // Insert new user
                    $insert_stmt = $conn->prepare("INSERT INTO users (firstname, lastname, email, cus_username, password) VALUES (?, ?, ?, ?, ?)");
                    if ($insert_stmt) {
                        $insert_stmt->bind_param("sssss", $firstname, $lastname, $email, $username, $hashed_password);
                        if ($insert_stmt->execute()) {
                            // Set success message in session
                            $_SESSION['register_success'] = "Registration successful! Please login with your credentials.";
                            // Redirect to login page
                            header("Location: login.php");
                            exit();
                        } else {
                            $message = "Error during registration. Please try again.";
                            $messageType = 'error';
                        }
                        $insert_stmt->close();
                    } else {
                        $message = "Database error. Please try again later.";
                        $messageType = 'error';
                    }
                }
                $stmt->close();
            } else {
                $message = "Database error. Please try again later.";
                $messageType = 'error';
            }
        } catch (Exception $e) {
            $message = "An error occurred. Please try again later.";
            $messageType = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="../css/register.css">
</head>
<body>
    <div class="register-container">
        <div class="left-panel">
        <img src="../images/johntech.jpg" alt="Logo" class="logo-img">
            <h2>Welcome Back!</h2>
            <p>To keep connected with us please login with your personal info</p>
            <a href="login.php"><button class="signin-btn">SIGN IN</button></a>
        </div>
        <div class="right-panel">
            <h2>Create Account</h2>
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            <form action="register.php" method="POST">
                <div class="form-group">
                    <label for="firstname">First Name:</label>
                    <input type="text" id="firstname" name="firstname" value="<?php echo isset($_POST['firstname']) ? htmlspecialchars($_POST['firstname']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="lastname">Last Name:</label>
                    <input type="text" id="lastname" name="lastname" value="<?php echo isset($_POST['lastname']) ? htmlspecialchars($_POST['lastname']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="cus_username">Username:</label>
                    <input type="text" id="cus_username" name="cus_username" value="<?php echo isset($_POST['cus_username']) ? htmlspecialchars($_POST['cus_username']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="password">Password (min. 6 characters):</label>
                    <input type="password" id="password" name="password">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password">
                </div>
                <button type="submit">Register</button>
            </form>
        </div>
    </div>
</body>
</html>
