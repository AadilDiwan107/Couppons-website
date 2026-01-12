<?php
$websiteName = "Coupon.is-great.org";
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

include 'includes/db.php';

/* ======================
   HANDLE STATUS UPDATE
====================== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_status'])) {
    $coupon_id = (int)$_POST['coupon_id'];
    $new_status = $_POST['status'];

    if (in_array($new_status, ['active', 'used', 'expired'])) {
        $stmt = $connection->prepare("UPDATE coupons SET c_status=? WHERE id=?");
        $stmt->bind_param("si", $new_status, $coupon_id);
        $stmt->execute();
        $stmt->close();
        $success_message = "Coupon status updated successfully!";
    }
}

/* ======================
   HANDLE DELETE
====================== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_coupon'])) {
    $coupon_id = (int)$_POST['coupon_id'];
    $stmt = $connection->prepare("DELETE FROM coupons WHERE id=?");
    $stmt->bind_param("i", $coupon_id);
    $stmt->execute();
    $stmt->close();
    $success_message = "Coupon deleted successfully!";
}

/* ======================
   FETCH COUPONS
====================== */
$result = $connection->query("
    SELECT c.*, u.username 
    FROM coupons c 
    LEFT JOIN users u ON c.user_id = u.id 
    ORDER BY c.created_at DESC
");
$coupons = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Coupons - <?php echo $websiteName; ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #4361ee;
            --sidebar-width: 260px;
            --bg-color: #f3f4f6;
            --text-dark: #1f2937;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-dark);
            margin: 0;
        }

        /* Sidebar Styling (Matches Manage Users) */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background: #ffffff;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            border-right: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
        }

        .sidebar-brand {
            padding: 20px 25px;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
            border-bottom: 1px solid #f3f4f6;
        }

        .sidebar-menu { padding: 20px 15px; flex-grow: 1; }

        .nav-link {
            color: #6b7280;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            font-weight: 500;
            text-decoration: none;
            transition: 0.3s;
        }

        .nav-link:hover, .nav-link.active {
            background-color: #eef2ff;
            color: var(--primary-color);
        }

        .nav-link i { margin-right: 12px; font-size: 1.1rem; }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 30px;
        }

        /* Coupon Card Styling */
        .coupon-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            padding: 20px;
            height: 100%;
            display: flex;
            flex-direction: column;
            box-shadow: 0 2px 4px rgba(0,0,0,0.03);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .coupon-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
        }

        .coupon-code-box {
            background: #f8fafc;
            border: 2px dashed #cbd5e1;
            padding: 10px;
            border-radius: 8px;
            font-family: monospace;
            font-weight: bold;
            color: var(--primary-color);
            text-align: center;
            font-size: 1.1rem;
            margin: 15px 0;
        }

        /* Status Badge Styling */
        .badge-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            text-transform: uppercase;
            font-weight: 700;
            display: inline-block;
            margin-bottom: 10px;
        }
        .bg-active { background: #dcfce7; color: #166534; }
        .bg-used { background: #fef9c3; color: #854d0e; }
        .bg-expired { background: #fee2e2; color: #991b1b; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-brand">
        <i class="bi bi-shield-lock-fill me-2"></i> AdminPanel
    </div>
    <div class="sidebar-menu">
        <a href="admin_dashboard.php" class="nav-link">
            <i class="bi bi-grid"></i> Dashboard
        </a>
        <a href="manage_users.php" class="nav-link">
            <i class="bi bi-people"></i> Manage Users
        </a>
        <a href="manage_coupons.php" class="nav-link active">
            <i class="bi bi-ticket-perforated"></i> Manage Coupons
        </a>
        <a href="reports.php" class="nav-link">
            <i class="bi bi-bar-chart"></i> Reports
        </a>
        <a href="settings.php" class="nav-link">
            <i class="bi bi-gear"></i> Settings
        </a>
    </div>
</div>

<div class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold m-0">Coupon Management</h2>
            <p class="text-muted m-0">Review, update, or remove active offers</p>
        </div>
        <a href="admin_home.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-house"></i>
        </a>
    </div>

    <?php if(isset($success_message)): ?>
        <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4">
            <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <?php foreach($coupons as $coupon): ?>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="coupon-card">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="badge-status bg-<?php echo $coupon['c_status']; ?>">
                        <?php echo ucfirst($coupon['c_status']); ?>
                    </span>
                    <small class="text-muted">#<?php echo $coupon['id']; ?></small>
                </div>

                <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($coupon['company_name'] ?? 'Special Offer'); ?></h5>
                <p class="text-muted small mb-3"><?php echo htmlspecialchars($coupon['description']); ?></p>

                <div class="coupon-code-box">
                    <?php echo htmlspecialchars($coupon['code'] ?? 'NO CODE'); ?>
                </div>

                <div class="mt-auto">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-person-circle me-2 text-muted"></i>
                        <span class="small text-muted">Posted by: <strong><?php echo htmlspecialchars($coupon['username']); ?></strong></span>
                    </div>

                    <form method="POST" class="row g-2">
                        <input type="hidden" name="coupon_id" value="<?php echo $coupon['id']; ?>">
                        <div class="col-8">
                            <select name="status" class="form-select form-select-sm" required>
                                <option value="active" <?= $coupon['c_status']=='active'?'selected':'' ?>>Set to Active</option>
                                <option value="used" <?= $coupon['c_status']=='used'?'selected':'' ?>>Set to Used</option>
                                <option value="expired" <?= $coupon['c_status']=='expired'?'selected':'' ?>>Set to Expired</option>
                            </select>
                        </div>
                        <div class="col-4">
                            <button type="submit" name="update_status" class="btn btn-primary btn-sm w-100" title="Update Status">
                                <i class="bi bi-check-lg"></i>
                            </button>
                        </div>
                    </form>

                    <form method="POST" class="mt-2" onsubmit="return confirm('Delete this coupon permanently?')">
                        <input type="hidden" name="coupon_id" value="<?php echo $coupon['id']; ?>">
                        <button type="submit" name="delete_coupon" class="btn btn-outline-danger btn-sm w-100">
                            <i class="bi bi-trash me-1"></i> Delete Coupon
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if(empty($coupons)): ?>
            <div class="col-12 text-center py-5">
                <i class="bi bi-ticket-detailed text-muted" style="font-size: 3rem;"></i>
                <p class="text-muted mt-3">No coupons found in the system.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
