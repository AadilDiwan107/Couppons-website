<?php
// Define website name variable
$websiteName = "Coupon.is-great.org";

// Start session
session_start();

// Include database connection
include 'includes/db.php';

// Initialize variables
$email = '';
$errors = [];
$success_message = '';
$show_reset_form = false;
$user_id = null;

// Handle email submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify_email'])) {
    $email = trim($_POST['email']);
    
    // Validation
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }
    
    // If no errors, check if email exists in database
    if (empty($errors)) {
        $stmt = $connection->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $user_id = $user['id'];
            $show_reset_form = true;
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_user_id'] = $user_id;
        } else {
            $errors[] = "User not found with this email address";
        }
        
        $stmt->close();
    }
}

// Handle password reset
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_password'])) {
    // Check if user has verified their email
    if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_user_id'])) {
        $errors[] = "Invalid reset request. Please start over.";
        $show_reset_form = false;
    } else {
        $user_id = $_SESSION['reset_user_id'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validation
        if (empty($new_password)) {
            $errors[] = "New password is required";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "Password must be at least 6 characters long";
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = "Passwords do not match";
        }
        
        // If no errors, update the password
        if (empty($errors)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $connection->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt->execute()) {
                $success_message = "Password updated successfully!";
                // Clear session variables
                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_user_id']);
                // Redirect to login page after 3 seconds
                header("refresh:3;url=login.php");
            } else {
                $errors[] = "Failed to update password. Please try again.";
            }
            
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?php echo $websiteName; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            padding-top: 80px; /* Account for fixed navbar */
            background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            color: white;
            min-height: 100vh;
            overflow-x: hidden;
        }

        @keyframes gradientBG {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }

        /* Floating particles animation */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 20%;
            animation: float 6s infinite linear;
        }

        @keyframes float {
            0% {
                transform: translateY(0) translateX(0) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 0.5;
            }
            90% {
                opacity: 0.5;
            }
            100% {
                transform: translateY(-100vh) translateX(100px) rotate(360deg);
                opacity: 0;
            }
        }

        .forgot-password-container {
            max-width: 500px;
            margin: 40px auto;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
            padding: 40px;
            position: relative;
            z-index: 2;
        }

        .forgot-password-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .forgot-password-header h1 {
            color: #28a745;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .forgot-password-header p {
            color: #aaa;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 12px 15px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: #28a745;
            box-shadow: 0 0 0 0.25rem rgba(40, 167, 69, 0.25);
            color: white;
        }

        .form-control::placeholder {
            color: #aaa;
        }

        .btn-primary {
            background: linear-gradient(45deg, #28a745, #218838);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: bold;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-primary:hover {
            background: linear-gradient(45deg, #218838, #1e7e34);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        }

        .alert {
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        .alert-danger {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        .input-icon {
            position: relative;
        }

        .input-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
        }

        .input-icon input {
            padding-left: 45px;
        }

        .required {
            color: #dc3545;
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
        }

        .form-label i {
            margin-right: 5px;
        }

        .back-to-login {
            text-align: center;
            margin-top: 20px;
        }

        .back-to-login a {
            color: #28a745;
            text-decoration: none;
            font-weight: 500;
        }

        .back-to-login a:hover {
            text-decoration: underline;
        }

        .password-strength {
            margin-top: 5px;
            font-size: 0.8em;
        }

        .password-weak {
            color: #dc3545;
        }

        .password-medium {
            color: #ffc107;
        }

        .password-strong {
            color: #28a745;
        }
    </style>
</head>
<body>
    <!-- Animated background particles -->
    <div class="particles" id="particles"></div>
    
    <!-- Include the navbar -->
    <?php include 'includes/navbar.php'; ?>
    
    <!-- Main Content -->
    <div class="forgot-password-container">
        <div class="forgot-password-header">
            <h1><i class="bi bi-key me-2"></i>Forgot Password</h1>
            <p>Reset your account password</p>
        </div>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($show_reset_form): ?>
            <!-- Password Reset Form -->
            <form method="POST" action="">
                <input type="hidden" name="reset_password" value="1">
                
                <div class="form-group input-icon">
                    <label class="form-label"><i class="bi bi-envelope"></i> Email Address</label>
                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($_SESSION['reset_email']); ?>" disabled>
                </div>
                
                <div class="form-group input-icon">
                    <label class="form-label"><i class="bi bi-lock"></i> New Password <span class="required">*</span></label>
                    <input type="password" class="form-control" name="new_password" id="new_password" placeholder="Enter new password" required>
                    <div class="password-strength" id="password-strength"></div>
                </div>
                
                <div class="form-group input-icon">
                    <label class="form-label"><i class="bi bi-lock"></i> Confirm New Password <span class="required">*</span></label>
                    <input type="password" class="form-control" name="confirm_password" placeholder="Confirm new password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Reset Password
                </button>
            </form>
        <?php else: ?>
            <!-- Email Verification Form -->
            <form method="POST" action="">
                <input type="hidden" name="verify_email" value="1">
                
                <div class="form-group input-icon">
                    <label class="form-label"><i class="bi bi-envelope"></i> Email Address <span class="required">*</span></label>
                    <input type="email" class="form-control" name="email" placeholder="Enter your email address" 
                           value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-send me-2"></i>Send Reset Link
                </button>
            </form>
        <?php endif; ?>
        
        <div class="back-to-login">
            <a href="login.php"><i class="bi bi-arrow-left me-2"></i>Back to Login</a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Create floating particles
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 100; // Increased particle count for more visual effect
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                
                // Random size
                const size = Math.random() * 8 + 2;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                
                // Random position
                particle.style.left = `${Math.random() * 100}%`;
                particle.style.top = `${Math.random() * 100}%`;
                
                // Random animation duration and delay
                const duration = Math.random() * 12 + 6;
                const delay = Math.random() * 4.5;
                particle.style.animationDuration = `${duration}s`;
                particle.style.animationDelay = `${delay}s`;
                
                particlesContainer.appendChild(particle);
            }
        }
        
        // Initialize particles when page loads
        window.addEventListener('load', createParticles);
        
        // Password strength indicator
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const strengthElement = document.getElementById('password-strength');
            
            if (password.length === 0) {
                strengthElement.textContent = '';
                return;
            }
            
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            let strengthText = '';
            let strengthClass = '';
            
            if (strength < 2) {
                strengthText = 'Weak';
                strengthClass = 'password-weak';
            } else if (strength < 4) {
                strengthText = 'Medium';
                strengthClass = 'password-medium';
            } else {
                strengthText = 'Strong';
                strengthClass = 'password-strong';
            }
            
            strengthElement.textContent = `Password strength: ${strengthText}`;
            strengthElement.className = `password-strength ${strengthClass}`;
        });
    </script>
</body>
</html>
