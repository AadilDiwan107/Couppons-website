<?php
// Define website name variable
$websiteName = "Coupon.is-great.org";

// Start session to check login status
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
include 'includes/db.php';

// Get user information
$user_id = $_SESSION['user_id'];
$stmt = $connection->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Handle coupon submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_coupon'])) {
    $description = trim($_POST['description']);
    $company_name = trim($_POST['company_name']);
    $link = trim($_POST['link']);
    $code = trim($_POST['code']);
    $terms = trim($_POST['terms']);
    $expire_date = $_POST['expire_date'];
    
    // Validation
    $errors = [];
    
    if (empty($description)) {
        $errors[] = "Description is required";
    }
    
    if (empty($company_name)) {
        $errors[] = "Company name is required";
    }
    
    if (!filter_var($link, FILTER_VALIDATE_URL)) {
        $errors[] = "Please enter a valid URL";
    }
    
    if (empty($code)) {
        $errors[] = "Coupon code is required";
    }
    
    if (empty($expire_date)) {
        $errors[] = "Expiration date is required";
    } elseif (strtotime($expire_date) < strtotime(date('Y-m-d'))) {
        $errors[] = "Expiration date cannot be in the past";
    }
    
    // If no errors, insert the coupon
    if (empty($errors)) {
        $stmt = $connection->prepare("INSERT INTO coupons (user_id, description, company_name, link, code, terms, expiration_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssss", $user_id, $description, $company_name, $link, $code, $terms, $expire_date);
        
        if ($stmt->execute()) {
            // Redirect to index.php after successful addition
            header("Location: index.php");
            exit();
        } else {
            $errors[] = "Failed to add coupon. Please try again.";
        }
        
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Add Coupon - <?php echo $websiteName; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
:root {
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --dark-bg: #0a0e27;
    --card-bg: rgba(20, 25, 47, 0.95);
    --card-border: rgba(102, 126, 234, 0.3);
    --text-primary: #ffffff;
    --text-secondary: rgba(255, 255, 255, 0.8);
    --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.1);
    --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.2);
    --shadow-lg: 0 8px 32px rgba(0, 0, 0, 0.3);
    --shadow-glow: 0 0 30px rgba(102, 126, 234, 0.4);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 50%, #2d1b4e 100%);
    background-attachment: fixed;
    color: var(--text-primary);
    padding-top: 100px;
    min-height: 100vh;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    line-height: 1.6;
}

.container {
    max-width: 1200px;
    padding: 0 20px;
}

.add-coupon-container {
    max-width: 800px;
    margin: 40px auto;
    background: var(--card-bg);
    backdrop-filter: blur(10px);
    border-radius: 24px;
    padding: 40px;
    position: relative;
    border: 1px solid var(--card-border);
    box-shadow: var(--shadow-lg);
    overflow: hidden;
}

.add-coupon-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--primary-gradient);
    opacity: 1;
}

.add-coupon-header {
    text-align: center;
    margin-bottom: 40px;
}

.add-coupon-header h1 {
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 12px;
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
}

.add-coupon-header p {
    color: var(--text-secondary);
    font-size: 1.1rem;
}

.form-group {
    margin-bottom: 24px;
}

.form-label {
    font-weight: 700;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--text-primary);
    font-size: 1rem;
}

.form-label i {
    color: #667eea;
    font-size: 1.1rem;
}

.form-control {
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.15);
    color: var(--text-primary);
    padding: 14px 18px;
    border-radius: 12px;
    transition: all 0.3s ease;
    font-size: 1rem;
    width: 100%;
}

.form-control:focus {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(102, 126, 234, 0.7);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
    color: var(--text-primary);
    outline: none;
}

.form-control::placeholder {
    color: rgba(255, 255, 255, 0.5);
}

.form-control:disabled {
    background: rgba(255, 255, 255, 0.04);
    color: var(--text-secondary);
    cursor: not-allowed;
}

textarea.form-control {
    min-height: 140px;
    resize: vertical;
    font-family: inherit;
}

.input-icon {
    position: relative;
}

.input-icon i {
    position: absolute;
    left: 18px;
    top: 50%;
    transform: translateY(-50%);
    color: rgba(102, 126, 234, 0.7);
    z-index: 1;
    pointer-events: none;
}

.input-icon input,
.input-icon textarea {
    padding-left: 50px;
}

.input-icon textarea + i {
    top: 20px;
    transform: none;
}

.btn-primary {
    background: var(--success-gradient);
    border: none;
    padding: 14px 32px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    color: white;
    box-shadow: 0 6px 20px rgba(17, 153, 142, 0.3);
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 28px rgba(17, 153, 142, 0.4);
    color: white;
}

.btn-primary:active {
    transform: translateY(0);
}

