<?php
// Define website name variable
$websiteName = "Coupon.is-great.org";

// Start session to check login status
session_start();

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Include database connection
include 'includes/db.php';

// Get admin information
$admin_id = $_SESSION['user_id'];
$stmt = $connection->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close();

// Get search query and filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

// Build the SQL query with search and filter functionality for coupons
$where_clause = "WHERE 1=1"; // Start with a neutral condition
$params = [];
$types = "";

if (!empty($search)) {
    $where_clause .= " AND (description LIKE ? OR code LIKE ? OR terms LIKE ? OR company_name LIKE ? OR u.username LIKE ?)";
    $search_param = "%$search%";
    $params = array($search_param, $search_param, $search_param, $search_param, $search_param);
    $types = "sssss";
}

if (!empty($filter)) {
    if ($filter == 'active') {
        $where_clause .= " AND c_status = 'active'";
    } elseif ($filter == 'used') {
        $where_clause .= " AND c_status = 'used'";
    } elseif ($filter == 'expired') {
        $where_clause .= " AND c_status = 'expired'";
    } elseif ($filter == 'top') {
        $where_clause .= " AND is_top = 1";
    }
}

$order_clause = "ORDER BY is_top DESC, created_at DESC";

$sql = "SELECT c.id, c.description, c.company_name, c.link, c.code, c.terms, c.expiration_date, c.created_at, c.is_top, c.c_status, u.username as user_name FROM coupons c LEFT JOIN users u ON c.user_id = u.id $where_clause $order_clause";
$stmt = $connection->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$coupons = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $coupons[] = $row;
    }
}
$stmt->close();

// Handle coupon status changes
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $coupon_id = $_POST['coupon_id'];
    $new_status = $_POST['status'];
    
    // Validate status
    if (in_array($new_status, ['active', 'used', 'expired'])) {
        $stmt = $connection->prepare("UPDATE coupons SET c_status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $coupon_id);
        
        if ($stmt->execute()) {
            $success_message = "Coupon status updated successfully!";
            // Refresh coupons list
            $result = mysqli_query($connection, $sql);
            $coupons = [];
            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $coupons[] = $row;
                }
            }
        } else {
            $error_message = "Failed to update coupon status.";
        }
        
        $stmt->close();
    } else {
        $error_message = "Invalid status selected.";
    }
}

