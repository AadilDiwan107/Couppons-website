<?php
$websiteName = "Coupon.is-great.org";
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'includes/db.php';

$user_id = $_SESSION['user_id'];

/* Fetch user info */
$stmt = $connection->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

/* Fetch used coupons */
$stmt = $connection->prepare("
    SELECT description, link, code, terms, expiration_date, created_at, is_top 
    FROM coupons 
    WHERE user_id = ? AND c_status = 'used'
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$coupons = [];
while ($row = $result->fetch_assoc()) {
    $coupons[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Used Coupons - <?php echo $websiteName; ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

<style>
:root{
    --primary-gradient: linear-gradient(135deg,#667eea,#764ba2);
    --success-gradient: linear-gradient(135deg,#11998e,#38ef7d);
    --warning-gradient: linear-gradient(135deg,#f093fb,#f5576c);
    --dark-bg:#0a0e27;
    --card-bg:rgba(20,25,47,.95);
    --card-border:rgba(102,126,234,.3);
    --text-primary:#fff;
    --text-secondary:rgba(255,255,255,.8);
}

body{
    background:linear-gradient(135deg,#0a0e27,#1a1f3a,#2d1b4e);
    background-attachment:fixed;
    color:var(--text-primary);
    padding-top:90px;
    font-family:'Segoe UI',sans-serif;
}

h1{
    font-weight:800;
    background:var(--primary-gradient);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
    text-align:center;
    margin-bottom:30px;
}

.user-box{
    background:var(--card-bg);
    border:1px solid var(--card-border);
    border-radius:20px;
    padding:24px;
    margin-bottom:40px;
    box-shadow:0 10px 30px rgba(0,0,0,.3);
}

.user-box h4{
    font-weight:700;
    margin-bottom:15px;
}

.user-box .info{
    display:flex;
    justify-content:space-between;
    padding:6px 0;
    color:var(--text-secondary);
}

.coupon-card{
    background:var(--card-bg);
    border-radius:24px;
    padding:26px;
    position:relative;
    border:1px solid var(--card-border);
    box-shadow:0 8px 28px rgba(0,0,0,.35);
    transition:.35s;
    height:100%;
}

.coupon-card:hover{
    transform:translateY(-10px) scale(1.02);
    box-shadow:0 14px 40px rgba(102,126,234,.4);
}

.top-coupon{
    border:2px solid gold;
}

.top-badge{
    position:absolute;
    top:15px;
    right:15px;
    background:linear-gradient(135deg,#ffd700,#ffed4e);
    color:#000;
    padding:6px 14px;
    border-radius:20px;
    font-size:.7rem;
    font-weight:800;
}

.used-badge{
    position:absolute;
    top:15px;
    left:15px;
    background:linear-gradient(135deg,#17a2b8,#138496);
    padding:6px 14px;
    border-radius:20px;
    font-size:.7rem;
    font-weight:700;
}

.coupon-title{
    font-size:1.4rem;
    font-weight:700;
    margin-bottom:12px;
    padding-right:80px;
}

.coupon-desc{
    color:var(--text-secondary);
    font-size:.95rem;
}

.coupon-terms{
    background:rgba(102,126,234,.1);
    border-left:4px solid #667eea;
    padding:12px 16px;
    border-radius:12px;
    font-size:.9rem;
    margin:15px 0;
}

.coupon-link{
    display:block;
    margin:15px 0;
    padding:12px;
    text-align:center;
    border-radius:12px;
    background:rgba(102,126,234,.15);
    color:#667eea;
    font-weight:600;
    text-decoration:none;
    transition:.3s;
}

.coupon-link:hover{
    background:var(--primary-gradient);
    color:#fff;
}

.coupon-code-box{
    text-align:center;
    margin:18px 0;
}

.coupon-code{
    display:inline-block;
    padding:14px 30px;
    border-radius:16px;
    background:var(--success-gradient);
    font-weight:700;
    letter-spacing:2px;
    cursor:pointer;
    box-shadow:0 4px 16px rgba(17,153,142,.4);
}

.coupon-meta{
    display:flex;
    justify-content:space-between;
    font-size:.85rem;
    color:var(--text-secondary);
    border-top:1px solid rgba(255,255,255,.1);
    padding-top:14px;
    margin-top:18px;
}

.no-data{
    text-align:center;
    padding:80px 20px;
    opacity:.8;
}
</style>
</head>

<body>

<?php include 'includes/navbar.php'; ?>

<div class="container">

<h1>🔥 My Used Coupons</h1>

<div class="user-box">
    <h4><i class="bi bi-person-circle me-2"></i>User Info</h4>
    <div class="info"><span>Username</span><span><?php echo htmlspecialchars($user['username']); ?></span></div>
    <div class="info"><span>Email</span><span><?php echo htmlspecialchars($user['email']); ?></span></div>
    <div class="info"><span>Total Used</span><span><?php echo count($coupons); ?></span></div>
</div>

<?php if($coupons): ?>
<div class="row">
<?php foreach($coupons as $coupon): ?>
<div class="col-md-6 col-lg-4 mb-4">
<div class="coupon-card <?php echo $coupon['is_top']?'top-coupon':''; ?>">

<?php if($coupon['is_top']): ?><div class="top-badge">TOP</div><?php endif; ?>
<div class="used-badge">USED</div>

<h5 class="coupon-title">Special Offer</h5>
<p class="coupon-desc"><?php echo htmlspecialchars($coupon['description']); ?></p>

<?php if($coupon['terms']): ?>
<div class="coupon-terms">
<i class="bi bi-info-circle"></i>
<?php echo htmlspecialchars($coupon['terms']); ?>
</div>
<?php endif; ?>

<?php if($coupon['link']): ?>
<a href="<?php echo htmlspecialchars($coupon['link']); ?>" target="_blank" class="coupon-link">
<i class="bi bi-shop"></i> Visit Store
</a>
<?php endif; ?>

<div class="coupon-code-box">
<div class="coupon-code" onclick="copyCode('<?php echo htmlspecialchars($coupon['code']); ?>')">
<?php echo htmlspecialchars($coupon['code']); ?>
</div>
</div>

<div class="coupon-meta">
<span><i class="bi bi-calendar"></i> <?php echo date('M d, Y', strtotime($coupon['created_at'])); ?></span>
<?php if($coupon['expiration_date']): ?>
<span><i class="bi bi-clock"></i> <?php echo date('M d', strtotime($coupon['expiration_date'])); ?></span>
<?php endif; ?>
</div>

</div>
</div>
<?php endforeach; ?>
</div>

<?php else: ?>
<div class="no-data">
<i class="bi bi-emoji-frown fs-1"></i>
<h4>No used coupons yet</h4>
<p>Once someone uses your coupon, it will appear here.</p>
</div>
<?php endif; ?>

</div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    
<script>
function copyCode(code){
    navigator.clipboard.writeText(code).then(()=>{
        alert("Coupon copied!");
    });
}
</script>

</body>
</html>
