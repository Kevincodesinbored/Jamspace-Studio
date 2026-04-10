<?php
require_once 'config/database.php';

// If already logged in, redirect to home
if (isLoggedIn()) {
    redirect('home.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    
    // Validation
    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        $conn = getDBConnection();
        
        $stmt = $conn->prepare("SELECT id, fullname, email, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['fullname'];
                $_SESSION['user_email'] = $user['email'];
                
                redirect('home.php');
            } else {
                $error = "Invalid email or password";
            }
        } else {
            $error = "Invalid email or password";
        }
        
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <title>Login - JamSpace</title>
</head>
<body>
    <div class="form-box">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 class="logo" style="color: var(--secondary);">Jam<span>Space</span></h1>
            <p style="color: var(--text-gray);">Welcome back! Please login to your account.</p>
        </div>
        <?php if ($error): ?> <div class="alert alert-error"><?php echo $error; ?></div> <?php endif; ?>
        <form method="POST">
            <label>Email</label>
            <input type="email" name="email" required placeholder="name@example.com">
            <label>Password</label>
            <div style="position: relative;">
                <input type="password" id="password" name="password" required placeholder="••••••••">
                <button type="button" class="toggle-password" style="position: absolute; right: 10px; top: 15px; background: none; border: none; cursor: pointer; color: #aaa;"><i class="fa-solid fa-eye"></i></button>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px; padding: 15px;">Login</button>
        </form>
        <div style="text-align: center; margin-top: 20px; font-size: 0.9rem;">
            Don't have an account? <a href="register.php" style="color: var(--primary); font-weight: 600; text-decoration: none;">Register</a>
        </div>
    </div>
    <script src="main.js"></script>
</body>
</html>