.btn-outline-light {
    border: 2px solid rgba(255, 255, 255, 0.3);
    color: var(--text-primary);
    background: transparent;
    padding: 12px 24px;
    border-radius: 12px;
    font-weight: 700;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-outline-light:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.5);
    color: var(--text-primary);
    transform: translateY(-2px);
}

.alert {
    border-radius: 12px;
    margin-bottom: 24px;
    padding: 16px 20px;
    border: none;
    font-weight: 600;
}

.alert-success {
    background: rgba(17, 153, 142, 0.15);
    color: #38ef7d;
    border-left: 4px solid #38ef7d;
}

.alert-danger {
    background: rgba(245, 87, 108, 0.15);
    color: #f5576c;
    border-left: 4px solid #f5576c;
}

.alert-danger div {
    margin-bottom: 8px;
}

.alert-danger div:last-child {
    margin-bottom: 0;
}

.required {
    color: #f5576c;
    font-weight: 800;
    margin-left: 4px;
}

.text-center {
    text-align: center;
}

.mt-3 {
    margin-top: 24px;
}

/* Responsive Design */
@media (max-width: 768px) {
    body {
        padding-top: 80px;
    }
    
    .add-coupon-container {
        margin: 20px;
        padding: 28px;
    }
    
    .add-coupon-header h1 {
        font-size: 2rem;
        flex-direction: column;
        gap: 8px;
    }
    
    .add-coupon-header p {
        font-size: 1rem;
    }
    
    .form-control {
        padding: 12px 16px;
        font-size: 0.95rem;
    }
    
    .btn-primary {
        width: 100%;
        justify-content: center;
        padding: 14px 24px;
    }
    
    .btn-outline-light {
        width: 100%;
        justify-content: center;
    }
    
    .input-icon input,
    .input-icon textarea {
        padding-left: 45px;
    }
}

@media (max-width: 576px) {
    .add-coupon-container {
        padding: 24px;
    }
    
    .add-coupon-header h1 {
        font-size: 1.75rem;
    }
    
    .form-label {
        font-size: 0.95rem;
    }
    
    .form-control {
        padding: 10px 14px;
        font-size: 0.9rem;
    }
    
    textarea.form-control {
        min-height: 120px;
    }
}
</style>
</head>
<body>
    <!-- Include the navbar -->
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container">
        <div class="add-coupon-container">
            <div class="add-coupon-header">
                <h1><i class="bi bi-plus-circle"></i>Add New Coupon</h1>
                <p>Share your deals with the community</p>
            </div>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($errors) && !empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label"><i class="bi bi-person"></i> User ID <span class="required">*</span></label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_id); ?>" disabled>
                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label"><i class="bi bi-person-circle"></i> Username</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                </div>
                
                <div class="form-group input-icon">
                    <label class="form-label"><i class="bi bi-pencil"></i> Description <span class="required">*</span></label>
                    <textarea class="form-control" name="description" placeholder="Describe the coupon offer..." required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>
                
                <div class="form-group input-icon">
                    <label class="form-label"><i class="bi bi-building"></i> Company Name <span class="required">*</span></label>
                    <input type="text" class="form-control" name="company_name" placeholder="Enter company name" 
                           value="<?php echo isset($_POST['company_name']) ? htmlspecialchars($_POST['company_name']) : ''; ?>" required>
                </div>
                
                <div class="form-group input-icon">
                    <label class="form-label"><i class="bi bi-link-45deg"></i> Link <span class="required">*</span></label>
                    <input type="url" class="form-control" name="link" placeholder="https://example.com" 
                           value="<?php echo isset($_POST['link']) ? htmlspecialchars($_POST['link']) : ''; ?>" required>
                </div>
                
                <div class="form-group input-icon">
                    <label class="form-label"><i class="bi bi-tag"></i> Coupon Code <span class="required">*</span></label>
                    <input type="text" class="form-control" name="code" placeholder="Enter coupon code" 
                           value="<?php echo isset($_POST['code']) ? htmlspecialchars($_POST['code']) : ''; ?>" required>
                </div>
                
                <div class="form-group input-icon">
                    <label class="form-label"><i class="bi bi-file-text"></i> Terms & Conditions</label>
                    <textarea class="form-control" name="terms" placeholder="Enter terms and conditions..."><?php echo isset($_POST['terms']) ? htmlspecialchars($_POST['terms']) : ''; ?></textarea>
                </div>
                
                <div class="form-group input-icon">
                    <label class="form-label"><i class="bi bi-calendar"></i> Expiration Date <span class="required">*</span></label>
                    <input type="date" class="form-control" name="expire_date" 
                           value="<?php echo isset($_POST['expire_date']) ? htmlspecialchars($_POST['expire_date']) : ''; ?>" required>
                </div>
                
                <button type="submit" name="add_coupon" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i>Add Coupon
                </button>
            </form>
            
            <div class="text-center mt-3">
                <a href="index.php" class="btn btn-outline-light">
                    <i class="bi bi-arrow-left"></i>Back to Coupons
                </a>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
