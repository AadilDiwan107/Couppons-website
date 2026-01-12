<?php
// Define variables for dynamic content
$websiteName = "Coupon.is-great.org";

// Start session to check login status
session_start();

// Include database connection
include 'includes/db.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['username']);

// Get search query and filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

// Check if this is the index page (show only 10 coupons)
$isIndexPage = basename($_SERVER['PHP_SELF']) === 'index.php';

// Update expired coupons to "expired" status
$update_sql = "UPDATE coupons SET c_status = 'expired' WHERE expiration_date < CURDATE() AND c_status = 'active'";
mysqli_query($connection, $update_sql);

// Build the SQL query with search and filter functionality
$where_conditions = [];
$params = [];
$types = "";

// Always show only active coupons
$where_conditions[] = "c_status = 'active'";

if (!empty($search)) {
    $where_conditions[] = "(description LIKE ? OR code LIKE ? OR terms LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
    $types .= "sss";
}

if (!empty($filter)) {
    if ($filter == 'close_to_expire') {
        $where_conditions[] = "expiration_date <= DATE_ADD(NOW(), INTERVAL 7 DAY)";
    } elseif ($filter == 'long_expire') {
        $where_conditions[] = "expiration_date > DATE_ADD(NOW(), INTERVAL 30 DAY)";
    }
}

// Construct WHERE clause
$where_clause = "";
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

$order_clause = "ORDER BY RAND()";

// Add limit for index page
$limit_clause = "";
if ($isIndexPage) {
    $limit_clause = "LIMIT 10";
}

$sql = "SELECT id, description, company_name, link, code, terms, expiration_date, created_at, is_top, c_status FROM coupons $where_clause $order_clause $limit_clause";

// Prepare and execute query
if (!empty($params)) {
    $stmt = $connection->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        // Fallback if prepare fails
        $result = mysqli_query($connection, $sql);
    }
} else {
    $result = mysqli_query($connection, $sql);
}

$coupons = [];

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Only fetch coupon code if user is logged in and coupon is active
        if ($isLoggedIn && $row['c_status'] === 'active') {
            $row['code'] = $row['code']; // This will be fetched in the query below
        } else {
            $row['code'] = null; // Don't include the code for non-logged-in users or non-active coupons
        }
        $coupons[] = $row;
    }
    
    // Close statement if it was prepared
    if (isset($stmt) && $stmt) {
        $stmt->close();
    }
}

