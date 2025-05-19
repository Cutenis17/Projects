<?php
// Enable CORS and set headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-API-KEY");
header('Content-Type: application/json');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'home_automation');
date_default_timezone_set('Asia/Manila');

// NodeMCU Configuration
define('NODEMCU_IP', '192.168.190.230');
define('NODEMCU_TIMEOUT', 2); // Reduced timeout to 2 seconds

// Secure API Key
define('API_KEY', 'LIVIN_Things_2023_SECRET_KEY_!@#$%7890');

// Handle OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

// Authenticate API request
function authenticateApiRequest() {
    // Skip authentication for local requests
    if ($_SERVER['REMOTE_ADDR'] === '127.0.0.1' || $_SERVER['REMOTE_ADDR'] === '::1') {
        return;
    }

    $providedKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
    if ($providedKey !== API_KEY) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid API key']);
        exit();
    }
}

// Database connection
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        http_response_code(500);
        die(json_encode(['error' => 'Database connection failed']));
    }
    return $conn;
}

// Forward request to NodeMCU
function forwardToNodeMCU($endpoint) {
    $url = "http://" . NODEMCU_IP . "/api/" . $endpoint;
    $method = $_SERVER['REQUEST_METHOD'];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1); // Faster connection timeout
    curl_setopt($ch, CURLOPT_TIMEOUT, NODEMCU_TIMEOUT);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
    } elseif ($method === 'PUT' || $method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    }
    
    if ($method === 'POST' || $method === 'PUT') {
        $postData = file_get_contents('php://input');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        http_response_code(500);
        echo json_encode(["error" => "Failed to connect to NodeMCU: " . curl_error($ch)]);
        curl_close($ch);
        exit();
    }
    
    curl_close($ch);
    http_response_code($httpCode);
    echo $response;
    exit();
}

// Convert to 24-hour format
function convertTo24Hour($hour, $minute, $ampm) {
    $hour = (int)$hour;
    $minute = (int)$minute;
    
    if ($ampm === 'PM' && $hour < 12) $hour += 12;
    if ($ampm === 'AM' && $hour == 12) $hour = 0;
    
    return sprintf('%02d:%02d:00', $hour, $minute);
}

