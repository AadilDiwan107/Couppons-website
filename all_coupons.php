<?php
// Define variables for dynamic content
$websiteName = "Coupon.is-great.org";

// Start session to check login status
session_start();

// Include database connection
include 'includes/db.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['username']);

if (!$isLoggedIn) {
    header("Location: login.php");
    exit();
}

// Get the logged-in user's ID
$user_id = $_SESSION['user_id'];

// Get search query and filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

// Check if this is the index page (show only 10 coupons)
$isIndexPage = basename($_SERVER['PHP_SELF']) === 'index.php';

// Update expired coupons to "expired" status
$update_sql = "UPDATE coupons SET c_status = 'expired' WHERE expiration_date < CURDATE() AND c_status = 'active'";
mysqli_query($connection, $update_sql);

// Build the SQL query with search and filter functionality for user's coupons only
$where_clause = "WHERE user_id = $user_id"; // Only show coupons posted by this user
$params = [];
$types = "";

if (!empty($search)) {
    $where_clause .= " AND (description LIKE ? OR code LIKE ? OR terms LIKE ?)";
    $search_param = "%$search%";
    $params = array($search_param, $search_param, $search_param);
    $types = "sss";
}

if (!empty($filter)) {
    if ($filter == 'close_to_expire') {
        $where_clause .= " AND expiration_date <= DATE_ADD(NOW(), INTERVAL 7 DAY)";
    } elseif ($filter == 'long_expire') {
        $where_clause .= " AND expiration_date > DATE_ADD(NOW(), INTERVAL 30 DAY)";
    }
}

// Add condition to only show active and used coupons (exclude expired)
$where_clause .= " AND c_status IN ('active', 'used')";

$order_clause = "ORDER BY is_top DESC, created_at DESC";

// Add limit for index page
$limit_clause = "";
if ($isIndexPage) {
    $limit_clause = "LIMIT 10";
}

if (!empty($search) || !empty($filter) || $isIndexPage) {
    $sql = "SELECT id, description, company_name, link, code, terms, expiration_date, created_at, is_top, c_status FROM coupons $where_clause $order_clause $limit_clause";
    $stmt = $connection->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $sql = "SELECT id, description, company_name, link, code, terms, expiration_date, created_at, is_top, c_status FROM coupons $where_clause $order_clause $limit_clause";
    $result = mysqli_query($connection, $sql);
}

$coupons = [];

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Only fetch coupon code if user is logged in and coupon is active
        if ($isLoggedIn && $row['c_status'] === 'active') {
            $row['code'] = $row['code']; // This will be fetched in the query below
        } else {
            $row['code'] = null; // Don't include the code for non-logged-in users or used/expired coupons
        }
        $coupons[] = $row;
    }
}

// Re-run the query for logged-in users to get codes for active coupons
if ($isLoggedIn && (!empty($search) || !empty($filter) || $isIndexPage)) {
    $sql = "SELECT id, description, company_name, link, code, terms, expiration_date, created_at, is_top, c_status FROM coupons $where_clause $order_clause $limit_clause";
    $stmt = $connection->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
} elseif ($isLoggedIn) {
    $sql = "SELECT id, description, company_name, link, code, terms, expiration_date, created_at, is_top, c_status FROM coupons $where_clause $order_clause $limit_clause";
    $result = mysqli_query($connection, $sql);
}

if ($isLoggedIn) {
    $coupons = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            // Only fetch code for active coupons
            if ($row['c_status'] === 'active') {
                $row['code'] = $row['code'];
            } else {
                $row['code'] = null;
            }
            $coupons[] = $row;
        }
    }
}

