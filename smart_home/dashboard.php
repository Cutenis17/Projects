<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Automation Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
</head>
<body>
    <div class="app-container">
        <!-- Navigation Sidebar -->
        <div class="sidebar">
            <div class="user-info">
                <h2><?php echo htmlspecialchars($username); ?></h2>
                <small><?php echo htmlspecialchars(ucfirst($role)); ?></small>
            </div>
            <nav class="nav-menu">
                <a href="dashboard.php" class="nav-item active">
                    <span class="nav-icon"><i class="fas fa-tachometer-alt"></i></span>
                    <span>Dashboard</span>
                </a>
                <?php if ($role === 'admin'): ?>
                <a href="admin/user-management.php" class="nav-item">
                    <span class="nav-icon"><i class="fas fa-users-cog"></i></span>
                    <span>User Management</span>
                </a>
                <?php endif; ?>
                <a href="#" class="nav-item" onclick="confirmLogout(event)">
                    <span class="nav-icon"><i class="fas fa-sign-out-alt"></i></span>
                    <span>Logout</span>
                </a>
            </nav>
        </div>
        
        <!-- Main Content Area -->
        <div class="main-content">
            <!-- Alert Banner -->
            <div id="smokeAlert" class="alert-banner" style="display:none;">
                <div class="alert-content">
                    <span class="alert-icon">🔥</span>
                    <div>
                        <h3>SMOKE DETECTED!</h3>
                        <p>Immediate attention required</p>
                    </div>
                </div>
                <button class="alert-button" onclick="resetAlert()">Reset Alert</button>
            </div>

            <header class="content-header">
                <h1>Home Automation Control Panel</h1>
                <div class="current-time" id="currentTime">--:--:-- --</div>
            </header>

            <!-- Relay Controls Section -->
            <section class="relay-section">
                <h2 class="section-title">Relay Controls</h2>
                <div class="relay-grid">
                    <div class="relay-card">
                        <h3>Relay 1</h3>
                        <button id="relay1Btn" class="relay-toggle relay-off" onclick="toggleRelay(1)">
                            <span class="relay-state">OFF</span>
                        </button>
                        <div class="relay-schedule">
                            <span class="schedule-label">Schedule:</span>
                            <span id="relay1Sch" class="schedule-value">Not set</span>
                        </div>
                        
                    </div>
                    <div class="relay-card">
                        <h3>Relay 2</h3>
                        <button id="relay2Btn" class="relay-toggle relay-off" onclick="toggleRelay(2)">
                            <span class="relay-state">OFF</span>
                        </button>
                        <div class="relay-schedule">
                            <span class="schedule-label">Schedule:</span>
                            <span id="relay2Sch" class="schedule-value">Not set</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Sensor Data Section -->
            <section class="sensor-section">
                <h2 class="section-title">Environment Monitoring</h2>
                <div class="sensor-grid">
                    <!-- Smoke Gauge -->
                    <div class="sensor-card">
                        <div class="gauge-container">
                            <div class="gauge-body">
                                <div class="gauge-fill" id="smokeGaugeFill"></div>
                                <div class="gauge-cover">
                                    <div id="smokeValue" class="gauge-value safe">0</div>
                                    <div class="gauge-label">Smoke Level</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="sensor-card">
                        <div class="sensor-icon">
                            <i class="fas fa-ruler-vertical" style="font-size: 2.5rem; color: #2196F3;"></i>
                        </div>
                        <div class="sensor-data">
                            <div id="ultrasonicValue" class="sensor-value">0</div>
                            <div class="sensor-unit">cm</div>
                        </div>
                        <div class="sensor-label">Distance</div>
                        <div class="sensor-status" id="motionStatus">No motion detected</div>
                    </div>
                    
                </div>
            </section>

            <!-- Schedule Section -->
            <section class="schedule-section">
                <h2 class="section-title">Schedule Settings</h2>
                <div class="schedule-form">
                    <div class="time-inputs">
                        <div class="time-group">
                            <label>ON Time</label>
                            <div class="time-controls">
                                <input type="number" id="on_hour" min="1" max="12" placeholder="HH" onchange="validateHourInput(this)">
                                <span>:</span>
                                <input type="number" id="on_minute" min="0" max="59" placeholder="MM" onchange="validateMinuteInput(this)">
                                <select id="on_ampm">
                                    <option value="AM">AM</option>
                                    <option value="PM">PM</option>
                                </select>
                            </div>
                        </div>
                        <div class="time-group">
                            <label>OFF Time</label>
                            <div class="time-controls">
                                <input type="number" id="off_hour" min="1" max="12" placeholder="HH" onchange="validateHourInput(this)">
                                <span>:</span>
                                <input type="number" id="off_minute" min="0" max="59" placeholder="MM" onchange="validateMinuteInput(this)">
                                <select id="off_ampm">
                                    <option value="AM">AM</option>
                                    <option value="PM">PM</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-footer">
                        <div class="relay-selector">
                            <label>Apply to:</label>
                            <select id="relaySelect">
                                <option value="1">Relay 1</option>
                                <option value="2">Relay 2</option>
                                <option value="both">Both Relays</option>
                            </select>
                        </div>
                        <button class="submit-btn" onclick="setSchedule()">SET SCHEDULE</button>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <script src="scripts.js"></script>
</body>
</html>