// Main API endpoint handler
function handleApiRequest() {
    $endpoint = $_GET['api'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Forward NodeMCU-specific requests immediately
    if (strpos($endpoint, 'nodemcu/') === 0) {
        forwardToNodeMCU(substr($endpoint, 8));
    }
    
    // Handle database operations
    $conn = getDBConnection();
    try {
        switch ($endpoint) {
            case 'db/status':
                handleStatusRequest($conn);
                break;
                
                case 'db/relays':
                    if ($method === 'PUT') {
                        $input = json_decode(file_get_contents('php://input'), true);
                        $relay = (int)($input['relay'] ?? 0);
                        $state = isset($input['state']) ? (int)$input['state'] : null;
                        $source = $input['source'] ?? 'manual'; // Get source or default to 'manual'
                        
                        if ($relay < 1 || $relay > 2) {
                            throw new Exception("Invalid relay number");
                        }
                        
                        if ($state === null) {
                            // Toggle state
                            $stmt = $conn->prepare("SELECT state FROM relays WHERE relay_number = ?");
                            $stmt->bind_param("i", $relay);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $current = $result->fetch_assoc();
                            $state = $current['state'] ? 0 : 1;
                        }
                        
                        // Updated statement with activation_source
                        $stmt = $conn->prepare("UPDATE relays SET state = ?, activation_source = ? WHERE relay_number = ?");
                        $stmt->bind_param("isi", $state, $source, $relay);
                        $stmt->execute();
                        
                        echo json_encode([
                            "status" => "OK",
                            "relay" => $relay,
                            "state" => $state,
                            "timestamp" => microtime(true)
                        ]);
                    } else {
                        http_response_code(405);
                        echo json_encode(["error" => "Method not allowed"]);
                    }
                    break;
                
            case 'db/schedules':
            case 'schedules':
                if ($method === 'POST') {
                    handleSchedulesRequest($conn);
                } else {
                    http_response_code(405);
                    echo json_encode(["error" => "Method not allowed"]);
                }
                break;
                
            case 'db/smoke':
                if ($method === 'POST') {
                    handleSmokeRequest($conn);
                } else {
                    http_response_code(405);
                    echo json_encode(["error" => "Method not allowed"]);
                }
                break;
                
            case 'db/alerts':
                if ($method === 'DELETE') {
                    handleAlertsRequest($conn);
                } else {
                    http_response_code(405);
                    echo json_encode(["error" => "Method not allowed"]);
                }
                break;

            case 'db/ultrasonic':
                if ($method === 'POST') {
                    handleUltrasonicRequest($conn);
                } else {
                    http_response_code(405);
                    echo json_encode(["error" => "Method not allowed"]);
                }
                break;

            default:
                http_response_code(404);
                echo json_encode(["error" => "Endpoint not found"]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => $e->getMessage()]);
    } finally {
        $conn->close();
    }
}

// Modify handleUltrasonicRequest() in api.php
function handleUltrasonicRequest($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['distance'])) {
        throw new Exception("Distance value not specified");
    }
    
    $distance = (int)$input['distance'];
    $threshold = isset($input['threshold']) ? (int)$input['threshold'] : 50;
    $triggered = $distance > 0 && $distance <= $threshold ? 1 : 0;
    
    // Update ultrasonic log
    $stmt = $conn->prepare("UPDATE ultrasonic_logs SET distance = ?, triggered = ?, 
                          last_motion_time = IF(?, NOW(), last_motion_time) WHERE id = 1");
    $stmt->bind_param("iii", $distance, $triggered, $triggered);
    $stmt->execute();
    
    // Turn on light if motion detected (only if not manually controlled)
    if ($triggered) {
        $conn->query("UPDATE relays SET state = 1, activation_source = 'motion' 
                     WHERE relay_number = 1 AND activation_source != 'manual'");
    }
    
    // Check for lights that need to be turned off (only if activated by motion)
    $timeoutMinutes = 1; // Adjust this value as needed
    $conn->query("UPDATE relays SET state = 0 
                 WHERE relay_number = 1 
                 AND state = 1 
                 AND activation_source = 'motion'
                 AND EXISTS (
                     SELECT 1 FROM ultrasonic_logs 
                     WHERE TIMESTAMPDIFF(MINUTE, last_motion_time, NOW()) >= $timeoutMinutes
                 )");
    
    echo json_encode(["status" => "OK"]);
}

// Handler functions
function handleStatusRequest($conn) {
    $response = [];
    
    $result = $conn->query("SELECT distance, triggered FROM ultrasonic_logs WHERE id = 1");
    if ($row = $result->fetch_assoc()) {
        $response['ultrasonic_distance'] = (int)$row['distance'];
        $response['motion_detected'] = (bool)$row['triggered'];
    } else {
        $response['ultrasonic_distance'] = 0;
        $response['motion_detected'] = false;
    }

    

    // Get relay states
    // In handleStatusRequest() function, modify the relay query:
$result = $conn->query("SELECT relay_number, state, activation_source FROM relays ORDER BY relay_number");
while ($row = $result->fetch_assoc()) {
    $response['relay' . $row['relay_number']] = (bool)$row['state'];
    $response['relay' . $row['relay_number'] . '_source'] = $row['activation_source'];
}
    

    // Get schedules
    $result = $conn->query("
        SELECT r.relay_number,
               IFNULL(DATE_FORMAT(s.on_time, '%h:%i %p'), 'Not set') as on_time,
               IFNULL(DATE_FORMAT(s.off_time, '%h:%i %p'), 'Not set') as off_time
        FROM (SELECT 1 as relay_number UNION SELECT 2) r
        LEFT JOIN schedules s ON r.relay_number = s.relay_number AND s.active = 1
        ORDER BY r.relay_number
    ");
    
    while ($row = $result->fetch_assoc()) {
        $relay = $row['relay_number'];
        if ($row['on_time'] != 'Not set' && $row['off_time'] != 'Not set') {
            $response['relay' . $relay . '_schedule'] = $row['on_time'] . ' to ' . $row['off_time'];
        } else {
            $response['relay' . $relay . '_schedule'] = 'Not set';
        }
    }
    
    
    
    // Get smoke reading
    $result = $conn->query("SELECT sensor_value FROM smoke_logs WHERE id = 1");
    if ($row = $result->fetch_assoc()) {
        $response['smoke_value'] = (int)$row['sensor_value'];
    } else {
        $response['smoke_value'] = 0;
    }
    
    // Check for active smoke alert
    $result = $conn->query("SELECT 1 FROM alerts WHERE alert_type = 'smoke' AND active = 1 LIMIT 1");
    $response['smoke_detected'] = $result->num_rows > 0;
    $response['alert_active'] = $response['smoke_detected'];
    
    // Current time
    $response['time'] = date('h:i:s A');
    
    echo json_encode($response);
}

function handleSchedulesRequest($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    $required = ['on_hour', 'on_minute', 'on_ampm', 'off_hour', 'off_minute', 'off_ampm', 'relay'];
    foreach ($required as $field) {
        if (!isset($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Convert to 24-hour format
    $onTime = convertTo24Hour($input['on_hour'], $input['on_minute'], $input['on_ampm']);
    $offTime = convertTo24Hour($input['off_hour'], $input['off_minute'], $input['off_ampm']);
    
    // Handle relay selection
    $relays = [];
    if ($input['relay'] === 'both') {
        $relays = [1, 2];
    } else {
        $relays = [(int)$input['relay']];
    }
    
    // Update schedules
    foreach ($relays as $relay) {
        // First deactivate any existing schedule for this relay
        $stmt = $conn->prepare("UPDATE schedules SET active = 0 WHERE relay_number = ?");
        $stmt->bind_param("i", $relay);
        $stmt->execute();
        
        // Insert new schedule
        $stmt = $conn->prepare("INSERT INTO schedules (relay_number, on_time, off_time) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $relay, $onTime, $offTime);
        $stmt->execute();
    }
    
    // Return all active schedules
    $response = ["status" => "Schedule Set"];
    $result = $conn->query("
        SELECT relay_number,
               DATE_FORMAT(on_time, '%h:%i %p') as on_time,
               DATE_FORMAT(off_time, '%h:%i %p') as off_time
        FROM schedules
        WHERE active = 1
        ORDER BY relay_number
    ");
    
    while ($row = $result->fetch_assoc()) {
        $relay = $row['relay_number'];
        $response["relay" . $relay . "_schedule"] = $row['on_time'] . ' to ' . $row['off_time'];
    }
    
    echo json_encode($response);
}

function handleSmokeRequest($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['value'])) {
        throw new Exception("Smoke value not specified");
    }
    
    $value = (int)$input['value'];
    $threshold = isset($input['threshold']) ? (int)$input['threshold'] : 250;
    $alertTriggered = $value > $threshold ? 1 : 0;
    
    // Log smoke reading
    $stmt = $conn->prepare("UPDATE smoke_logs SET sensor_value = ?, alert_triggered = ?, updated_at = NOW() WHERE id = 1");
    $stmt->bind_param("ii", $value, $alertTriggered);
    $stmt->execute();
    
    // Handle alert if threshold exceeded
    if ($alertTriggered) {
        // Check if alert already exists
        $result = $conn->query("SELECT id FROM alerts WHERE alert_type = 'smoke' AND active = 1 LIMIT 1");
        if ($result->num_rows === 0) {
            $conn->query("INSERT INTO alerts (alert_type, active) VALUES ('smoke', 1)");
        }
    }
    
    echo json_encode(["status" => "OK"]);
}

function handleAlertsRequest($conn) {
    // Reset all active smoke alerts
    $result = $conn->query("UPDATE alerts SET active = 0, resolved_at = NOW() WHERE alert_type = 'smoke' AND active = 1");
    
    if ($result) {
        // Check if any rows were affected
        if ($conn->affected_rows > 0) {
            echo json_encode([
                "status" => "success",
                "message" => "Alert Reset",
                "alert_active" => false
            ]);
        } else {
            echo json_encode([
                "status" => "success",
                "message" => "No active alerts to reset",
                "alert_active" => false
            ]);
        }
    } else {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "Database error: " . $conn->error
        ]);
    }
}



// Authenticate and process the request
authenticateApiRequest();
handleApiRequest();