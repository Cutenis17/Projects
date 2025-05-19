<?php
session_start();

// Include database connection and utility functions
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'home_automation');

function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

// Check if user is logged in
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Handle form submissions for Add/Edit/Delete
$conn = getDBConnection();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["save"])) {
        $id = $_POST["id"] ?? null;
        $username = $_POST["username"];
        $password = $_POST["password"]; // Password is stored without hashing
        $role = $_POST["role"];

        if (empty($id)) {
            // Add new user
            $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $password, $role);
        } else {
            // Update existing user
            $stmt = $conn->prepare("UPDATE users SET username=?, password=?, role=? WHERE id=?");
            $stmt->bind_param("sssi", $username, $password, $role, $id);
        }

        if ($stmt->execute()) {
            $_SESSION['message'] = "User saved successfully!";
        } else {
            $_SESSION['error'] = "Error saving user: " . $conn->error;
        }
    } elseif (isset($_POST["delete"])) {
        $id = $_POST["id"];
        $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $_SESSION['message'] = "User deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting user: " . $conn->error;
        }
    }
    header("Location: user-management.php");
    exit();
}

// Fetch all users
$users = [];
$result = $conn->query("SELECT * FROM users ORDER BY role, username");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | Smart Home</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #e0e7ff;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --dark: #212529;
            --light: #f8f9fa;
            --gray: #6c757d;
            --gray-light: #e9ecef;
            --white: #ffffff;
            --sidebar-width: 250px;
            --header-height: 70px;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fa;
            color: var(--dark);
            line-height: 1.6;
        }

        .app-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--dark);
            color: var(--white);
            padding: 1.5rem 0;
            transition: var(--transition);
            position: fixed;
            height: 100vh;
            z-index: 100;
        }

        .user-info {
            padding: 0 1.5rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 1.5rem;
        }

        .user-info h2 {
            font-size: 1.1rem;
            font-weight: 500;
            color: var(--white);
        }

        .nav-menu {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 0.8rem 1.5rem;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: var(--transition);
            font-size: 0.95rem;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--white);
        }

        .nav-item.active {
            background: var(--primary);
            color: var(--white);
        }

        .nav-icon {
            margin-right: 0.75rem;
            font-size: 1rem;
            width: 20px;
            text-align: center;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 2rem;
        }

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .content-header h1 {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--dark);
        }

        /* Card Styles */
        .card {
            background: var(--white);
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        /* Table Styles */
        .user-table {
            width: 100%;
            border-collapse: collapse;
        }

        .user-table th, .user-table td {
            padding: 0.75rem 1rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-light);
        }

        .user-table th {
            background-color: var(--primary-light);
            color: var(--primary);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }

        .user-table tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }

        /* Button Styles */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            outline: none;
        }

        .btn-primary {
            background-color: var(--primary);
            color: var(--white);
        }

        .btn-primary:hover {
            background-color: var(--secondary);
        }

        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background-color: var(--primary-light);
        }

        .btn-danger {
            background-color: var(--danger);
            color: var(--white);
        }

        .btn-danger:hover {
            background-color: #d1146a;
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.8125rem;
        }

        .btn i {
            margin-right: 0.375rem;
            font-size: 0.875rem;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--gray-light);
            border-radius: 6px;
            font-size: 0.9375rem;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 16px 12px;
            padding-right: 2.5rem;
        }

        /* Alert Styles */
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            font-size: 0.9375rem;
        }

        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #b91c1c;
            border-left: 4px solid #ef4444;
        }

        /* Utility Classes */
        .flex {
            display: flex;
        }

        .items-center {
            align-items: center;
        }

        .justify-between {
            justify-content: space-between;
        }

        .gap-2 {
            gap: 0.5rem;
        }

        .mb-3 {
            margin-bottom: 1.5rem;
        }

        .mt-3 {
            margin-top: 1.5rem;
        }

        .text-muted {
            color: var(--gray);
        }

        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .sidebar {
                width: 70px;
                overflow: hidden;
            }

            .sidebar .user-info h2,
            .sidebar .nav-item span:not(.nav-icon) {
                display: none;
            }

            .sidebar .nav-item {
                justify-content: center;
                padding: 0.8rem;
            }

            .sidebar .nav-icon {
                margin-right: 0;
                font-size: 1.2rem;
            }

            .main-content {
                margin-left: 70px;
            }
        }

        @media (max-width: 768px) {
            .user-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Navigation Sidebar -->
        <div class="sidebar">
            <div class="user-info">
                <h2><?php echo htmlspecialchars($_SESSION['username']); ?></h2>
                <small class="text-muted">Admin</small>
            </div>
            <nav class="nav-menu">
                <a href="../dashboard.php" class="nav-item">
                    <span class="nav-icon"><i class="fas fa-tachometer-alt"></i></span>
                    <span>Dashboard</span>
                </a>
                <a href="user-management.php" class="nav-item active">
                    <span class="nav-icon"><i class="fas fa-users-cog"></i></span>
                    <span>User Management</span>
                </a>
                <a href="#" class="nav-item" onclick="confirmLogout(event)">
                    <span class="nav-icon"><i class="fas fa-sign-out-alt"></i></span>
                    <span>Logout</span>
                </a>
            </nav>
        </div>

        <!-- Main Content Area -->
        <div class="main-content">
            <header class="content-header">
                <h1><i class="fas fa-users-cog mr-2"></i> User Management</h1>
                <button class="btn btn-primary" onclick="resetForm()">
                    <i class="fas fa-plus"></i> Add User
                </button>
            </header>

            <!-- Messages -->
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle mr-2"></i> <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
                </div>
            <?php elseif (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle mr-2"></i> <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <div class="flex gap-2" style="gap: 2rem;">
                <!-- User Table Card -->
                <section class="card" style="flex: 2;">
                    <h2 class="section-title">
                        <i class="fas fa-users mr-2"></i> User List
                    </h2>
                    <div class="user-table-container">
                        <table class="user-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($users) > 0): ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td>
                                                <span class="badge <?php echo $user['role'] === 'admin' ? 'badge-primary' : 'badge-secondary'; ?>">
                                                    <?php echo htmlspecialchars(ucfirst($user['role'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="flex gap-2">
                                                    <button class="btn btn-outline btn-sm" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <form method="POST" action="user-management.php" style="display:inline;">
                                                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($user['id']); ?>">
                                                        <button type="submit" name="delete" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this user?')">
                                                            <i class="fas fa-trash-alt"></i> Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            No users found. Start by adding a new user.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Add/Edit User Form Card -->
                <section class="card" style="flex: 1;">
                    <h2 class="section-title" id="formTitle">
                        <i class="fas fa-user-plus mr-2"></i> Add New User
                    </h2>
                    <form id="userForm" method="POST" action="user-management.php">
                        <input type="hidden" name="id" id="userId">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" name="username" id="username" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" name="password" id="password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="role">Role</label>
                            <select name="role" id="role" class="form-control" required>
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="mt-3">
                            <button type="submit" name="save" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save User
                            </button>
                            <button type="button" class="btn btn-outline" onclick="resetForm()">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>

    <script>
        // Fill form for editing a user
        function editUser(user) {
            document.getElementById('userId').value = user.id;
            document.getElementById('username').value = user.username;
            document.getElementById('password').value = user.password;
            document.getElementById('role').value = user.role;
            
            const formTitle = document.getElementById('formTitle');
            formTitle.innerHTML = `<i class="fas fa-user-edit mr-2"></i> Edit User: ${user.username}`;
            
            // Scroll to form for better UX on mobile
            document.getElementById('userForm').scrollIntoView({ behavior: 'smooth' });
        }

        // Reset form to add new user
        function resetForm() {
            document.getElementById('userForm').reset();
            document.getElementById('userId').value = '';
            document.getElementById('formTitle').innerHTML = `<i class="fas fa-user-plus mr-2"></i> Add New User`;
        }

        // Confirmation for logout
        function confirmLogout(event) {
            event.preventDefault();
            if (confirm("Are you sure you want to logout?")) {
                window.location.href = "../logout.php";
            }
        }

        // Show password toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            // You could add a password toggle here if desired
            // const passwordInput = document.getElementById('password');
            // const toggleBtn = document.createElement('button');
            // ... (implementation for show/hide password)
        });
    </script>
</body>
</html>