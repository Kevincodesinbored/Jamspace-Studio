<?php
require_once 'config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = sanitize($_POST['fullname']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    $gender = isset($_POST['gender']) ? sanitize($_POST['gender']) : null;
    $dob = isset($_POST['dob']) && !empty($_POST['dob']) ? $_POST['dob'] : null;
    
    // Validation
    if (strlen($fullname) < 3) {
        $error = "Name must be at least 3 characters";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match";
    } else {
        $conn = getDBConnection();
        
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Email already registered";
        } else {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $stmt = $conn->prepare("INSERT INTO users (fullname, email, password, gender, dob) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $fullname, $email, $hashedPassword, $gender, $dob);
            
            if ($stmt->execute()) {
                $success = "Registration successful! Redirecting to login...";
                header("refresh:2;url=login.php");
            } else {
                $error = "Registration failed. Please try again.";
            }
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - JamSpace</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        /* Styling tambahan khusus untuk Radio Button Gender */
        .gender-group {
            display: flex;
            gap: 20px;
            margin: 10px 0 20px 0;
            align-items: center;
        }
        .gender-option {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 0.95rem;
            color: var(--text-dark);
        }
        .gender-option input {
            width: auto;
            margin: 0;
        }
        .password-field {
            position: relative;
            display: flex;
            align-items: center;
        }
        .password-field input {
            margin: 10px 0 20px 0;
        }
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 22px; /* Menyesuaikan dengan margin input */
            background: none;
            border: none;
            cursor: pointer;
            color: #aaa;
        }
    </style>
</head>
<body>

    <div class="form-box">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 class="logo" style="color: var(--secondary);">Jam<span style="color: var(--primary);">Space</span></h1>
            <p style="color: var(--text-gray);">Create your account and start jamming!</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="registerForm">
            <label>Full Name</label>
            <input type="text" id="fullname" name="fullname" required placeholder="Enter your full name" 
                   value="<?php echo isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : ''; ?>">
            
            <label>Email Address</label>
            <input type="email" id="email" name="email" required placeholder="name@example.com" 
                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            
            <label>Password</label>
            <div class="password-field">
                <input type="password" id="password" name="password" required placeholder="Create a password">
                <button type="button" class="toggle-password"><i class="fa-solid fa-eye"></i></button>
            </div>

            <label>Confirm Password</label>
            <div class="password-field">
                <input type="password" id="confirmPassword" name="confirmPassword" required placeholder="Confirm your password">
                <button type="button" class="toggle-password"><i class="fa-solid fa-eye"></i></button>
            </div>

            <label>Gender</label>
            <div class="gender-group">
                <label class="gender-option">
                    <input type="radio" name="gender" value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Male') ? 'checked' : ''; ?>> Male
                </label>
                <label class="gender-option">
                    <input type="radio" name="gender" value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Female') ? 'checked' : ''; ?>> Female
                </label>
            </div>

            <label>Date of Birth</label>
            <input type="date" id="dob" name="dob" value="<?php echo isset($_POST['dob']) ? htmlspecialchars($_POST['dob']) : ''; ?>">

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px; padding: 15px; font-size: 1rem;">
                Sign Up Now <i class="fa-solid fa-user-plus"></i>
            </button>
        </form>
        
        <div style="text-align: center; margin-top: 25px; font-size: 0.9rem; color: var(--text-gray);">
            Already have an account? <a href="login.php" style="color: var(--primary); font-weight: 600; text-decoration: none;">Login here</a>
        </div>
    </div>

    <script src="main.js"></script>
</body>
</html>
