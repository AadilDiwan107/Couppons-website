<?php
$websiteName = "Coupon.is-great.org";
session_start();

// Check Login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'includes/db.php';

$user_id = $_SESSION['user_id'];
$success_message = "";
$errors = [];

// Handle Profile Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $new_username = trim($_POST['username']);
    $new_email = trim($_POST['email']);
    $new_mobile = trim($_POST['mobile']);
    
    // Validation
    if (empty($new_username) || strlen($new_username) < 3) {
        $errors[] = "Username must be at least 3 characters long";
    }
    if (empty($new_email) || !filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }
    if (empty($new_mobile) || !preg_match('/^[0-9]{10,15}$/', $new_mobile)) {
        $errors[] = "Please enter a valid mobile number (10-15 digits)";
    }
    
    // Check Uniqueness (Username)
    if (empty($errors)) {
        $stmt = $connection->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->bind_param("si", $new_username, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) $errors[] = "Username already exists";
        $stmt->close();
    }

    // Check Uniqueness (Email)
    if (empty($errors)) {
        $stmt = $connection->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $new_email, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) $errors[] = "Email already exists";
        $stmt->close();
    }
    
    // Update Database
    if (empty($errors)) {
        $stmt = $connection->prepare("UPDATE users SET username = ?, email = ?, mobile = ? WHERE id = ?");
        $stmt->bind_param("sssi", $new_username, $new_email, $new_mobile, $user_id);
        
        if ($stmt->execute()) {
            $success_message = "Profile updated successfully!";
            $_SESSION['username'] = $new_username; // Update session
        } else {
            $errors[] = "Failed to update profile. Please try again.";
        }
        $stmt->close();
    }
}

// Fetch Current Data (Always fetch fresh data)
$stmt = $connection->prepare("SELECT username, email, mobile FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile - <?php echo $websiteName; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <style>
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --input-bg: rgba(255, 255, 255, 0.05);
        --input-border: rgba(255, 255, 255, 0.15);
        --card-bg: rgba(20, 25, 47, 0.95);
        --text-primary: #ffffff;
    }

    body {
        background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 50%, #2d1b4e 100%);
        background-attachment: fixed;
        color: var(--text-primary);
        padding-top: 100px;
        min-height: 100vh;
        font-family: 'Segoe UI', sans-serif;
    }

    .edit-container {
        max-width: 700px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .form-card {
        background: var(--card-bg);
        backdrop-filter: blur(12px);
        border-radius: 24px;
        border: 1px solid rgba(102, 126, 234, 0.3);
        padding: 40px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    }

    .profile-avatar {
        width: 100px;
        height: 100px;
        background: var(--primary-gradient);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        margin: 0 auto 20px;
        box-shadow: 0 0 20px rgba(102, 126, 234, 0.5);
    }

    .form-label {
        color: rgba(255, 255, 255, 0.8);
        font-weight: 600;
        margin-bottom: 8px;
    }

    .form-control {
        background: var(--input-bg);
        border: 1px solid var(--input-border);
        color: white;
        padding: 12px 16px;
        border-radius: 12px;
        transition: 0.3s;
    }

    .form-control:focus {
        background: rgba(255, 255, 255, 0.1);
        border-color: #667eea;
        color: white;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15);
    }

    .btn-update {
        background: var(--primary-gradient);
        border: none;
        padding: 14px;
        border-radius: 12px;
        font-weight: 700;
        letter-spacing: 1px;
        width: 100%;
        margin-top: 20px;
        transition: 0.3s;
    }

    .btn-update:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
    }

    .alert {
        border-radius: 12px;
        border: none;
    }
    .alert-success {
        background: rgba(25, 135, 84, 0.2);
        color: #75b798;
        border: 1px solid rgba(25, 135, 84, 0.3);
    }
    .alert-danger {
        background: rgba(220, 53, 69, 0.2);
        color: #ea868f;
        border: 1px solid rgba(220, 53, 69, 0.3);
    }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="edit-container">
    <div class="form-card">
        <div class="text-center mb-4">
            <div class="profile-avatar">
                <i class="bi bi-person"></i>
            </div>
            <h3>Edit Profile</h3>
            <p class="text-secondary">Update your personal information</p>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success d-flex align-items-center">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <div class="d-flex align-items-center mb-1">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label"><i class="bi bi-person me-2"></i>Username</label>
                <input type="text" class="form-control" name="username" 
                       value="<?php echo htmlspecialchars($user['username']); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label"><i class="bi bi-envelope me-2"></i>Email Address</label>
                <input type="email" class="form-control" name="email" 
                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label"><i class="bi bi-phone me-2"></i>Mobile Number</label>
                <input type="tel" class="form-control" name="mobile" 
                       placeholder="e.g. 1234567890"
                       value="<?php echo htmlspecialchars($user['mobile']); ?>" required>
            </div>

            <button type="submit" name="update_profile" class="btn btn-primary btn-update">
                <i class="bi bi-save me-2"></i> Save Changes
            </button>

            <div class="text-center mt-4">
                <a href="index.php" class="text-decoration-none text-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
