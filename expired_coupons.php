<?php
$websiteName = "Coupon.is-great.org";
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'includes/db.php';

$user_id = $_SESSION['user_id'];

// Fetch user
$stmt = $connection->prepare("SELECT username FROM users WHERE id=?");
$stmt->bind_param("i",$user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Auto expire coupons logic
$upd = $connection->prepare(
    "UPDATE coupons SET c_status='expired'
     WHERE expiration_date < CURDATE()
     AND c_status='active'
     AND user_id=?"
);
$upd->bind_param("i",$user_id);
$upd->execute();
$upd->close();

// Fetch expired coupons
$stmt = $connection->prepare(
    "SELECT * FROM coupons
     WHERE user_id=? AND c_status='expired'
     ORDER BY created_at DESC"
);
$stmt->bind_param("i",$user_id);
$stmt->execute();
$coupons = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Expired Coupons - <?php echo $websiteName; ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

<style>
/* Keeping styling consistent with Index.php */
:root {
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --expired-gradient: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
    --dark-bg: #0a0e27;
    --card-bg: rgba(20, 25, 47, 0.95);
    --card-border: rgba(102, 126, 234, 0.3);
    --text-primary: #ffffff;
    --text-secondary: rgba(255, 255, 255, 0.8);
    --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.2);
}

body {
    background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 50%, #2d1b4e 100%);
    background-attachment: fixed;
    color: var(--text-primary);
    padding-top: 100px;
    min-height: 100vh;
    font-family: 'Segoe UI', sans-serif;
}

.page-header {
    text-align: center;
    margin-bottom: 3rem;
}

.page-header h2 {
    font-weight: 800;
    background: var(--expired-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    display: inline-block;
}

.stats-card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 30px;
    text-align: center;
}

.coupon-card {
    background: var(--card-bg);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 25px;
    position: relative;
    border: 1px solid rgba(255, 75, 43, 0.3); /* Red border for expired */
    box-shadow: var(--shadow-md);
    height: 100%;
    opacity: 0.85; /* Slightly faded */
    transition: all 0.3s;
}

.coupon-card:hover {
    opacity: 1;
    transform: translateY(-5px);
    border-color: #ff4b2b;
}

.status-badge {
    position: absolute;
    top: 16px;
    right: 16px;
    background: var(--expired-gradient);
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 1px;
    box-shadow: 0 4px 12px rgba(255, 75, 43, 0.3);
}

.coupon-title {
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 10px;
    color: var(--text-primary);
}

.coupon-code {
    background: rgba(255, 255, 255, 0.1);
    padding: 10px 20px;
    border-radius: 8px;
    font-family: monospace;
    letter-spacing: 1px;
    text-decoration: line-through;
    color: #ff4b2b;
    margin: 15px 0;
    display: inline-block;
}

.meta-info {
    font-size: 0.85rem;
    color: var(--text-secondary);
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding-top: 15px;
    margin-top: 15px;
    display: flex;
    justify-content: space-between;
}

.no-data {
    text-align: center;
    padding: 60px;
    color: var(--text-secondary);
}

.btn-back {
    background: rgba(255,255,255,0.1);
    color: white;
    border: 1px solid rgba(255,255,255,0.2);
    padding: 10px 24px;
    border-radius: 12px;
    transition: 0.3s;
    text-decoration: none;
}
.btn-back:hover {
    background: white;
    color: #000;
}
</style>
</head>

<body>

<?php include 'includes/navbar.php'; ?>

<div class="container">
    <div class="page-header">
        <h2><i class="bi bi-hourglass-bottom"></i> Expired Coupons</h2>
        <p class="text-secondary">Coupons that have passed their valid date</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="stats-card">
                <h5 class="m-0">
                    User: <span class="text-white"><?php echo htmlspecialchars($user['username']); ?></span>
                    <span class="mx-2">|</span> 
                    Total Expired: <strong style="color: #ff4b2b"><?php echo count($coupons); ?></strong>
                </h5>
            </div>
        </div>
    </div>
    
     <div class="text-center my-5">
        <a href="index.php" class="btn-back me-2"><i class="bi bi-house"></i> Home</a>
        <a href="my_coupons.php" class="btn-back"><i class="bi bi-tags"></i> My Active Coupons</a>
    </div>

    <?php if($coupons): ?>
    <div class="row g-4">
        <?php foreach($coupons as $coupon): ?>
        <div class="col-md-6 col-lg-4">
            <div class="coupon-card">
                <div class="status-badge">EXPIRED</div>

                <h5 class="coupon-title">
                    <?php echo htmlspecialchars($coupon['company_name'] ?: 'Offer'); ?>
                </h5>
                <p class="text-secondary small mb-2">
                    <?php echo htmlspecialchars($coupon['description']); ?>
                </p>

                <div class="text-center">
                    <div class="coupon-code">
                        <?php echo htmlspecialchars($coupon['code']); ?>
                    </div>
                </div>

                <div class="meta-info">
                    <span><i class="bi bi-calendar-x"></i> Expired: 
                        <?php echo date('M d, Y', strtotime($coupon['expiration_date'])); ?>
                    </span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="no-data">
        <i class="bi bi-emoji-smile fs-1 mb-3 d-block"></i>
        <h4>No expired coupons found</h4>
        <p>All your coupons are currently active!</p>
    </div>
    <?php endif; ?>

   

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