// Handle coupon deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_coupon'])) {
    $coupon_id = $_POST['coupon_id'];
    
    // Verify that the coupon belongs to the logged-in user
    $stmt = $connection->prepare("SELECT id FROM coupons WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $coupon_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        // Delete the coupon
        $stmt = $connection->prepare("DELETE FROM coupons WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $coupon_id, $user_id);
        
        if ($stmt->execute()) {
            $success_message = "Coupon deleted successfully!";
            // Refresh the coupons list
            $stmt = $connection->prepare("SELECT id, description, company_name, link, code, terms, expiration_date, created_at, is_top, c_status FROM coupons WHERE user_id = ? $where_clause $order_clause $limit_clause");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $coupons = [];
            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    if ($row['c_status'] === 'active') {
                        $row['code'] = $row['code'];
                    } else {
                        $row['code'] = null;
                    }
                    $coupons[] = $row;
                }
            }
        } else {
            $errors[] = "Failed to delete coupon.";
        }
        
        $stmt->close();
    } else {
        $errors[] = "Invalid coupon or unauthorized access.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isIndexPage ? 'My Coupons' : 'All My Coupons'; ?> - <?php echo $websiteName; ?></title>
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

h1 {
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 3rem;
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    letter-spacing: -0.5px;
}

.coupons-container {
    padding: 20px;
    position: relative;
    z-index: 2;
}

.search-section {
    background: var(--card-bg);
    backdrop-filter: blur(10px);
    border-radius: 18px;
    padding: 24px;
    margin-bottom: 30px;
    box-shadow: var(--shadow-md);
    border: 1px solid var(--card-border);
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
    padding: 12px 18px;
    border-radius: 12px;
    transition: all 0.25s ease;
    font-size: 1rem;
}

.search-input:focus {
    outline: none;
    border-color: rgba(102, 126, 234, 0.7);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
    background: rgba(255, 255, 255, 0.08);
}

.search-input::placeholder {
    color: rgba(255, 255, 255, 0.5);
}

.search-btn {
    background: var(--success-gradient);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 12px;
    font-weight: 700;
    transition: all 0.25s ease;
    box-shadow: 0 4px 15px rgba(17, 153, 142, 0.3);
    display: flex;
    align-items: center;
    gap: 8px;
}

.search-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(17, 153, 142, 0.4);
}

.filter-section {
    display: flex;
    gap: 10px;
    margin-top: 16px;
    flex-wrap: wrap;
}

.filter-btn {
    background: rgba(255, 255, 255, 0.06);
    color: var(--text-primary);
    border: 1px solid rgba(255, 255, 255, 0.15);
    padding: 10px 18px;
    border-radius: 18px;
    cursor: pointer;
    transition: all 0.25s ease;
    font-size: 0.9rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
}

.filter-btn:hover {
    background: rgba(255, 255, 255, 0.12);
    border-color: rgba(102, 126, 234, 0.6);
}

.filter-btn.active {
    background: var(--primary-gradient);
    border-color: transparent;
    color: white;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.coupon-card {
    background: var(--card-bg);
    backdrop-filter: blur(10px);
    border-radius: 24px;
    padding: 28px;
    position: relative;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    height: 100%;
    border: 1px solid var(--card-border);
    box-shadow: var(--shadow-md);
    overflow: hidden;
}

.coupon-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--primary-gradient);
    opacity: 0;
    transition: opacity 0.3s;
}

.coupon-card:hover::before {
    opacity: 1;
}

.coupon-card:hover {
    transform: translateY(-12px) scale(1.02);
    box-shadow: var(--shadow-lg), var(--shadow-glow);
    border-color: rgba(102, 126, 234, 0.6);
}

.coupon-card.top-coupon {
    border: 2px solid rgba(255, 215, 0, 0.5);
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.05) 0%, var(--card-bg) 100%);
}

.coupon-card.top-coupon::before {
    background: linear-gradient(90deg, #ffd700 0%, #ffed4e 100%);
    opacity: 1;
}

.top-badge {
    position: absolute;
    top: 16px;
    right: 16px;
    background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
    color: #000;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 800;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    box-shadow: 0 4px 12px rgba(255, 215, 0, 0.4);
    z-index: 2;
}

.status-badge {
    position: absolute;
    top: 16px;
    left: 16px;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    z-index: 2;
    box-shadow: var(--shadow-sm);
}

.used-badge {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    color: white;
}

.expired-badge {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
}

.coupon-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 12px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.coupon-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
    line-height: 1.3;
    padding-right: 80px;
}

.coupon-category {
    background: rgba(102, 126, 234, 0.2);
    color: #cbd5ff;
    padding: 6px 14px;
    border-radius: 16px;
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
}

.coupon-description {
    margin: 16px 0;
    color: var(--text-secondary);
    line-height: 1.6;
    font-size: 1rem;
}

.coupon-link {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin: 16px 0;
    padding: 14px 20px;
    text-align: center;
    border-radius: 12px;
    background: rgba(102, 126, 234, 0.15);
    color: #667eea;
    text-decoration: none;
    transition: all 0.3s;
    font-weight: 600;
    border: 1px solid rgba(102, 126, 234, 0.3);
}

.coupon-link:hover {
    background: var(--primary-gradient);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    border-color: transparent;
}

.coupon-code-container {
    background: rgba(255, 255, 255, 0.05);
    border: 1px dashed rgba(255, 255, 255, 0.12);
    border-radius: 14px;
    padding: 16px;
    margin: 16px 0;
    text-align: center;
}

