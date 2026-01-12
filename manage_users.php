<?php
// Website name
$websiteName = "Coupon.is-great.org";

// Start session
session_start();

// Admin auth check
if (!isset($_SESSION['user_id'], $_SESSION['username']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// DB connection
include 'includes/db.php';

// Admin info
$admin_id = $_SESSION['user_id'];
$stmt = $connection->prepare("SELECT username FROM users WHERE id=?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch users
$users = [];
$result = mysqli_query($connection, "SELECT * FROM users ORDER BY created_at DESC");
while ($row = mysqli_fetch_assoc($result)) {
    $users[] = $row;
}

// Update role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $uid = $_POST['user_id'];
    $role = $_POST['role'];

    if (in_array($role, ['user','admin'])) {
        $stmt = $connection->prepare("UPDATE users SET role=? WHERE id=?");
        $stmt->bind_param("si", $role, $uid);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: manage_users.php");
    exit();
}

// Delete user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $uid = $_POST['user_id'];
    if ($uid != $admin_id) {
        $stmt = $connection->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: manage_users.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Users - <?php echo $websiteName; ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

<style>
:root{
    --primary:#4361ee;
    --bg:#f3f4f6;
    --text:#1f2937;
    --sidebar-width:260px;
}

body{
    background:var(--bg);
    font-family:Inter,system-ui;
    color:var(--text);
    margin:0;
}

/* Main content */
.main-content{
    margin-left:var(--sidebar-width);
    padding:30px;
}

.card-box{
    background:#fff;
    border-radius:14px;
    border:1px solid #e5e7eb;
    box-shadow:0 4px 12px rgba(0,0,0,.04);
    padding:20px;
}

/* Stats */
.stats{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:20px;
    margin-bottom:30px;
}

.stat{
    display:flex;
    justify-content:space-between;
    align-items:center;
    background:#fff;
    padding:20px;
    border-radius:14px;
    border:1px solid #e5e7eb;
}

.stat i{
    font-size:1.8rem;
    color:var(--primary);
}

.table thead th{
    background:#f9fafb;
    text-transform:uppercase;
    font-size:.75rem;
    color:#6b7280;
}

.user-avatar{
    width:34px;
    height:34px;
    background:#e0e7ff;
    color:var(--primary);
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:700;
    margin-right:10px;
}

.role-badge{
    padding:4px 12px;
    border-radius:20px;
    font-size:.75rem;
    font-weight:600;
}
.role-admin{background:#eef2ff;color:#4338ca;}
.role-user{background:#ecfdf5;color:#16a34a;}

@media(max-width:768px){
    .main-content{margin-left:0;}
}
</style>
</head>

<body>

<!-- ✅ External Admin Navbar -->
<?php include 'includes/admin_nav.php'; ?>

<div class="main-content">

    <div class="mb-4">
        <h2 class="fw-bold mb-1">User Management</h2>
        <p class="text-muted">View & manage all system users</p>
    </div>

    <!-- Stats -->
    <div class="stats">
        <div class="stat">
            <div>
                <h4 class="fw-bold mb-0"><?php echo count($users); ?></h4>
                <small>Total Users</small>
            </div>
            <i class="bi bi-people"></i>
        </div>

        <div class="stat">
            <div>
                <h4 class="fw-bold mb-0"><?php echo count(array_filter($users, fn($u)=>$u['role']=='admin')); ?></h4>
                <small>Admins</small>
            </div>
            <i class="bi bi-shield-lock"></i>
        </div>

        <div class="stat">
            <div>
                <h4 class="fw-bold mb-0"><?php echo count(array_filter($users, fn($u)=>$u['role']=='user')); ?></h4>
                <small>Users</small>
            </div>
            <i class="bi bi-person"></i>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card-box">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Mobile</th>
                        <th>Joined</th>
                        <th>Role</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if($users): foreach($users as $u): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="user-avatar"><?php echo strtoupper($u['username'][0]); ?></div>
                                <div>
                                    <strong><?php echo htmlspecialchars($u['username']); ?></strong><br>
                                    <small class="text-muted">ID #<?php echo $u['id']; ?></small>
                                </div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td><?php echo htmlspecialchars($u['mobile'] ?: '—'); ?></td>
                        <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                        <td>
                            <span class="role-badge <?php echo $u['role']=='admin'?'role-admin':'role-user'; ?>">
                                <?php echo ucfirst($u['role']); ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <form method="post" class="d-inline-flex gap-2">
                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                <input type="hidden" name="update_role" value="1">

                                <select name="role" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="user" <?php echo $u['role']=='user'?'selected':''; ?>>User</option>
                                    <option value="admin" <?php echo $u['role']=='admin'?'selected':''; ?>>Admin</option>
                                </select>

                                <?php if($u['id'] != $admin_id): ?>
                                <button type="submit" name="delete_user"
                                    onclick="return confirm('Delete this user permanently?')"
                                    class="btn btn-light btn-sm text-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="6" class="text-center py-5 text-muted">No users found</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
