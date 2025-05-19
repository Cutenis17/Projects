<?php
session_start();

// Database connection constants
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'home_automation');

// Utility functions
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

function authenticateUser($username, $password) {
    $conn = getDBConnection();

    $stmt = $conn->prepare("SELECT username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        // Password matching (Use password_hash and password_verify in production)
        if ($password === $user['password']) {
            return [
                'username' => $user['username'],
                'role' => $user['role'], // Return the user's role
            ];
        }
    }

    return false;
}

function isLoggedIn() {
    return isset($_SESSION['username']);
}

// Handle login logic
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Authenticate user
    if ($user = authenticateUser($username, $password)) {
        // Store user details in session
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role']; // Store the user's role in session

        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid username or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Home Automation</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color:rgb(19, 158, 31);
            --primary-hover:rgb(26, 182, 96);
            --error-color: #f94144;
            --text-color: #2b2d42;
            --light-gray: #f8f9fa;
            --border-radius: 12px;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-gray);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            color: var(--text-color);
            background-image: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }

        .login-container {
            background: white;
            padding: 2.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            width: 100%;
            max-width: 420px;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(90deg,rgb(26, 182, 96),rgb(19, 158, 31));
        }

        h2 {
            text-align: center;
            margin-bottom: 1.75rem;
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .logo {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .logo img {
            height: 50px;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.9rem;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid #e0e0e0;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
            background-color: #f8f9fa;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
            background-color: white;
        }

        button {
            width: 100%;
            padding: 14px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 0.5rem;
        }

        button:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
        }

        button:active {
            transform: translateY(0);
        }

        .error {
            color: var(--error-color);
            text-align: center;
            margin-bottom: 1.5rem;
            padding: 12px;
            background-color: rgba(249, 65, 68, 0.1);
            border-radius: var(--border-radius);
            font-size: 0.9rem;
        }

        .input-icon {
            position: relative;
        }

        .input-icon i {
            position: absolute;
            top: 50%;
            right: 16px;
            transform: translateY(-50%);
            color: #adb5bd;
        }

        /* Responsive Design */
        @media (max-width: 480px) {
            .login-container {
                padding: 1.75rem;
            }
            
            h2 {
                font-size: 1.5rem;
                margin-bottom: 1.5rem;
            }
            
            input[type="text"],
            input[type="password"],
            button {
                padding: 12px 14px;
            }
        }

        @media (max-width: 360px) {
            .login-container {
                padding: 1.5rem;
            }
            
            h2 {
                font-size: 1.3rem;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-container {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <!-- You can add your logo here -->
            <!-- <img src="logo.png" alt="Home Automation"> -->
        </div>
        <h2>Welcome <br> LIVIN-Things</h2>
        
        <?php if ($error): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-icon">
                    <input type="text" id="username" name="username" required placeholder="Enter your username">
                    <i class="fas fa-user"></i>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-icon">
                    <input type="password" id="password" name="password" required placeholder="Enter your password">
                    <i class="fas fa-lock"></i>
                </div>
            </div>
            
            <button type="submit">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>
    </div>

    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</body>
</html>