// Re-run the query for logged-in users to get codes
if ($isLoggedIn) {
    $sql = "SELECT id, description, company_name, link, code, terms, expiration_date, created_at, is_top, c_status FROM coupons $where_clause $order_clause $limit_clause";
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
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isIndexPage ? 'Available Coupons' : 'All Coupons'; ?> - <?php echo $websiteName; ?></title>
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

        * { margin: 0; padding: 0; box-sizing: border-box; }

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

        h1 {
            font-size: 2.4rem;
            font-weight: 800;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.5px;
        }

        .coupons-container {
            padding: 24px 0 48px;
        }

        .search-section {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 18px;
            padding: 22px;
            margin-bottom: 28px;
            box-shadow: var(--shadow-md);
            backdrop-filter: blur(10px);
        }

        .search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .search-input {
            flex: 1;
            min-width: 220px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: var(--text-primary);
            padding: 12px 14px;
            border-radius: 12px;
            transition: all 0.25s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: rgba(102, 126, 234, 0.7);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
            background: rgba(255, 255, 255, 0.08);
        }

        .search-input::placeholder {
            color: rgba(255, 255, 255, 0.55);
        }

        .search-btn {
            background: var(--success-gradient);
            color: white;
            border: none;
            padding: 12px 18px;
            border-radius: 12px;
            font-weight: 700;
            transition: all 0.25s ease;
            box-shadow: 0 6px 18px rgba(17, 153, 142, 0.25);
        }

        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 22px rgba(17, 153, 142, 0.35);
        }

        .filter-section {
            display: flex;
            gap: 10px;
            margin-top: 14px;
            flex-wrap: wrap;
        }

        .filter-btn {
            background: rgba(255, 255, 255, 0.06);
            color: var(--text-primary);
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 9px 15px;
            border-radius: 18px;
            cursor: pointer;
            transition: all 0.25s ease;
            font-size: 0.9rem;
        }

        .filter-btn:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(102, 126, 234, 0.6);
        }

        .filter-btn.active {
            background: var(--primary-gradient);
            border-color: transparent;
            color: white;
            box-shadow: 0 8px 18px rgba(102, 126, 234, 0.35);
        }

        .card.coupon-card {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border-radius: 18px;
            padding: 18px;
            position: relative;
            transition: all 0.3s;
            height: 100%;
            border: 1px solid var(--card-border);
            box-shadow: var(--shadow-md);
            overflow: hidden;
        }

        .card.coupon-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
            opacity: 0;
            transition: opacity 0.25s ease;
        }

        .card.coupon-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg), var(--shadow-glow);
            border-color: rgba(102, 126, 234, 0.6);
        }

        .card.coupon-card:hover::before {
            opacity: 1;
        }

        .coupon-card.top-coupon {
            border: 2px solid rgba(255, 215, 0, 0.5);
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.05) 0%, var(--card-bg) 100%);
            box-shadow: 0 10px 28px rgba(255, 215, 0, 0.2);
        }

        .coupon-card.top-coupon::before {
            opacity: 1;
            background: linear-gradient(90deg, #ffd700 0%, #ffed4e 100%);
        }

        .top-badge {
            position: absolute;
            top: 14px;
            right: 14px;
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            color: #000;
            padding: 6px 12px;
            border-radius: 18px;
            font-size: 0.8rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            box-shadow: 0 6px 16px rgba(255, 215, 0, 0.35);
            z-index: 2;
        }

        .status-badge {
            position: absolute;
            top: 14px;
            left: 14px;
            padding: 6px 12px;
            border-radius: 18px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            z-index: 2;
            box-shadow: var(--shadow-sm);
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: white;
        }

        .used-badge { background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); }
        .expired-badge { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); }

        .coupon-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .coupon-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
        }

        .coupon-category {
            background: rgba(102, 126, 234, 0.18);
            color: #cbd5ff;
            padding: 6px 12px;
            border-radius: 16px;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .coupon-description {
            margin: 12px 0;
            color: var(--text-secondary);
            line-height: 1.55;
            font-size: 0.97rem;
        }

        .coupon-link {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            background: rgba(102, 126, 234, 0.14);
            padding: 10px 14px;
            border-radius: 12px;
            margin: 10px 0;
            color: #7fa2ff;
            text-decoration: none;
            transition: all 0.25s ease;
            border: 1px solid rgba(102, 126, 234, 0.35);
        }

        .coupon-link:hover {
            background: var(--primary-gradient);
            color: white;
            border-color: transparent;
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(102, 126, 234, 0.35);
        }

        .coupon-code-container {
            background: rgba(255, 255, 255, 0.05);
            border: 1px dashed rgba(255, 255, 255, 0.12);
            border-radius: 14px;
            padding: 14px;
            margin: 12px 0;
            text-align: center;
        }

        .coupon-code {
            background: var(--success-gradient);
            color: white;
            padding: 12px 22px;
            border-radius: 14px;
            font-weight: 800;
            font-size: 1.05rem;
            letter-spacing: 2px;
            display: inline-block;
            box-shadow: 0 6px 18px rgba(17, 153, 142, 0.3);
            transition: all 0.25s ease;
            cursor: pointer;
        }

        .coupon-code:hover {
            transform: scale(1.04);
            box-shadow: 0 10px 24px rgba(17, 153, 142, 0.38);
        }

        .login-required {
            background: var(--warning-gradient) !important;
            color: white !important;
            box-shadow: 0 4px 15px rgba(245, 87, 108, 0.3);
        }

        .terms-section {
            background: rgba(255, 255, 255, 0.04);
            padding: 10px 12px;
            border-radius: 12px;
            margin: 10px 0;
            font-size: 0.92rem;
            border-left: 3px solid rgba(102, 126, 234, 0.7);
            color: var(--text-secondary);
        }

        .coupon-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 14px;
            padding-top: 10px;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            font-size: 0.88rem;
            color: var(--text-secondary);
        }

        .coupon-expiration {
            color: #ffb3c1;
            font-weight: 700;
        }

        .coupon-date {
            color: rgba(255, 255, 255, 0.7);
        }

        .see-all-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 12px;
            border: 1px solid rgba(102, 126, 234, 0.3);
            background: var(--primary-gradient);
            color: #fff;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 6px 18px rgba(102, 126, 234, 0.35);
            transition: transform 0.2s ease, box-shadow 0.3s ease, opacity 0.2s;
        }

        .see-all-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(102, 126, 234, 0.4);
        }

        .see-all-btn:active {
            transform: translateY(0);
            opacity: 0.9;
        }

        .used-it-btn {
            background: linear-gradient(135deg, #6f42c1, #5a32a3);
            color: white;
            border: none;
            padding: 10px 14px;
            border-radius: 12px;
            font-weight: 700;
            transition: all 0.25s ease;
            cursor: pointer;
            width: 100%;
            margin-top: 12px;
            text-align: center;
            display: inline-block;
            box-shadow: 0 8px 20px rgba(111, 66, 193, 0.35);
        }

        .used-it-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 26px rgba(111, 66, 193, 0.45);
        }

        .used-it-btn.used {
            background: linear-gradient(135deg, #17a2b8, #138496);
            cursor: not-allowed;
            box-shadow: none;
        }

        .no-coupons {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }

        .no-coupons i {
            font-size: 2.8rem;
            margin-bottom: 12px;
            opacity: 0.6;
        }

        .alert-info {
            background: rgba(102, 126, 234, 0.12);
            border: 1px solid rgba(102, 126, 234, 0.4);
            color: #dce3ff;
        }

        @media (max-width: 768px) {
            body { padding-top: 80px; }
            h1 { font-size: 2rem; }
            .search-form { flex-direction: column; }
            .filter-section { flex-direction: column; }
            .coupon-header { flex-direction: column; align-items: flex-start; gap: 8px; }
            .coupon-meta { flex-direction: column; align-items: flex-start; gap: 6px; }
        }
    </style>
</head>
<body>
    <!-- Include the navbar -->
    <?php include 'includes/navbar.php'; ?>
    
    <!-- Main Content -->
    <div class="container">
    <div class="coupons-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0" style="animation: fadeIn 1s ease-in;">
                <?php echo $isIndexPage ? 'Available Coupons' : 'All Coupons'; ?>
            </h1>
            <?php if ($isIndexPage && count($coupons) >= 10): ?>
                <a href="search_coupons.php" class="btn btn-outline-light see-all-btn">
                    <i class="bi bi-arrow-right-circle me-2"></i>See All Coupons
                </a>
            <?php endif; ?>
        </div>
        
        <?php if (!$isIndexPage): ?>
            <!-- Search Section (only for search page) -->
            <div class="search-section">
                <form method="GET" action="" class="search-form">
                    <input type="text" name="search" class="search-input" placeholder="Search coupons by description, code, or terms..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="search-btn">
                        <i class="bi bi-search me-2"></i>Search
                    </button>
                </form>
                
                <!-- Filter Section -->
                <div class="filter-section">
                    <button type="button" class="filter-btn <?php echo $filter == 'close_to_expire' ? 'active' : ''; ?>" 
                            onclick="applyFilter('close_to_expire')">
                        <i class="bi bi-clock me-1"></i> Close to Expire
                    </button>
                    <button type="button" class="filter-btn <?php echo $filter == 'long_expire' ? 'active' : ''; ?>" 
                            onclick="applyFilter('long_expire')">
                        <i class="bi bi-calendar-check me-1"></i> Long to Expire
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
                            Showing results for "<?php echo htmlspecialchars($search); ?>" with <?php echo $filter == 'close_to_expire' ? 'close to expire' : 'long to expire'; ?> filter
                        <?php elseif (!empty($search)): ?>
                            Showing results for "<?php echo htmlspecialchars($search); ?>"
                        <?php elseif (!empty($filter)): ?>
                            Showing <?php echo $filter == 'close_to_expire' ? 'coupons close to expire' : 'coupons with long expiration'; ?>
                        <?php endif; ?>
                        <?php if (empty($coupons)): ?>
                            - No coupons found
                        <?php else: ?>
                            - <?php echo count($coupons); ?> coupon(s) found
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
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
                                
                                <?php if ($isLoggedIn && $coupon['c_status'] === 'active'): ?>
                                    <button class="used-it-btn" onclick="markAsUsed(<?php echo $coupon['id']; ?>, this)">
                                        <i class="bi bi-check-circle me-2"></i>I Used It
                                    </button>
                                <?php elseif ($isLoggedIn && $coupon['c_status'] === 'used'): ?>
                                    <button class="used-it-btn used">
                                        <i class="bi bi-check-circle-fill me-2"></i>Already Used
                                    </button>
                                <?php endif; ?>
                                
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
                        Check back later for new deals!
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
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
        
        function markAsUsed(couponId, button) {
            // Change button appearance to show it's been used
            button.classList.add('used');
            button.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>Used!';
            button.onclick = null; // Remove the click handler
            
            // Change card appearance to show it's used
            const card = button.closest('.coupon-card');
            card.classList.remove('active');
            card.classList.add('used');
            
            // Update the coupon code display
            const codeContainer = card.querySelector('.coupon-code');
            if (codeContainer) {
                codeContainer.style.background = 'linear-gradient(45deg, #28a745, #218838)';
                codeContainer.innerHTML = '<i class="bi bi-check-circle me-2"></i>USED';
            }
            
            // Add status badge
            const statusBadge = document.createElement('div');
            statusBadge.className = 'status-badge used-badge';
            statusBadge.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Used';
            card.appendChild(statusBadge);
            
            // Send AJAX request to mark as used
            fetch('mark_used.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    coupon_id: couponId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Coupon marked as used');
                } else {
                    console.error('Failed to mark coupon as used');
                    // Revert changes if failed
                    button.classList.remove('used');
                    button.innerHTML = '<i class="bi bi-check-circle me-2"></i>I Used It';
                    button.onclick = function() { markAsUsed(couponId, this); };
                    
                    card.classList.remove('used');
                    card.classList.add('active');
                    
                    if (codeContainer) {
                        codeContainer.style.background = 'linear-gradient(45deg, #28a745, #218838)';
                        codeContainer.innerHTML = card.querySelector('.coupon-code').dataset.originalCode || 'Code';
                    }
                    
                    // Remove status badge
                    const badge = card.querySelector('.status-badge');
                    if (badge) badge.remove();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Revert changes if error
                button.classList.remove('used');
                button.innerHTML = '<i class="bi bi-check-circle me-2"></i>I Used It';
                button.onclick = function() { markAsUsed(couponId, this); };
                
                card.classList.remove('used');
                card.classList.add('active');
                
                if (codeContainer) {
                    codeContainer.style.background = 'linear-gradient(45deg, #28a745, #218838)';
                    codeContainer.innerHTML = card.querySelector('.coupon-code').dataset.originalCode || 'Code';
                }
                
                // Remove status badge
                const badge = card.querySelector('.status-badge');
                if (badge) badge.remove();
            });
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