.coupon-code {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    padding: 16px 32px;
    border-radius: 16px;
    background: var(--success-gradient);
    color: white;
    font-weight: 700;
    font-size: 1.1rem;
    letter-spacing: 2px;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 4px 15px rgba(17, 153, 142, 0.3);
    border: none;
}

.coupon-code:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 20px rgba(17, 153, 142, 0.4);
}

.login-required {
    background: var(--warning-gradient) !important;
    box-shadow: 0 4px 15px rgba(245, 87, 108, 0.3);
    letter-spacing: 1px;
    font-size: 1rem;
}

.login-required:hover {
    box-shadow: 0 6px 20px rgba(245, 87, 108, 0.4);
}

.terms-section {
    background: rgba(102, 126, 234, 0.1);
    border-left: 4px solid #667eea;
    padding: 14px 18px;
    border-radius: 12px;
    font-size: 0.9rem;
    margin: 16px 0;
    color: var(--text-secondary);
    line-height: 1.5;
}

.coupon-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.85rem;
    color: var(--text-secondary);
    margin-top: 20px;
    padding-top: 16px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.coupon-meta span {
    display: flex;
    align-items: center;
    gap: 6px;
}

.coupon-expiration {
    color: #ffb3c1;
    font-weight: 700;
}

.coupon-date {
    color: var(--text-secondary);
}

.action-buttons {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.delete-btn {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 12px;
    font-weight: 700;
    transition: all 0.3s ease;
    cursor: pointer;
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
}

.delete-btn:hover {
    background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(220, 53, 69, 0.4);
}

.no-coupons {
    text-align: center;
    padding: 80px 20px;
    color: var(--text-secondary);
}

.no-coupons i {
    font-size: 4rem;
    margin-bottom: 24px;
    opacity: 0.6;
    color: var(--text-secondary);
}

.no-coupons h3 {
    margin-bottom: 12px;
    color: var(--text-primary);
    font-size: 2rem;
    font-weight: 700;
}

.no-coupons p {
    margin-bottom: 24px;
    font-size: 1.1rem;
}

.no-coupons .btn-success {
    background: var(--success-gradient);
    border: none;
    padding: 14px 28px;
    border-radius: 12px;
    font-weight: 700;
    color: white;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 6px 20px rgba(17, 153, 142, 0.3);
    transition: all 0.3s ease;
}

.no-coupons .btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 28px rgba(17, 153, 142, 0.4);
    color: white;
}

