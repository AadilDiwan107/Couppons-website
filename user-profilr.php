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

// Get user information including points
$user_id = $_SESSION['user_id'];
$stmt = $connection->prepare("SELECT username, email, mobile, created_at, points FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// If points column doesn't exist, set to 0
$user_points = isset($user['points']) ? $user['points'] : 0;

// Get the number of coupons posted by the user
$stmt = $connection->prepare("SELECT COUNT(*) as coupon_count FROM coupons WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$coupon_result = $stmt->get_result();
$coupon_data = $coupon_result->fetch_assoc();
$coupon_count = $coupon_data['coupon_count'];
$stmt->close();

// Get number of expired coupons for this user
$stmt = $connection->prepare("SELECT COUNT(*) as expired_count FROM coupons WHERE user_id = ? AND c_status = 'expired'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$expired_result = $stmt->get_result();
$expired_data = $expired_result->fetch_assoc();
$expired_count = $expired_data['expired_count'];
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Profile - <?php echo $websiteName; ?></title>
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

.profile-container {
    max-width: 900px;
    margin: 30px auto;
    background: var(--card-bg);
    backdrop-filter: blur(10px);
    border-radius: 24px;
    padding: 40px;
    position: relative;
    border: 1px solid var(--card-border);
    box-shadow: var(--shadow-lg);
    overflow: hidden;
}

.profile-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--primary-gradient);
    opacity: 1;
}

.profile-avatar-top-left {
    position: absolute;
    top: 30px;
    left: 30px;
    width: 100px;
    height: 100px;
    background: var(--primary-gradient);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 3em;
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
    z-index: 3;
    border: 3px solid rgba(102, 126, 234, 0.3);
}

.coupon-actions-row {
    margin-bottom: 30px;
    text-align: right;
    position: relative;
    z-index: 3;
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    flex-wrap: wrap;
}

.coupon-actions-row .btn-primary {
    padding: 12px 20px;
    border-radius: 12px;
    background: var(--primary-gradient);
    border: none;
    color: white;
    font-weight: 700;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    min-width: 100px;
}

.coupon-actions-row .btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.5);
}

.coupon-actions-row .btn-primary i {
    font-size: 1.5em;
}

.profile-header {
    text-align: center;
    margin-bottom: 30px;
    padding-top: 20px;
}