// Handle coupon deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_coupon'])) {
    $coupon_id = $_POST['coupon_id'];
    
    $stmt = $connection->prepare("DELETE FROM coupons WHERE id = ?");
    $stmt->bind_param("i", $coupon_id);
    
    if ($stmt->execute()) {
        $success_message = "Coupon deleted successfully!";
        // Refresh coupons list
        $result = mysqli_query($connection, $sql);
        $coupons = [];
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $coupons[] = $row;
            }
        }
    } else {
        $error_message = "Failed to delete coupon.";
    }
    
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderation Panel - <?php echo $websiteName; ?></title>
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

        .moderation-container {
            padding: 20px;
            position: relative;
            z-index: 2;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            color: #28a745;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .page-header p {
            color: #aaa;
        }
        
        .stats-section {
            display: flex;
            justify-content: space-around;
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #28a745;
        }
        
        .stat-label {
            color: #aaa;
            font-size: 0.9em;
        }
        
        .search-section {
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }
        
        .search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .search-input {
            flex: 1;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 12px 15px;
            border-radius: 10px;
            transition: all 0.3s ease;
            min-width: 200px;
        }
        
        .search-input:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: #28a745;
            box-shadow: 0 0 0 0.25rem rgba(40, 167, 69, 0.25);
            color: white;
        }
        
        .search-input::placeholder {
            color: #aaa;
        }
        
        .search-btn {
            background: linear-gradient(45deg, #28a745, #218838);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .search-btn:hover {
            background: linear-gradient(45deg, #218838, #1e7e34);
            transform: translateY(-2px);
        }
        
        .filter-section {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9em;
        }
        
        .filter-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .filter-btn.active {
            background: linear-gradient(45deg, #28a745, #218838);
            border-color: #28a745;
        }
        
        .coupon-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
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
        
        .status-badge {
            position: absolute;
            top: -10px;
            left: -10px;
            background: linear-gradient(45deg, #28a745, #218838);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
            z-index: 10;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }
        
        .used-badge {
            background: linear-gradient(45deg, #17a2b8, #138496);
        }
        
        .expired-badge {
            background: linear-gradient(45deg, #dc3545, #c82333);
        }
        
        .coupon-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ff6b6b, #4ecdc4, #45b7d1, #96ceb4, #feca57);
        }
        
        .coupon-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            background: rgba(255, 255, 255, 0.15);
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
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
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
            background: rgba(40, 167, 69, 0.1);
            border: 2px dashed rgba(40, 167, 69, 0.3);
            border-radius: 12px;
            padding: 15px;
            margin: 15px 0;
            text-align: center;
            position: relative;
        }
        
        .coupon-code {
            background: linear-gradient(45deg, #28a745, #218838);
            color: white;
            padding: 12px 20px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 1.3em;
            letter-spacing: 2px;
            display: inline-block;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
            transition: all 0.3s ease;
        }
        
        .coupon-code:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.5);
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
            color: #ff6b6b;
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
            color: #4ecdc4;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .coupon-link:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #4ecdc4;
            transform: translateY(-2px);
            border-color: #4ecdc4;
        }
        
        .terms-section {
            background: rgba(255, 255, 255, 0.05);
            padding: 10px;
            border-radius: 8px;
            margin: 10px 0;
            font-size: 0.9em;
            border-left: 3px solid #4ecdc4;
        }
        
        .search-results {
            color: #aaa;
            font-size: 0.9em;
            margin-top: 10px;
            text-align: center;
        }
        
        .coupon-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .action-btn {
            padding: 8px 15px;
            border-radius: 8px;
            font-weight: bold;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .status-btn {
            background: linear-gradient(45deg, #ffc107, #e0a800);
            color: white;
            border: none;
        }
        
        .delete-btn {
            background: linear-gradient(45deg, #dc3545, #c82333);
            color: white;
            border: none;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
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
    </style>
</head>
<body>
    <!-- Animated background particles -->
    <div class="particles" id="particles"></div>
    
    <!-- Include the navbar -->
    <?php include 'includes/admin_nav.php'; ?>
    
    <!-- Main Content -->
    <div class="moderation-container">
        <div class="page-header">
            <h1><i class="bi bi-shield-lock me-2"></i>Moderation Panel</h1>
            <p>Manage and moderate coupons</p>
        </div>
        
        <!-- Statistics Section -->
        <div class="stats-section">
            <div class="stat-item">
                <div class="stat-number">
                    <?php
                    $stmt = $connection->prepare("SELECT COUNT(*) as count FROM coupons");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $total_coupons = $result->fetch_assoc()['count'];
                    $stmt->close();
                    echo $total_coupons;
                    ?>
                </div>
                <div class="stat-label">Total Coupons</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">
                    <?php
                    $stmt = $connection->prepare("SELECT COUNT(*) as count FROM coupons WHERE c_status = 'active'");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $active_coupons = $result->fetch_assoc()['count'];
                    $stmt->close();
                    echo $active_coupons;
                    ?>
                </div>
                <div class="stat-label">Active Coupons</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">
                    <?php
                    $stmt = $connection->prepare("SELECT COUNT(*) as count FROM coupons WHERE c_status = 'used'");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $used_coupons = $result->fetch_assoc()['count'];
                    $stmt->close();
                    echo $used_coupons;
                    ?>
                </div>
                <div class="stat-label">Used Coupons</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">
                    <?php
                    $stmt = $connection->prepare("SELECT COUNT(*) as count FROM coupons WHERE c_status = 'expired'");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $expired_coupons = $result->fetch_assoc()['count'];
                    $stmt->close();
                    echo $expired_coupons;
                    ?>
                </div>
                <div class="stat-label">Expired Coupons</div>
            </div>
        </div>
        
        <!-- Search and Filter Section -->
        <div class="search-section">
            <form method="GET" action="" class="search-form">
                <input type="text" name="search" class="search-input" placeholder="Search coupons by description, code, or company name..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="search-btn">
                    <i class="bi bi-search me-2"></i>Search
                </button>
            </form>
            
            <!-- Filter Section -->
            <div class="filter-section">
                <button type="button" class="filter-btn <?php echo $filter == 'active' ? 'active' : ''; ?>" 
                        onclick="applyFilter('active')">
                    <i class="bi bi-check-circle me-1"></i> Active
                </button>
                <button type="button" class="filter-btn <?php echo $filter == 'used' ? 'active' : ''; ?>" 
                        onclick="applyFilter('used')">
                    <i class="bi bi-check-circle-fill me-1"></i> Used
                </button>
                <button type="button" class="filter-btn <?php echo $filter == 'expired' ? 'active' : ''; ?>" 
                        onclick="applyFilter('expired')">
                    <i class="bi bi-x-circle me-1"></i> Expired
                </button>
                <button type="button" class="filter-btn <?php echo $filter == 'top' ? 'active' : ''; ?>" 
                        onclick="applyFilter('top')">
                    <i class="bi bi-star me-1"></i> Top Coupons
                </button>
                <?php if (!empty($filter) || !empty($search)): ?>
                    <button type="button" class="filter-btn" onclick="clearFilters()">
                        <i class="bi bi-x-circle me-1"></i> Clear
                    </button>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($search) || !empty($filter)): ?>
                <div class="search-results">
                    <?php if (!empty($search) && !empty($filter)): ?>
                        Showing results for "<?php echo htmlspecialchars($search); ?>" with <?php echo $filter; ?> filter
                    <?php elseif (!empty($search)): ?>
                        Showing results for "<?php echo htmlspecialchars($search); ?>"
                    <?php elseif (!empty($filter)): ?>
                        Showing <?php echo $filter; ?> coupons
                    <?php endif; ?>
                    <?php if (empty($coupons)): ?>
                        - No coupons found
                    <?php else: ?>
                        - <?php echo count($coupons); ?> coupon(s) found
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($coupons)): ?>
            <div class="row">
                <?php foreach ($coupons as $coupon): ?>
                    <div class="col-12 mb-4">
                        <div class="card coupon-card h-100 <?php echo $coupon['is_top'] ? 'top-coupon' : ''; ?> <?php echo $coupon['c_status']; ?>">
                            <div class="card-body">
                                <?php if ($coupon['is_top']): ?>
                                    <div class="top-badge">
                                        <i class="bi bi-star-fill me-1"></i> TOP
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($coupon['c_status'] !== 'active'): ?>
                                    <div class="status-badge <?php echo $coupon['c_status'] === 'used' ? 'used-badge' : 'expired-badge'; ?>">
                                        <i class="bi bi-<?php echo $coupon['c_status'] === 'used' ? 'check-circle-fill' : 'exclamation-triangle-fill'; ?> me-1"></i>
                                        <?php echo ucfirst($coupon['c_status']); ?>
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
                                
                                <?php if (!empty($coupon['link'])): ?>
                                    <a href="<?php echo htmlspecialchars($coupon['link']); ?>" target="_blank" class="coupon-link">
                                        <i class="bi bi-link-45deg me-1"></i> Visit Store
                                    </a>
                                <?php endif; ?>
                                
                                <div class="coupon-code-container">
                                    <?php if ($isLoggedIn && !empty($coupon['code']) && $coupon['c_status'] === 'active'): ?>
                                        <div class="coupon-code" onclick="copyToClipboard('<?php echo htmlspecialchars($coupon['code']); ?>')">
                                            <?php echo htmlspecialchars($coupon['code']); ?>
                                        </div>
                                    <?php elseif ($coupon['c_status'] === 'used'): ?>
                                        <div class="coupon-code" style="background: linear-gradient(45deg, #28a745, #218838);">
                                            <i class="bi bi-check-circle me-2"></i>USED
                                        </div>
                                    <?php else: ?>
                                        <div class="coupon-code login-required" onclick="window.location.href='login.php'">
                                            <i class="bi bi-lock me-1"></i> Login to view code
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (!empty($coupon['terms'])): ?>
                                    <div class="terms-section">
                                        <strong>Terms:</strong> <?php echo htmlspecialchars($coupon['terms']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="coupon-actions">
                                    <form method="POST" action="" class="status-form d-inline">
                                        <input type="hidden" name="coupon_id" value="<?php echo $coupon['id']; ?>">
                                        <select name="status" class="form-select form-select-sm me-2">
                                            <option value="active" <?php echo $coupon['c_status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="used" <?php echo $coupon['c_status'] === 'used' ? 'selected' : ''; ?>>Used</option>
                                            <option value="expired" <?php echo $coupon['c_status'] === 'expired' ? 'selected' : ''; ?>>Expired</option>
                                        </select>
                                        <button type="submit" name="update_status" class="btn btn-primary btn-sm">
                                            <i class="bi bi-gear me-1"></i>Update Status
                                        </button>
                                    </form>
                                    
                                    <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this coupon? This action cannot be undone.')">
                                        <input type="hidden" name="coupon_id" value="<?php echo $coupon['id']; ?>">
                                        <button type="submit" name="delete_coupon" class="btn btn-danger btn-sm">
                                            <i class="bi bi-trash me-1"></i>Delete
                                        </button>
                                    </form>
                                </div>
                                
                                <div class="coupon-meta">
                                    <?php if (!empty($coupon['expiration_date'])): ?>
                                        <span class="coupon-expiration">
                                            <i class="bi bi-clock me-1"></i>
                                            <?php echo date('M d', strtotime($coupon['expiration_date'])); ?>
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
                <i class="bi bi-tag"></i>
                <h3>
                    <?php if (!empty($search) || !empty($filter)): ?>
                        No coupons found
                    <?php else: ?>
                        No coupons available
                    <?php endif; ?>
                </h3>
                <p>
                    <?php if (!empty($search) || !empty($filter)): ?>
                        Try searching with different keywords or clearing filters
                    <?php else: ?>
                        There are no coupons in the system yet!
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
        
        <div class="text-center mt-4">
            <a href="admin_home.php" class="btn btn-outline-light me-2">
                <i class="bi bi-arrow-left me-2"></i>Back to Admin Panel
            </a>
            <a href="add_coupon.php" class="btn btn-outline-light">
                <i class="bi bi-plus-circle me-2"></i>Add New Coupon
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
        
        function applyFilter(filter) {
            const search = document.querySelector('.search-input') ? document.querySelector('.search-input').value : '';
            let url = window.location.pathname;
            let params = new URLSearchParams();
            
            if (search) {
                params.set('search', search);
            }
            if (filter) {
                params.set('filter', filter);
            }
            
            window.location.href = url + '?' + params.toString();
        }
        
        function clearFilters() {
            window.location.href = window.location.pathname;
        }
    </script>
</body>
</html>