.search-results {
    color: var(--text-secondary);
    font-size: 0.9rem;
    margin-top: 16px;
    text-align: center;
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
    color: white;
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

/* Responsive Design */
@media (max-width: 768px) {
    body {
        padding-top: 80px;
    }
    
    h1 {
        font-size: 2rem;
        margin-bottom: 2rem;
    }
    
    .coupons-container {
        padding: 16px;
    }
    
    .search-form {
        flex-direction: column;
    }
    
    .search-input {
        width: 100%;
    }
    
    .filter-section {
        flex-direction: column;
    }
    
    .filter-btn {
        width: 100%;
        justify-content: center;
    }
    
    .coupon-card {
        padding: 20px;
    }
    
    .coupon-title {
        font-size: 1.25rem;
        padding-right: 60px;
    }
    
    .coupon-meta {
        flex-direction: column;
        gap: 8px;
        align-items: flex-start;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .delete-btn {
        width: 100%;
    }
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
                <h1 class="mb-0">
                    <?php echo $isIndexPage ? 'My Coupons' : 'All My Coupons'; ?>
                </h1>
                <?php if ($isIndexPage && count($coupons) >= 10): ?>
                    <a href="all_coupons.php" class="see-all-btn">
                        <i class="bi bi-arrow-right-circle"></i>See All My Coupons
                    </a>
                <?php endif; ?>
            </div>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($errors) && !empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <div><i class="bi bi-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$isIndexPage): ?>
                <!-- Search Section (only for search page) -->
                <div class="search-section">
                    <form method="GET" action="" class="search-form">
                        <input type="text" name="search" class="search-input" placeholder="Search your coupons by description, code, or terms..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="search-btn">
                            <i class="bi bi-search"></i>Search
                        </button>
                    </form>
                    
                    <!-- Filter Section -->
                    <div class="filter-section">
                        <button type="button" class="filter-btn <?php echo $filter == 'close_to_expire' ? 'active' : ''; ?>" 
                                onclick="applyFilter('close_to_expire')">
                            <i class="bi bi-clock"></i> Close to Expire
                        </button>
                        <button type="button" class="filter-btn <?php echo $filter == 'long_expire' ? 'active' : ''; ?>" 
                                onclick="applyFilter('long_expire')">
                            <i class="bi bi-calendar-check"></i> Long to Expire
                        </button>
                        <?php if (!empty($filter) || !empty($search)): ?>
                            <button type="button" class="filter-btn" onclick="clearFilters()">
                                <i class="bi bi-x-circle"></i> Clear
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
            
            <?php if (!empty($coupons)): ?>
                <div class="row">
                    <?php foreach ($coupons as $coupon): ?>
                        <div class="col-12 col-md-6 col-lg-4 mb-4">
                            <div class="coupon-card <?php echo $coupon['is_top'] ? 'top-coupon' : ''; ?>">
                                <?php if ($coupon['is_top']): ?>
                                    <div class="top-badge">TOP</div>
                                <?php endif; ?>
                                
                                <?php if ($coupon['c_status'] === 'used'): ?>
                                    <div class="status-badge used-badge">USED</div>
                                <?php elseif ($coupon['c_status'] === 'expired'): ?>
                                    <div class="status-badge expired-badge">EXPIRED</div>
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
                                        <i class="bi bi-shop"></i> Visit Store
                                    </a>
                                <?php endif; ?>
                                
                                <div class="coupon-code-container">
                                    <?php if ($isLoggedIn && !empty($coupon['code']) && $coupon['c_status'] === 'active'): ?>
                                        <div class="coupon-code" onclick="copyToClipboard('<?php echo htmlspecialchars($coupon['code']); ?>')">
                                            <?php echo htmlspecialchars($coupon['code']); ?>
                                        </div>
                                    <?php elseif ($coupon['c_status'] === 'used'): ?>
                                        <div class="coupon-code" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
                                            <i class="bi bi-check-circle"></i> USED
                                        </div>
                                    <?php else: ?>
                                        <div class="coupon-code login-required">
                                            <i class="bi bi-lock"></i> Login to view code
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (!empty($coupon['terms'])): ?>
                                    <div class="terms-section">
                                        <i class="bi bi-info-circle me-1"></i><strong>Terms:</strong> <?php echo htmlspecialchars($coupon['terms']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="action-buttons">
                                    <button class="delete-btn" onclick="confirmDelete(<?php echo $coupon['id']; ?>, '<?php echo addslashes(htmlspecialchars($coupon['description'])); ?>')">
                                        <i class="bi bi-trash"></i>Delete
                                    </button>
                                </div>
                                
                                <div class="coupon-meta">
                                    <?php if (!empty($coupon['expiration_date'])): ?>
                                        <span class="coupon-expiration">
                                            <i class="bi bi-clock"></i>
                                            <?php echo date('M d', strtotime($coupon['expiration_date'])); ?>
                                        </span>
                                    <?php endif; ?>
                                    <span class="coupon-date">
                                        <i class="bi bi-calendar"></i>
                                        <?php echo date('M d, Y', strtotime($coupon['created_at'] ?? date('Y-m-d'))); ?>
                                    </span>
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
                            You haven't posted any coupons yet!
                        <?php endif; ?>
                    </p>
                    <a href="add_coupon.php" class="btn btn-success">
                        <i class="bi bi-plus-circle"></i>Add Your First Coupon
                    </a>
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
                    showToast("Coupon code copied to clipboard!");
                }).catch(function(err) {
                    console.error('Failed to copy: ', err);
                    showToast("Failed to copy code", "error");
                });
            } else {
                const textArea = document.createElement("textarea");
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showToast("Coupon code copied!");
            }
        }
        
        function showToast(message, type = "success") {
            const toast = document.createElement('div');
            toast.className = 'alert ' + (type === 'success' ? 'alert-success' : 'alert-danger');
            toast.style.position = 'fixed';
            toast.style.bottom = '30px';
            toast.style.right = '30px';
            toast.style.zIndex = '9999';
            toast.style.minWidth = '300px';
            toast.innerHTML = `<i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>${message}`;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.3s';
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 300);
            }, 3000);
        }
        
        function confirmDelete(couponId, couponDescription) {
            const confirmed = confirm(`Are you sure you want to delete this coupon?\n\n"${couponDescription}"\n\nThis action cannot be undone.`);
            if (confirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                const couponIdInput = document.createElement('input');
                couponIdInput.type = 'hidden';
                couponIdInput.name = 'coupon_id';
                couponIdInput.value = couponId;
                
                const deleteInput = document.createElement('input');
                deleteInput.type = 'hidden';
                deleteInput.name = 'delete_coupon';
                deleteInput.value = '1';
                
                form.appendChild(couponIdInput);
                form.appendChild(deleteInput);
                
                document.body.appendChild(form);
                form.submit();
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
