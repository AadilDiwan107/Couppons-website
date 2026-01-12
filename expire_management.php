<?php
// Define website name variable
$websiteName = "Coupon.is-great.org";

// Start session to check login status
session_start();

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['username']);

// Include database connection
include 'includes/db.php';

// Get user information
if ($isLoggedIn) {
    $user_id = $_SESSION['user_id'];
    $stmt = $connection->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
}

// Update expired coupons to "expired" status
$update_sql = "UPDATE coupons SET c_status = 'expired' WHERE expiration_date < CURDATE() AND c_status = 'active'";
mysqli_query($connection, $update_sql);

// Fetch only expired coupons from the database
$sql = "SELECT id, description, company_name, link, code, terms, expiration_date, created_at, is_top, c_status FROM coupons WHERE c_status = 'expired' ORDER BY expiration_date DESC";
$result = mysqli_query($connection, $sql);
$coupons = [];

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Only fetch coupon code if user is logged in
        if ($isLoggedIn) {
            $row['code'] = $row['code']; // This will be fetched in the query below
        } else {
            $row['code'] = null; // Don't include the code for non-logged-in users
        }
        $coupons[] = $row;
    }
}

// Re-run the query for logged-in users to get codes
if ($isLoggedIn) {
    $sql = "SELECT id, description, company_name, link, code, terms, expiration_date, created_at, is_top, c_status FROM coupons WHERE c_status = 'expired' ORDER BY expiration_date DESC";
    $result = mysqli_query($connection, $sql);
    $coupons = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $coupons[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expired Coupons - <?php echo $websiteName; ?></title>
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

        .coupons-container {
            padding: 20px;
            position: relative;
            z-index: 2;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            color: #dc3545;
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 2.5em;
        }
        
        .page-header p {
            color: #aaa;
            font-size: 1.2em;
        }
        
        .expired-badge {
            background: linear-gradient(45deg, #dc3545, #c82333);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
            position: absolute;
            top: -10px;
            left: -10px;
            z-index: 10;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }
        
        .coupon-card {
            background: rgba(220, 53, 69, 0.1); /* Red tint for expired */
            backdrop-filter: blur(10px);
            border: 1px solid rgba(220, 53, 69, 0.3); /* Red border for expired */
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            animation: fadeInUp 0.6s ease-out;
            animation-fill-mode: both;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }
        
        .coupon-card.top-coupon {
            border: 2px solid #ffd700;
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.3);
        }
        
        .coupon-card.top-coupon::before {
            background: linear-gradient(90deg, #ffd700, #ff6b6b, #4ecdc4, #45b7d1, #96ceb4, #feca57, #ffd700);
        }
        
        .top-badge {
            position: absolute;
            top: -10px;
            right: -10px;
            background: linear-gradient(45deg, #ffd700, #ffed4e);
            color: #333;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
            z-index: 10;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }
        
        .coupon-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #dc3545, #ff6b6b, #4ecdc4, #45b7d1, #96ceb4, #feca57, #dc3545);
        }
        
        .coupon-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            background: rgba(220, 53, 69, 0.15);
        }
        
        .coupon-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .coupon-title {
            font-size: 1.2em;
            font-weight: bold;
            color: white; /* Changed to white color */
            margin: 0;
        }
        
        .coupon-category {
            background: rgba(220, 53, 69, 0.2); /* Red background for expired */
            color: #dc3545;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .coupon-description {
            margin: 15px 0;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.5;
            font-size: 0.95em;
        }
        
        .coupon-code-container {
            background: rgba(220, 53, 69, 0.1); /* Red tint for expired */
            border: 2px dashed rgba(220, 53, 69, 0.3); /* Red border for expired */
            border-radius: 12px;
            padding: 15px;
            margin: 15px 0;
            text-align: center;
            position: relative;
        }
        
        .coupon-code {
            background: linear-gradient(45deg, #dc3545, #c82333); /* Red gradient for expired */
            color: white;
            padding: 12px 20px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 1.3em;
            letter-spacing: 2px;
            display: inline-block;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
            transition: all 0.3s ease;
        }
        
        .coupon-code:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.5);
        }
        
        .login-prompt {
            background: rgba(255, 107, 107, 0.2);
            color: #ff6b6b;
            padding: 10px;
            border-radius: 8px;
            font-size: 0.9em;
            text-align: center;
            margin-top: 10px;
        }
        
        .coupon-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.85em;
        }
        
        .coupon-expiration {
            color: #dc3545; /* Red color for expired */
            font-weight: bold;
        }
        
        .coupon-date {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .no-coupons {
            text-align: center;
            padding: 80px 20px;
            color: rgba(255, 255, 255, 0.7);
            animation: fadeIn 1s ease-in;
        }
        
        .no-coupons i {
            font-size: 3em;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .no-coupons h3 {
            margin-bottom: 10px;
            color: white;
        }
        
        .login-indicator {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: bold;
        }
        
        .login-required {
            background: linear-gradient(45deg, #ff6b6b, #ff8e53) !important;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .login-required:hover {
            background: linear-gradient(45deg, #ff8e53, #ff6b6b) !important;
            transform: scale(1.05);
        }

        /* Animation keyframes */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }

        .coupon-link {
            display: block;
            background: rgba(255, 255, 255, 0.1);
            padding: 10px 15px;
            border-radius: 8px;
            margin: 10px 0;
            text-align: center;
            color: #ff6b6b; /* Red color for expired */
            text-decoration: none;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .coupon-link:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #ff6b6b;
            transform: translateY(-2px);
            border-color: #ff6b6b;
        }
        
        .terms-section {
            background: rgba(255, 255, 255, 0.05);
            padding: 10px;
            border-radius: 8px;
            margin: 10px 0;
            font-size: 0.9em;
            border-left: 3px solid #ff6b6b; /* Red border for expired */
        }
        
        .expired-note {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            padding: 10px;
            border-radius: 8px;
            margin: 15px 0;
            font-size: 0.9em;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }
        
        .expired-note i {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <!-- Animated background particles -->
    <div class="particles" id="particles"></div>
    
    <!-- Include the navbar -->
    <?php include 'includes/navbar.php'; ?>
    
    <!-- Main Content -->
    <div class="coupons-container">
        <div class="page-header">
            <h1><i class="bi bi-exclamation-triangle me-2"></i>Expired Coupons</h1>
            <p>Coupons that have passed their expiration date</p>
        </div>
        
        <?php if (!$isLoggedIn): ?>
            <div class="alert alert-info text-center mb-4" style="animation: fadeIn 1s ease-in;">
                <i class="bi bi-info-circle me-2"></i>
                Please <a href="login.php" class="alert-link">log in</a> to see coupon codes
            </div>
        <?php endif; ?>
        
        <?php if (!empty($coupons)): ?>
            <div class="row">
                <?php foreach ($coupons as $coupon): ?>
                    <div class="col-12 col-md-6 col-lg-4 mb-4">
                        <div class="card coupon-card h-100 <?php echo $coupon['is_top'] ? 'top-coupon' : ''; ?>">
                            <div class="card-body">
                                <?php if ($coupon['is_top']): ?>
                                    <div class="top-badge">
                                        <i class="bi bi-star-fill me-1"></i> TOP
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Expired badge -->
                                <div class="expired-badge">
                                    <i class="bi bi-x-circle-fill me-1"></i> Expired
                                </div>
                                
                                <?php if ($isLoggedIn): ?>
                                    <div class="login-indicator">
                                        <i class="bi bi-check-circle me-1"></i> Logged In
                                    </div>
                                <?php endif; ?>
                                
                                <div class="coupon-header">
                                    <h5 class="coupon-title">
                                        <?php 
                                        if (!empty($coupon['company_name'])) {
                                            echo htmlspecialchars($coupon['company_name']);
                                        } else {
                                            echo "Special Offer";
                                        }
                                        ?>
                                    </h5>
                                    <span class="coupon-category">Deal</span>
                                </div>
                                
                                <p class="coupon-description">
                                    <?php echo htmlspecialchars($coupon['description'] ?? 'No description available'); ?>
                                </p>
                                
                                <div class="expired-note">
                                    <i class="bi bi-info-circle me-1"></i> This coupon has expired and is no longer valid
                                </div>
                                
                                <?php if (!empty($coupon['link'])): ?>
                                    <a href="<?php echo htmlspecialchars($coupon['link']); ?>" target="_blank" class="coupon-link">
                                        <i class="bi bi-link-45deg me-1"></i> Visit Store
                                    </a>
                                <?php endif; ?>
                                
                                <div class="coupon-code-container">
                                    <?php if ($isLoggedIn && !empty($coupon['code'])): ?>
                                        <div class="coupon-code" style="background: linear-gradient(45deg, #dc3545, #c82333);">
                                            <i class="bi bi-x-circle me-2"></i>EXPIRED
                                        </div>
                                    <?php else: ?>
                                        <div class="coupon-code login-required" onclick="window.location.href='login.php'" style="background: linear-gradient(45deg, #6c757d, #5a6268);">
                                            <i class="bi bi-lock me-1"></i> Login to view code
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (!empty($coupon['terms'])): ?>
                                    <div class="terms-section">
                                        <strong>Terms:</strong> <?php echo htmlspecialchars($coupon['terms']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="coupon-meta">
                                    <?php if (!empty($coupon['expiration_date'])): ?>
                                        <span class="coupon-expiration">
                                            <i class="bi bi-clock me-1"></i>
                                            <?php echo date('M d, Y', strtotime($coupon['expiration_date'])); ?>
                                        </span>
                                    <?php endif; ?>
                                    <span class="coupon-date">
                                        <?php echo date('M d, Y', strtotime($coupon['created_at'] ?? date('Y-m-d'))); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-coupons">
                <i class="bi bi-x-circle"></i>
                <h3>No expired coupons</h3>
                <p>There are no expired coupons at the moment!</p>
            </div>
        <?php endif; ?>
        
        <div class="text-center mt-4">
            <a href="index.php" class="btn btn-outline-light me-2">
                <i class="bi bi-arrow-left me-2"></i>Back to Home
            </a>
            <a href="all_coupons.php" class="btn btn-outline-light">
                <i class="bi bi-tags me-2"></i>All Coupons
            </a>
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
                const size = Math.random() * 8 + 5;
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
        
        function copyToClipboard(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function() {
                    // Show a temporary notification
                    const originalText = document.querySelector('.coupon-code').textContent;
                    document.querySelector('.coupon-code').textContent = 'Copied!';
                    setTimeout(function() {
                        document.querySelector('.coupon-code').textContent = originalText;
                    }, 2000);
                }).catch(function(err) {
                    console.error('Failed to copy: ', err);
                });
            } else {
                // Fallback for older browsers
                const textArea = document.createElement("textarea");
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
            }
        }
    </script>
</body>
</html>