.profile-username {
    font-size: 2rem;
    font-weight: 800;
    margin-bottom: 8px;
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.profile-email {
    color: var(--text-secondary);
    font-size: 1rem;
    margin-bottom: 0;
}

.profile-stats {
    display: flex;
    justify-content: space-around;
    background: rgba(102, 126, 234, 0.1);
    padding: 24px;
    border-radius: 16px;
    margin: 30px 0;
    border: 1px solid rgba(102, 126, 234, 0.2);
}

.stat-item {
    text-align: center;
    flex: 1;
    padding: 10px;
}

.stat-number {
    font-size: 2rem;
    font-weight: 800;
    margin-bottom: 8px;
}

.stat-item:first-child .stat-number {
    background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.stat-item:nth-child(2) .stat-number {
    background: var(--success-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.stat-item:nth-child(3) .stat-number {
    background: var(--warning-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.stat-label {
    font-size: 0.9rem;
    color: var(--text-secondary);
    font-weight: 600;
}

.points-stat {
    background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.profile-section {
    margin-bottom: 30px;
    padding: 24px;
    background: rgba(102, 126, 234, 0.05);
    border-radius: 16px;
    border: 1px solid rgba(102, 126, 234, 0.15);
}

.section-title {
    color: var(--text-primary);
    margin-bottom: 20px;
    font-size: 1.5rem;
    font-weight: 700;
    border-bottom: 2px solid rgba(102, 126, 234, 0.3);
    padding-bottom: 12px;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.info-item:last-child {
    border-bottom: none;
}

.info-label {
    color: var(--text-secondary);
    font-weight: 600;
    font-size: 0.95rem;
}

.info-value {
    color: var(--text-primary);
    font-weight: 700;
    text-align: right;
    font-size: 1rem;
}

.btn-primary {
    background: var(--primary-gradient);
    border: none;
    padding: 12px 24px;
    border-radius: 12px;
    font-weight: 700;
    transition: all 0.3s ease;
    color: white;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
    color: white;
}

.btn-outline-primary {
    border: 2px solid rgba(102, 126, 234, 0.5);
    color: #667eea;
    background: transparent;
    padding: 12px 24px;
    border-radius: 12px;
    font-weight: 700;
    transition: all 0.3s ease;
}

.btn-outline-primary:hover {
    background: rgba(102, 126, 234, 0.15);
    border-color: rgba(102, 126, 234, 0.8);
    color: #7fa2ff;
    transform: translateY(-2px);
}

.profile-actions {
    display: flex;
    gap: 12px;
    margin-top: 20px;
    flex-wrap: wrap;
    justify-content: center;
}

.logout-btn {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    border: none;
    padding: 12px 24px;
    border-radius: 12px;
    font-weight: 700;
    transition: all 0.3s ease;
    color: white;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
}

.logout-btn:hover {
    background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
    transform: translateY(-2px);
    text-decoration: none;
    color: white;
    box-shadow: 0 8px 24px rgba(220, 53, 69, 0.4);
}

.edit-profile-btn {
    background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);
    border: none;
    padding: 12px 24px;
    border-radius: 12px;
    font-weight: 700;
    transition: all 0.3s ease;
    color: white;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 15px rgba(111, 66, 193, 0.3);
}

.edit-profile-btn:hover {
    background: linear-gradient(135deg, #5a32a3 0%, #4a2780 100%);
    transform: translateY(-2px);
    text-decoration: none;
    color: white;
    box-shadow: 0 8px 24px rgba(111, 66, 193, 0.4);
}

.see-expired-btn {
    background: linear-gradient(135deg, #fd7e14 0%, #e06c00 100%);
    border: none;
    padding: 12px 24px;
    border-radius: 12px;
    font-weight: 700;
    transition: all 0.3s ease;
    color: white;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 15px rgba(253, 126, 20, 0.3);
}

.see-expired-btn:hover {
    background: linear-gradient(135deg, #e06c00 0%, #c85e00 100%);
    transform: translateY(-2px);
    text-decoration: none;
    color: white;
    box-shadow: 0 8px 24px rgba(253, 126, 20, 0.4);
}

.btn-action {
    flex: 1;
    min-width: 120px;
    text-align: center;
}

/* Responsive Design */
@media (max-width: 768px) {
    body {
        padding-top: 80px;
    }
    
    .profile-container {
        padding: 24px;
        margin: 20px;
        padding-top: 140px;
    }
    
    .profile-avatar-top-left {
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 80px;
        font-size: 2.5em;
    }
    
    .coupon-actions-row {
        text-align: center;
        margin-top: 20px;
        justify-content: center;
    }
    
    .coupon-actions-row .btn-primary {
        flex: 1;
        min-width: 80px;
        padding: 10px 16px;
    }
    
    .profile-username {
        font-size: 1.75rem;
    }
    
    .profile-stats {
        flex-direction: column;
        gap: 20px;
    }
    
    .stat-item {
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        padding-bottom: 20px;
    }
    
    .stat-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    
    .info-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 6px;
    }
    
    .info-label {
        font-weight: 700;
        color: #667eea;
    }
    
    .info-value {
        text-align: left;
        font-weight: 600;
    }
    
    .profile-actions {
        flex-direction: column;
    }
    
    .btn-outline-primary,
    .logout-btn,
    .edit-profile-btn,
    .see-expired-btn {
        width: 100%;
        justify-content: center;
    }
    
    .section-title {
        font-size: 1.3rem;
    }
    
    .stat-number {
        font-size: 1.75rem;
    }
}

@media (max-width: 576px) {
    .profile-container {
        padding: 20px;
        padding-top: 120px;
    }
    
    .profile-avatar-top-left {
        width: 70px;
        height: 70px;
        font-size: 2em;
    }
    
    .profile-username {
        font-size: 1.5rem;
    }
    
    .profile-email {
        font-size: 0.9rem;
    }
    
    .coupon-actions-row .btn-primary {
        padding: 8px 12px;
        font-size: 0.85rem;
        min-width: 70px;
    }
    
    .coupon-actions-row .btn-primary i {
        font-size: 1.3em;
    }
    
    .stat-number {
        font-size: 1.5rem;
    }
    
    .stat-label {
        font-size: 0.85rem;
    }
    
    .section-title {
        font-size: 1.2rem;
    }
    
    .info-item {
        padding: 12px 0;
    }
    
    .info-label,
    .info-value {
        font-size: 0.9rem;
    }
}
</style>
</head>
<body>
    <!-- Include the navbar -->
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container">
        <div class="profile-container">
            <!-- Profile Avatar top-left -->
            <div class="profile-avatar-top-left">
                <i class="bi bi-person"></i>
            </div>
            
            <!-- Coupon action icons -->
            <div class="coupon-actions-row">
                <a href="add_coupon.php" class="btn btn-primary" title="Add Coupon">
                    <i class="bi bi-plus-circle"></i>
                    <span>Add Coupon</span>
                </a>
                <a href="all_coupons.php" class="btn btn-primary" title="See All Coupons">
                    <i class="bi bi-ticket-perforated"></i>
                    <span>All Coupons</span>
                </a>
                <a href="used_coupons.php" class="btn btn-primary" title="See Used Coupons">
                    <i class="bi bi-archive"></i>
                    <span>Used Coupons</span>
                </a>
                <a href="expired_coupons.php" class="btn btn-primary" title="See Expired Coupons">
                    <i class="bi bi-x-circle"></i>
                    <span>Expired Coupons</span>
                </a>
            </div>
            
            <div class="profile-header">
                <h1 class="profile-username"><?php echo htmlspecialchars($user['username']); ?></h1>
                <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
            </div>
            
            <div class="profile-stats">
                <div class="stat-item">
                    <div class="stat-number"><?php echo htmlspecialchars($user_points); ?></div>
                    <div class="stat-label points-stat">Points</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo htmlspecialchars($coupon_count); ?></div>
                    <div class="stat-label">Total Coupons</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo htmlspecialchars($expired_count); ?></div>
                    <div class="stat-label">Expired Coupons</div>
                </div>
            </div>
            
            <div class="profile-section">
                <h3 class="section-title">Personal Information</h3>
                <div class="info-item">
                    <span class="info-label">Username:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['username']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Mobile:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['mobile']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Member Since:</span>
                    <span class="info-value"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Points:</span>
                    <span class="info-value points-stat"><?php echo htmlspecialchars($user_points); ?> Points</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Coupons Posted:</span>
                    <span class="info-value"><?php echo htmlspecialchars($coupon_count); ?> Coupons</span>
                </div>
            </div>
            
            <div class="profile-section text-center">
                <h3 class="section-title">Account Actions</h3>
                <div class="profile-actions">
                    <button class="btn btn-outline-primary" onclick="window.location.href='index.php'">
                        <i class="bi bi-arrow-left me-2"></i>Back to Coupons
                    </button>
                    <a href="edit_user_profile.php" class="edit-profile-btn">
                        <i class="bi bi-pencil-square me-2"></i>Edit Profile
                    </a>
                    <a href="expired_coupons.php" class="see-expired-btn">
                        <i class="bi bi-x-circle me-2"></i>See Expired Coupons
                    </a>
                    <a href="logout.php" class="logout-btn">
                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
