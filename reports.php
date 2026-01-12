<?php
$websiteName = "Coupon.is-great.org";
session_start();

/* OPTIONAL:
   If this page should be PUBLIC, comment this block
   If ADMIN ONLY, keep it
*/
// if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
//     header("Location: login.php");
//     exit();
// }

include 'includes/db.php';

$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? '';
$isIndexPage = basename($_SERVER['PHP_SELF']) === 'index.php';

$where = [];
$params = [];
$types = "";

if ($search) {
    $where[] = "(description LIKE ? OR code LIKE ? OR terms LIKE ? OR company_name LIKE ?)";
    $searchParam = "%$search%";
    $params = [$searchParam,$searchParam,$searchParam,$searchParam];
    $types = "ssss";
}

if ($filter) {
    if ($filter === 'active') $where[] = "c_status='active'";
    if ($filter === 'used') $where[] = "c_status='used'";
    if ($filter === 'expired') $where[] = "c_status='expired'";
    if ($filter === 'close_to_expire') {
        $where[] = "expiration_date <= DATE_ADD(NOW(), INTERVAL 7 DAY) AND c_status='active'";
    }
}

if ($isIndexPage) {
    $where[] = "c_status IN ('active','used')";
}

$whereSQL = $where ? 'WHERE '.implode(' AND ',$where) : '';
$orderSQL = "ORDER BY is_top DESC, created_at DESC";
$limitSQL = $isIndexPage ? "LIMIT 10" : "";

$sql = "SELECT * FROM coupons $whereSQL $orderSQL $limitSQL";

$stmt = $connection->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$coupons = [];
while ($row = $result->fetch_assoc()) {
    if (!isset($_SESSION['user_id']) || $row['c_status'] !== 'active') {
        $row['code'] = null;
    }
    $coupons[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Coupons - <?php echo $websiteName; ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

<style>
:root{
    --nav-height:70px;
}
body{
    margin:0;
    background:linear-gradient(-45deg,#ee7752,#e73c7e,#23a6d5,#23d5ab);
    background-size:400% 400%;
    animation:gradientBG 15s ease infinite;
    color:#fff;
}
@keyframes gradientBG{
    0%{background-position:0% 50%}
    50%{background-position:100% 50%}
    100%{background-position:0% 50%}
}

/* CONTENT OFFSET FOR NAVBAR */
.page-wrapper{
    padding-top:var(--nav-height);
}

/* CARDS */
.coupon-card{
    background:rgba(255,255,255,.12);
    backdrop-filter:blur(12px);
    border-radius:16px;
    border:1px solid rgba(255,255,255,.25);
    box-shadow:0 10px 30px rgba(0,0,0,.25);
    transition:.3s;
}
.coupon-card:hover{
    transform:translateY(-8px) scale(1.02);
}
.top-badge{
    position:absolute;
    top:-10px;
    right:-10px;
    background:#ffd700;
    color:#333;
    padding:5px 15px;
    border-radius:20px;
    font-weight:700;
}
.coupon-code{
    background:#28a745;
    padding:12px 22px;
    border-radius:30px;
    font-weight:700;
    cursor:pointer;
    display:inline-block;
}
.login-required{
    background:#ff6b6b;
}
.terms{
    background:rgba(255,255,255,.08);
    border-left:4px solid #4ecdc4;
    padding:10px;
    border-radius:8px;
}
</style>
</head>

<body>

<!-- ✅ EXTERNAL NAVBAR -->
<?php include 'includes/admin_nav.php'; ?>

<div class="page-wrapper">
<div class="container py-4">

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold">Available Coupons</h2>
    <?php if ($isIndexPage && count($coupons) >= 10): ?>
        <a href="search_coupons.php" class="btn btn-success">
            See All Coupons
        </a>
    <?php endif; ?>
</div>

<div class="row">
<?php if($coupons): foreach($coupons as $c): ?>
<div class="col-md-6 col-lg-4 mb-4">
<div class="coupon-card p-4 position-relative h-100">

<?php if($c['is_top']): ?>
<div class="top-badge">TOP</div>
<?php endif; ?>

<h5 class="fw-bold"><?php echo htmlspecialchars($c['company_name'] ?: 'Special Deal'); ?></h5>
<p><?php echo htmlspecialchars($c['description']); ?></p>

<?php if($c['link']): ?>
<a href="<?php echo htmlspecialchars($c['link']); ?>" target="_blank" class="btn btn-outline-light w-100 mb-2">
    Visit Store
</a>
<?php endif; ?>

<div class="text-center my-3">
<?php if($c['code']): ?>
    <div class="coupon-code" onclick="copyCode('<?php echo $c['code']; ?>')">
        <?php echo $c['code']; ?>
    </div>
<?php else: ?>
    <div class="coupon-code login-required" onclick="location.href='login.php'">
        Login to view code
    </div>
<?php endif; ?>
</div>

<?php if($c['terms']): ?>
<div class="terms mt-2">
<strong>Terms:</strong> <?php echo htmlspecialchars($c['terms']); ?>
</div>
<?php endif; ?>

<div class="d-flex justify-content-between mt-3 small">
<span><?php echo date('M d, Y',strtotime($c['created_at'])); ?></span>
<?php if($c['expiration_date']): ?>
<span class="text-warning">Expires <?php echo date('M d',strtotime($c['expiration_date'])); ?></span>
<?php endif; ?>
</div>

</div>
</div>
<?php endforeach; else: ?>
<div class="text-center py-5">
<h4>No coupons found</h4>
</div>
<?php endif; ?>
</div>

</div>
</div>

<script>
function copyCode(code){
    navigator.clipboard.writeText(code).then(()=>{
        alert("Coupon copied!");
    });
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
