<?php
$websiteName = "Coupon.is-great.org";
session_start();
include 'includes/db.php';

$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['username']);

/* Auto-expire coupons */
mysqli_query(
    $connection,
    "UPDATE coupons 
     SET c_status='expired' 
     WHERE expiration_date < CURDATE() 
     AND c_status='active'"
);

/* Fetch coupons */
$sql = "SELECT id, description, company_name, link, code, terms,
               expiration_date, created_at, is_top, c_status
        FROM coupons
        WHERE c_status IN ('active','used')
        ORDER BY is_top DESC, created_at DESC";

$result = mysqli_query($connection, $sql);
$coupons = [];

while ($row = mysqli_fetch_assoc($result)) {
    if (!$isLoggedIn || $row['c_status'] !== 'active') {
        $row['code'] = null;
    }
    $coupons[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo $websiteName; ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

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

h2 {
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 3rem;
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    text-align: center;
    letter-spacing: -0.5px;
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

.top-coupon {
    border: 2px solid rgba(255, 215, 0, 0.5);
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.05) 0%, var(--card-bg) 100%);
}

.top-coupon::before {
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

.coupon-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 12px;
    color: var(--text-primary);
    line-height: 1.3;
    padding-right: 80px;
}

.coupon-desc {
    font-size: 1rem;
    color: var(--text-secondary);
    margin-bottom: 16px;
    line-height: 1.6;
}

.coupon-terms {
    background: rgba(102, 126, 234, 0.1);
    border-left: 4px solid #667eea;
    padding: 14px 18px;
    border-radius: 12px;
    font-size: 0.9rem;
    margin: 16px 0;
    color: var(--text-secondary);
    line-height: 1.5;
}

.coupon-terms i {
    color: #667eea;
    margin-right: 8px;
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

.coupon-code-box {
    margin: 20px 0;
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
    position: relative;
    overflow: hidden;
}

.coupon-code::before {
    content: 'Click to Copy';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0);
    font-size: 0.7rem;
    opacity: 0;
    transition: all 0.3s;
}

.coupon-code:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 20px rgba(17, 153, 142, 0.4);
}

.coupon-code:active {
    transform: scale(0.98);
}

.login-required {
    background: var(--warning-gradient);
    box-shadow: 0 4px 15px rgba(245, 87, 108, 0.3);
    letter-spacing: 1px;
    font-size: 1rem;
}

.login-required:hover {
    box-shadow: 0 6px 20px rgba(245, 87, 108, 0.4);
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

.coupon-meta i {
    font-size: 0.9rem;
}

.action-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 1.5rem;
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

/* Toast Notification */
.toast-notification {
    position: fixed;
    bottom: 30px;
    right: 30px;
    background: var(--success-gradient);
    color: white;
    padding: 16px 24px;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
    z-index: 9999;
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}

.toast-notification.show {
    opacity: 1;
    transform: translateY(0);
}

.toast-notification i {
    font-size: 1.2rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    body {
        padding-top: 80px;
    }
    
    h2 {
        font-size: 2rem;
        margin-bottom: 2rem;
    }
    
    .coupon-card {
        padding: 20px;
    }
    
    .coupon-title {
        font-size: 1.25rem;
        padding-right: 60px;
    }
    
    .coupon-code {
        padding: 14px 24px;
        font-size: 1rem;
    }
    
    .coupon-meta {
        flex-direction: column;
        gap: 8px;
        align-items: flex-start;
    }
    
    .action-bar {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .toast-notification {
        bottom: 20px;
        right: 20px;
        left: 20px;
    }
}
</style>
</head>

<body>

<?php include 'includes/navbar.php'; ?>

<div class="container">
<div class="action-bar">
<h2 class="mb-0">🔥 Latest Coupons</h2>
<a href="search_coupons.php" class="see-all-btn">
<i class="bi bi-grid"></i> See all coupons
</a>
</div>

<div class="row">
<?php foreach($coupons as $coupon): ?>
<div class="col-md-6 col-lg-4 mb-4">
<div class="coupon-card <?php echo $coupon['is_top']?'top-coupon':''; ?>">

<?php if($coupon['is_top']): ?>
<div class="top-badge">TOP</div>
<?php endif; ?>

<?php if($coupon['c_status']=='used'): ?>
<div class="status-badge used-badge">USED</div>
<?php endif; ?>

<h5 class="coupon-title">
<?php echo htmlspecialchars($coupon['company_name'] ?: 'Special Offer'); ?>
</h5>

<p class="coupon-desc">
<?php echo htmlspecialchars($coupon['description']); ?>
</p>

<!-- ✅ TERMS SECTION -->
<?php if(!empty($coupon['terms'])): ?>
<div class="coupon-terms">
<i class="bi bi-info-circle me-1"></i>
<?php echo htmlspecialchars($coupon['terms']); ?>
</div>
<?php endif; ?>

<?php if($coupon['link']): ?>
<a href="<?php echo htmlspecialchars($coupon['link']); ?>" target="_blank" class="coupon-link">
<i class="bi bi-shop me-1"></i> Visit Store
</a>
<?php endif; ?>

<div class="coupon-code-box">
<?php if($isLoggedIn && $coupon['c_status']=='active'): ?>
<div class="coupon-code" onclick="copyCode('<?php echo $coupon['code']; ?>')">
<?php echo htmlspecialchars($coupon['code']); ?>
</div>
<?php else: ?>
<div class="coupon-code login-required" onclick="location.href='login.php'">
<i class="bi bi-lock"></i> Login to view
</div>
<?php endif; ?>
</div>

<div class="coupon-meta">
<span><i class="bi bi-calendar"></i>
<?php echo date('M d, Y', strtotime($coupon['created_at'])); ?>
</span>
<?php if($coupon['expiration_date']): ?>
<span><i class="bi bi-clock"></i>
<?php echo date('M d', strtotime($coupon['expiration_date'])); ?>
</span>
<?php endif; ?>
</div>

</div>
</div>
<?php endforeach; ?>
</div>
</div>

<script>
function copyCode(code){
    navigator.clipboard.writeText(code).then(()=>{
        showToast("Coupon code copied to clipboard!");
    }).catch(()=>{
        showToast("Failed to copy code", "error");
    });
}

function showToast(message, type = "success"){
    const toast = document.createElement('div');
    toast.className = 'toast-notification';
    toast.innerHTML = `
        <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    document.body.appendChild(toast);
    
    setTimeout(()=>{
        toast.classList.add('show');
    }, 10);
    
    setTimeout(()=>{
        toast.classList.remove('show');
        setTimeout(()=>{
            document.body.removeChild(toast);
        }, 300);
    }, 3000);
}
</script>

</body>
</html>
