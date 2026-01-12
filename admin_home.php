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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo $websiteName; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --sidebar-width: 260px;
            --bg-color: #f3f4f6;
            --card-bg: #ffffff;
            --text-dark: #1f2937;
            --text-light: #6b7280;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-dark);
            margin: 0;
            overflow-x: hidden;
        }

        /* Sidebar Styling (Required in CSS even if HTML is included) */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background: #ffffff;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            transition: all 0.3s ease;
            border-right: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
        }

        .sidebar-brand {
            padding: 20px 25px;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            border-bottom: 1px solid #f3f4f6;
        }
        

        .sidebar-menu { padding: 20px 15px; flex-grow: 1; }

        .nav-link {
            color: var(--text-light);
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
        }

        .nav-link:hover, .nav-link.active {
            background-color: #eef2ff;
            color: var(--primary-color);
        }

        .nav-link i { margin-right: 12px; font-size: 1.1rem; }

        .user-profile {
            padding: 20px;
            border-top: 1px solid #f3f4f6;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        /* Main Content Styling */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 30px;
            transition: all 0.3s ease;
        }

        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .toggle-sidebar {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-dark);
        }

        /* Stats Cards */
        .stat-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid #f3f4f6;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .stat-info h3 { font-size: 1.8rem; font-weight: 700; margin: 0; color: var(--text-dark); }
        .stat-info p { margin: 0; color: var(--text-light); font-size: 0.9rem; }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .bg-blue-light { background: #eef2ff; color: #4361ee; }
        .bg-green-light { background: #dcfce7; color: #16a34a; }
        .bg-purple-light { background: #f3e8ff; color: #9333ea; }
        .bg-red-light { background: #fee2e2; color: #dc2626; }

        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .action-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            border: 1px solid #e5e7eb;
            transition: all 0.3s;
            text-decoration: none;
            display: block;
        }

        .action-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-5px);
        }

        .action-card i { font-size: 2rem; color: var(--primary-color); margin-bottom: 15px; display: block; }
        .action-card h5 { color: var(--text-dark); font-weight: 600; margin-bottom: 10px; }
        .action-card p { color: var(--text-light); font-size: 0.85rem; margin-bottom: 0; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .toggle-sidebar { display: block; }
        }
    </style>
</head>
<body>

    <?php include 'includes/admin_nav.php'; ?>

    <div class="main-content">
        <div class="top-header">
            <button class="toggle-sidebar" onclick="toggleSidebar()">
                <i class="bi bi-list"></i>
            </button>
            <h2 class="m-0 fw-bold text-dark">Dashboard Overview</h2>
            <div class="d-none d-md-block text-muted">
                <?php echo date('l, F j, Y'); ?>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-12 col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-info">
                        <?php
                        $stmt = $connection->prepare("SELECT COUNT(*) as count FROM users");
                        $stmt->execute();
                        $users_count = $stmt->get_result()->fetch_assoc()['count'];
                        $stmt->close();
                        ?>
                        <h3><?php echo $users_count; ?></h3>
                        <p>Total Users</p>
                    </div>
                    <div class="stat-icon bg-blue-light">
                        <i class="bi bi-people"></i>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-info">
                        <?php
                        $stmt = $connection->prepare("SELECT COUNT(*) as count FROM coupons");
                        $stmt->execute();
                        $coupons_count = $stmt->get_result()->fetch_assoc()['count'];
                        $stmt->close();
                        ?>
                        <h3><?php echo $coupons_count; ?></h3>
                        <p>Total Coupons</p>
                    </div>
                    <div class="stat-icon bg-purple-light">
                        <i class="bi bi-tags"></i>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-info">
                        <?php
                        $stmt = $connection->prepare("SELECT COUNT(*) as count FROM coupons WHERE c_status = 'used'");
                        $stmt->execute();
                        $used_count = $stmt->get_result()->fetch_assoc()['count'];
                        $stmt->close();
                        ?>
                        <h3><?php echo $used_count; ?></h3>
                        <p>Used Coupons</p>
                    </div>
                    <div class="stat-icon bg-green-light">
                        <i class="bi bi-check-lg"></i>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-info">
                        <?php
                        $stmt = $connection->prepare("SELECT COUNT(*) as count FROM coupons WHERE c_status = 'expired'");
                        $stmt->execute();
                        $expired_count = $stmt->get_result()->fetch_assoc()['count'];
                        $stmt->close();
                        ?>
                        <h3><?php echo $expired_count; ?></h3>
                        <p>Expired</p>
                    </div>
                    <div class="stat-icon bg-red-light">
                        <i class="bi bi-x-circle"></i>
                    </div>
                </div>
            </div>
        </div>

        <h4 class="mb-3 fw-bold text-dark">Quick Actions</h4>
        <div class="action-grid">
            <a href="manage_users.php" class="action-card">
                <i class="bi bi-person-gear"></i>
                <h5>User Management</h5>
                <p>Add, edit or ban users</p>
            </a>
            
            <a href="manage_coupons.php" class="action-card">
                <i class="bi bi-ticket-detailed"></i>
                <h5>Coupon Control</h5>
                <p>Create and track coupons</p>
            </a>
            
            <a href="reports.php" class="action-card">
                <i class="bi bi-graph-up-arrow"></i>
                <h5>System Reports</h5>
                <p>View usage analytics</p>
            </a>
            
            <a href="settings.php" class="action-card">
                <i class="bi bi-sliders"></i>
                <h5>Settings</h5>
                <p>Configuration options</p>
            </a>

            <a href="moderation.php" class="action-card">
                <i class="bi bi-shield-check"></i>
                <h5>Moderation</h5>
                <p>Review content</p>
            </a>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
