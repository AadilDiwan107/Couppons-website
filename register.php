<?php
// Define website name variable
$websiteName = "Coupon.is-great.org";

// Start session
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id']) || isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Include database connection
include 'includes/db.php';

// Handle registration form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $mobile = trim($_POST['mobile']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation flags
    $valid = true;
    $errors = [];
    
    // Validate inputs
    if (empty($username) || strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters long";
        $valid = false;
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
        $valid = false;
    }
    
    if (empty($mobile) || !preg_match('/^[0-9]{10,15}$/', $mobile)) {
        $errors[] = "Please enter a valid mobile number (10-15 digits)";
        $valid = false;
    }
    
    if (empty($password) || strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
        $valid = false;
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
        $valid = false;
    }
    
    // Check if username or email already exists
    if ($valid) {
        $stmt = $connection->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "Username or email already exists";
            $valid = false;
        }
        
        $stmt->close();
    }
    
    // If all validations pass, create the user
    if ($valid) {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $connection->prepare("INSERT INTO users (username, email, mobile, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $email, $mobile, $hashed_password);
        
        if ($stmt->execute()) {
            // Registration successful - log the user in
            $user_id = $stmt->insert_id;
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            
            // Redirect to main page
            header("Location: index.php");
            exit();
        } else {
            $errors[] = "Registration failed. Please try again.";
            $valid = false;
        }
        
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - <?php echo $websiteName; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            overflow: hidden;
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
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
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

        .signup-container {
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
            padding: 40px;
            width: 100%;
            max-width: 450px;
            position: relative;
            z-index: 2;
            animation: slideInUp 0.8s ease-out;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .signup-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .signup-header h2 {
            color: #28a745;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .signup-header p {
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

        .btn-signup {
            background: linear-gradient(45deg, #28a745, #218838);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 10px;
            width: 100%;
            font-weight: bold;
            font-size: 1.1em;
            transition: all 0.3s ease;
        }

        .btn-signup:hover {
            background: linear-gradient(45deg, #218838, #1e7e34);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        }

        .signup-footer {
            text-align: center;
            margin-top: 20px;
            color: #aaa;
        }

        .signup-footer a {
            color: #28a745;
            text-decoration: none;
        }

        .signup-footer a:hover {
            text-decoration: underline;
        }

        .error-message {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        .success-message {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        .logo {
            font-size: 2em;
            color: #28a745;
            margin-bottom: 10px;
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
        
        .password-strength {
            margin-top: 5px;
            font-size: 0.8em;
        }
        
        .password-weak { color: #dc3545; }
        .password-medium { color: #ffc107; }
        .password-strong { color: #28a745; }
    </style>
</head>
<body>
    <!-- Animated background particles -->
    <div class="particles" id="particles"></div>
    
    <div class="signup-container">
        <div class="signup-header">
            <div class="logo">
                <i class="bi bi-tag-fill"></i>
            </div>
            <h2>Create Account</h2>
            <p>Join us to access exclusive coupons</p>
        </div>
        
        <?php if (isset($errors) && !empty($errors)): ?>
            <div class="error-message">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group input-icon">
                <i class="bi bi-person"></i>
                <input type="text" class="form-control" name="username" placeholder="Username" 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
            </div>
            
            <div class="form-group input-icon">
                <i class="bi bi-envelope"></i>
                <input type="email" class="form-control" name="email" placeholder="Email" 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>
            
            <div class="form-group input-icon">
                <i class="bi bi-phone"></i>
                <input type="tel" class="form-control" name="mobile" placeholder="Mobile Number" 
                       value="<?php echo isset($_POST['mobile']) ? htmlspecialchars($_POST['mobile']) : ''; ?>" required>
            </div>
            
            <div class="form-group input-icon">
                <i class="bi bi-lock"></i>
                <input type="password" class="form-control" name="password" id="password" placeholder="Password" required>
                <div class="password-strength" id="password-strength"></div>
            </div>
            
            <div class="form-group input-icon">
                <i class="bi bi-lock"></i>
                <input type="password" class="form-control" name="confirm_password" placeholder="Confirm Password" required>
            </div>
            
            <button type="submit" class="btn btn-signup">
                <i class="bi bi-person-plus me-2"></i>Sign Up
            </button>
        </form>
        
        <div class="signup-footer">
            <p>Already have an account? <a href="login.php">Login</a></p>
            <p><a href="index.php">Back to Home</a></p>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Create floating particles
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 50;
            
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
                const delay = Math.random() * 5;
                particle.style.animationDuration = `${duration}s`;
                particle.style.animationDelay = `${delay}s`;
                
                particlesContainer.appendChild(particle);
            }
        }
        
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
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
        
        // Initialize particles when page loads
        window.addEventListener('load', createParticles);
    </script>
</body>
</html>
