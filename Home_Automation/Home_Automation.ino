#include <ESP8266WiFi.h>
#include <ESP8266WebServer.h>
#include <ESP8266HTTPClient.h>
#include <WiFiClient.h>
#include <time.h>
#include <ArduinoJson.h>

// WiFi Configuration
const char* ssid = "realme";
const char* password = "jeraldBSC2";
const char* serverUrl = "http://192.168.190.49/smart_home/api.php?api=db/";
const char* apiKey = "LIVIN_Things_2023_SECRET_KEY_!@#$%7890";

// Pin Definitions
#define RELAY1 D1
#define RELAY2 D2
#define SMOKE_SENSOR A0 
#define TRIGGER_PIN D5
#define ECHO_PIN D6

// Constants
const int SMOKE_THRESHOLD = 250;
const unsigned long STATUS_UPDATE_INTERVAL = 1000;
const unsigned long SCHEDULE_CHECK_INTERVAL = 5000;
const unsigned long SMOKE_CHECK_INTERVAL = 2000;
const unsigned long IMMEDIATE_CONTROL_TIMEOUT = 5000;
const int ULTRASONIC_THRESHOLD = 20;
const unsigned long ULTRASONIC_INTERVAL = 500;
const unsigned long LIGHT_TIMEOUT = 1 * 60 * 1000; // 1 minute in milliseconds

// Global Variables
ESP8266WebServer server(80);
bool relay1State = false;
bool relay2State = false;
bool immediateControl = false;
bool manualOverride = false;
unsigned long immediateControlTimeout = 0;
unsigned long lastUltrasonicCheck = 0;
unsigned long lastMotionTime = 0;

// Schedule structure
struct Schedule {
  int relayNumber;
  int onHour;
  int onMinute;
  int offHour;
  int offMinute;
  bool onExecuted;
  bool offExecuted;
};

Schedule relay1Schedule = {1, 0, 0, 0, 0, false, false};
Schedule relay2Schedule = {2, 0, 0, 0, 0, false, false};

// Timing variables
unsigned long lastStatusUpdate = 0;
unsigned long lastSmokeUpdate = 0;
unsigned long lastScheduleCheck = 0;

// Function prototypes
void updateRelayInDB(int relay, bool state, String source = "manual");
bool isRelayActivatedByMotion(int relayNumber);
void sendUltrasonicData(int distance, int remainingSeconds = -1);

void setup() {
  Serial.begin(115200);

  // Initialize hardware
  pinMode(RELAY1, OUTPUT);
  pinMode(RELAY2, OUTPUT);
  pinMode(SMOKE_SENSOR, INPUT);
  digitalWrite(RELAY1, HIGH);
  digitalWrite(RELAY2, HIGH);
  pinMode(TRIGGER_PIN, OUTPUT);
  pinMode(ECHO_PIN, INPUT);

  // Connect to WiFi
  connectToWiFi();

  // Configure time
  configTime(8 * 3600, 0, "pool.ntp.org", "time.nist.gov");

  // Setup web server
  setupWebServer();
}

void loop() {
  server.handleClient();
  unsigned long currentMillis = millis();

  // Handle immediate control timeout
  if (immediateControl && currentMillis - immediateControlTimeout >= IMMEDIATE_CONTROL_TIMEOUT) {
    immediateControl = false;
    Serial.println("Returning to normal control mode");
  }
  
  // Update relay states
  if (!immediateControl && currentMillis - lastStatusUpdate >= STATUS_UPDATE_INTERVAL) {
    lastStatusUpdate = currentMillis;
    updateRelayStatesFromDB();
  }
  
  // Check schedules
  if (currentMillis - lastScheduleCheck >= SCHEDULE_CHECK_INTERVAL) {
    lastScheduleCheck = currentMillis;
    checkSchedules();
  }
  
  // Check ultrasonic sensor
  if (currentMillis - lastUltrasonicCheck >= ULTRASONIC_INTERVAL) {
    lastUltrasonicCheck = currentMillis;
    checkUltrasonic();
  }
  if (currentMillis - lastSmokeUpdate >= SMOKE_CHECK_INTERVAL) {
    lastSmokeUpdate = currentMillis;
    checkSmoke();
  }
  // Check WiFi
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi disconnected. Reconnecting...");
    connectToWiFi();
  }
}
String getRelayActivationSource(int relayNumber) {
    if (WiFi.status() != WL_CONNECTED) return "";

    WiFiClient client;
    HTTPClient http;

    String url = String(serverUrl) + "status";
    http.begin(client, url);
    http.addHeader("X-API-KEY", apiKey);
    int httpCode = http.GET();

    if (httpCode == HTTP_CODE_OK) {
        String payload = http.getString();
        DynamicJsonDocument doc(512);
        deserializeJson(doc, payload);

        String sourceKey = "relay" + String(relayNumber) + "_source";
        if (doc.containsKey(sourceKey)) {
            return doc[sourceKey].as<String>();
        }
    }
    http.end();
    return "manual"; // Default to manual if we can't check
}

void checkUltrasonic() {
    static unsigned long lastMeasurement = 0;
    const unsigned long MEASUREMENT_INTERVAL = 200;
    unsigned long currentMillis = millis();
    
    if (currentMillis - lastMeasurement < MEASUREMENT_INTERVAL) return;
    lastMeasurement = currentMillis;

    // Trigger measurement
    digitalWrite(TRIGGER_PIN, LOW);
    delayMicroseconds(4);
    digitalWrite(TRIGGER_PIN, HIGH);
    delayMicroseconds(10);
    digitalWrite(TRIGGER_PIN, LOW);
    
    // Read echo with timeout
    long duration = pulseIn(ECHO_PIN, HIGH, 30000);
    
    if (duration <= 0) {
        return;
    }
    
    int distance = duration * 0.034 / 2;
    
    if (distance > 400) return;
    
    bool motionDetected = (distance > 0 && distance <= ULTRASONIC_THRESHOLD);
    
    if (motionDetected) {
        lastMotionTime = currentMillis;
        
        // Only turn on if not already on or if currently off but not manually controlled
        if (!relay1State || (relay1State && getRelayActivationSource(1) != "manual")) {
            relay1State = true;
            digitalWrite(RELAY1, LOW);
            updateRelayInDB(1, true, "motion");
            Serial.println("Motion detected - light ON");
        }
    }
    
    // Send data to server periodically
    static unsigned long lastCountdownUpdate = 0;
    if (relay1State && (currentMillis - lastCountdownUpdate >= 1000)) {
        lastCountdownUpdate = currentMillis;
        int remaining = (LIGHT_TIMEOUT - (currentMillis - lastMotionTime)) / 1000;
        sendUltrasonicData(distance, max(0, remaining));
    }
}

void sendUltrasonicData(int distance, int remainingSeconds) {
    if (WiFi.status() != WL_CONNECTED) return;

    WiFiClient client;
    HTTPClient http;

    String url = String(serverUrl) + "ultrasonic";
    http.begin(client, url);
    http.addHeader("Content-Type", "application/json");
    http.addHeader("X-API-KEY", apiKey);

    String postData = "{\"distance\":" + String(distance) + 
                     ",\"threshold\":" + String(ULTRASONIC_THRESHOLD);
    
    if (remainingSeconds >= 0) {
        postData += ",\"remaining\":" + String(remainingSeconds);
    }
    
    postData += "}";
    
    int httpCode = http.POST(postData);

    if (httpCode != HTTP_CODE_OK) {
        Serial.printf("Failed to send ultrasonic data. HTTP code: %d\n", httpCode);
    }

    http.end();
}

void connectToWiFi() {
  Serial.print("Connecting to WiFi");
  WiFi.begin(ssid, password);

  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 20) {
    delay(500);
    Serial.print(".");
    attempts++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\nConnected! IP: " + WiFi.localIP().toString());
  } else {
    Serial.println("\nFailed to connect to WiFi. Restarting...");
    ESP.restart();
  }
}

void setupWebServer() {
  server.on("/api/relays/1", HTTP_PUT, handleRelay1Toggle);
  server.on("/api/relays/2", HTTP_PUT, handleRelay2Toggle);
  server.on("/api/status", HTTP_GET, handleStatus);

  server.on("/api", HTTP_OPTIONS, []() {
    sendCORSHeaders();
    server.send(204);
  });

  server.onNotFound([]() {
    if (server.method() == HTTP_OPTIONS) {
      sendCORSHeaders();
      server.send(204);
    } else {
      sendJsonResponse(404, "{\"error\":\"Not found\"}");
    }
  });

  server.begin();
  Serial.println("HTTP server started");
}

void updateRelayStatesFromDB() {
  if (WiFi.status() != WL_CONNECTED) return;

  WiFiClient client;
  HTTPClient http;

  String url = String(serverUrl) + "status";
  http.begin(client, url);
  http.addHeader("X-API-KEY", apiKey);
  int httpCode = http.GET();

  if (httpCode == HTTP_CODE_OK) {
    String payload = http.getString();
    DynamicJsonDocument doc(512);
    deserializeJson(doc, payload);

    bool newRelay1State = doc["relay1"];
    bool newRelay2State = doc["relay2"];

    if (newRelay1State != relay1State) {
      relay1State = newRelay1State;
      digitalWrite(RELAY1, relay1State ? LOW : HIGH);
      Serial.printf("Relay 1 state updated from DB: %s\n", relay1State ? "ON" : "OFF");
    }

    if (newRelay2State != relay2State) {
      relay2State = newRelay2State;
      digitalWrite(RELAY2, relay2State ? LOW : HIGH);
      Serial.printf("Relay 2 state updated from DB: %s\n", relay2State ? "ON" : "OFF");
    }

    // Update schedules if they exist
    if (doc.containsKey("relay1_schedule") && String(doc["relay1_schedule"]) != "Not set") {
      parseSchedule(doc["relay1_schedule"], 1);
    }
    if (doc.containsKey("relay2_schedule") && String(doc["relay2_schedule"]) != "Not set") {
      parseSchedule(doc["relay2_schedule"], 2);
    }
  } else {
    Serial.printf("Failed to get relay states from DB. HTTP code: %d\n", httpCode);
  }

  http.end();
}

void parseSchedule(String scheduleStr, int relayNumber) {
  int toIndex = scheduleStr.indexOf(" to ");
  if (toIndex == -1) return;
  
  String onTimeStr = scheduleStr.substring(0, toIndex);
  String offTimeStr = scheduleStr.substring(toIndex + 4);
  
  if (relayNumber == 1) {
    parseTime(onTimeStr, relay1Schedule.onHour, relay1Schedule.onMinute);
    parseTime(offTimeStr, relay1Schedule.offHour, relay1Schedule.offMinute);
    relay1Schedule.onExecuted = false;
    relay1Schedule.offExecuted = false;
  } else if (relayNumber == 2) {
    parseTime(onTimeStr, relay2Schedule.onHour, relay2Schedule.onMinute);
    parseTime(offTimeStr, relay2Schedule.offHour, relay2Schedule.offMinute);
    relay2Schedule.onExecuted = false;
    relay2Schedule.offExecuted = false;
  }
}

void parseTime(String timeStr, int &hour, int &minute) {
  int spaceIndex = timeStr.indexOf(' ');
  if (spaceIndex == -1) return;
  
  String timePart = timeStr.substring(0, spaceIndex);
  String ampm = timeStr.substring(spaceIndex + 1);
  
  int colonIndex = timePart.indexOf(':');
  if (colonIndex == -1) return;
  
  hour = timePart.substring(0, colonIndex).toInt();
  minute = timePart.substring(colonIndex + 1).toInt();
  
  // Convert to 24-hour format
  if (ampm == "PM" && hour < 12) hour += 12;
  if (ampm == "AM" && hour == 12) hour = 0;
}

void checkSchedules() {
  time_t now = time(nullptr);
  struct tm *timeinfo = localtime(&now);
  int currentHour = timeinfo->tm_hour;
  int currentMinute = timeinfo->tm_min;
  int currentSecond = timeinfo->tm_sec;

  // Reset executed flags at midnight
  if (currentHour == 0 && currentMinute == 0 && currentSecond == 0) {
    relay1Schedule.onExecuted = false;
    relay1Schedule.offExecuted = false;
    relay2Schedule.onExecuted = false;
    relay2Schedule.offExecuted = false;
    Serial.println("Midnight - reset all schedule flags");
  }

  // Check Relay 1 schedule
  if (!relay1Schedule.onExecuted && 
      currentHour == relay1Schedule.onHour && 
      currentMinute == relay1Schedule.onMinute) {
    relay1State = true;
    relay1Schedule.onExecuted = true;
    digitalWrite(RELAY1, LOW);
    updateRelayInDB(1, true, "schedule");
    Serial.println("Relay 1 turned ON by schedule");
  }

  if (!relay1Schedule.offExecuted && 
      currentHour == relay1Schedule.offHour && 
      currentMinute == relay1Schedule.offMinute) {
    relay1State = false;
    relay1Schedule.offExecuted = true;
    digitalWrite(RELAY1, HIGH);
    updateRelayInDB(1, false, "schedule");
    Serial.println("Relay 1 turned OFF by schedule");
  }

  // Check Relay 2 schedule
  if (!relay2Schedule.onExecuted && 
      currentHour == relay2Schedule.onHour && 
      currentMinute == relay2Schedule.onMinute) {
    relay2State = true;
    relay2Schedule.onExecuted = true;
    digitalWrite(RELAY2, LOW);
    updateRelayInDB(2, true, "schedule");
    Serial.println("Relay 2 turned ON by schedule");
  }

  if (!relay2Schedule.offExecuted && 
      currentHour == relay2Schedule.offHour && 
      currentMinute == relay2Schedule.offMinute) {
    relay2State = false;
    relay2Schedule.offExecuted = true;
    digitalWrite(RELAY2, HIGH);
    updateRelayInDB(2, false, "schedule");
    Serial.println("Relay 2 turned OFF by schedule");
  }
}

void checkSmoke() {
  static int lastSmokeValue = -1;
  int smokeValue = analogRead(SMOKE_SENSOR);
  
  // Only send if value changed significantly
  if (abs(smokeValue - lastSmokeValue) > 5 || lastSmokeValue == -1) {
    lastSmokeValue = smokeValue;
    
    if (WiFi.status() == WL_CONNECTED) {
      WiFiClient client;
      HTTPClient http;

      String url = String(serverUrl) + "smoke";
      http.begin(client, url);
      http.addHeader("Content-Type", "application/json");
      http.addHeader("X-API-KEY", apiKey);

      String postData = "{\"value\":" + String(smokeValue) + ",\"threshold\":" + String(SMOKE_THRESHOLD) + "}";
      int httpCode = http.POST(postData);

      if (httpCode != HTTP_CODE_OK) {
        Serial.printf("Failed to send smoke data. HTTP code: %d\n", httpCode);
      }

      http.end();
    }
  }

  if (smokeValue > SMOKE_THRESHOLD) {
    Serial.printf("SMOKE DETECTED! Level: %d (Threshold: %d)\n", smokeValue, SMOKE_THRESHOLD);
  }
}

void handleRelay1Toggle() {
  relay1State = !relay1State;
  digitalWrite(RELAY1, relay1State ? LOW : HIGH);
  
  immediateControl = true;
  immediateControlTimeout = millis();
  
  // Always set source to "manual" when toggled manually
  updateRelayInDB(1, relay1State, "manual");
  
  String response = "{\"status\":\"OK\",\"relay1\":" + String(relay1State ? "true" : "false") + 
                   ",\"relay2\":" + String(relay2State ? "true" : "false") + 
                   ",\"time\":\"" + getTimeWithSeconds() + "\"}";
  sendJsonResponse(200, response);
}

void handleRelay2Toggle() {
  relay2State = !relay2State;
  digitalWrite(RELAY2, relay2State ? LOW : HIGH);
  
  immediateControl = true;
  immediateControlTimeout = millis();
  
  updateRelayInDB(2, relay2State, "manual");
  
  String response = "{\"status\":\"OK\",\"relay1\":" + String(relay1State ? "true" : "false") + 
                   ",\"relay2\":" + String(relay2State ? "true" : "false") + 
                   ",\"time\":\"" + getTimeWithSeconds() + "\"}";
  sendJsonResponse(200, response);
}

bool isRelayActivatedByMotion(int relayNumber) {
    if (WiFi.status() != WL_CONNECTED) return false;

    WiFiClient client;
    HTTPClient http;

    String url = String(serverUrl) + "status";
    http.begin(client, url);
    http.addHeader("X-API-KEY", apiKey);
    int httpCode = http.GET();

    if (httpCode == HTTP_CODE_OK) {
        String payload = http.getString();
        DynamicJsonDocument doc(512);
        deserializeJson(doc, payload);

        if (doc.containsKey("activation_source")) {
            return strcmp(doc["activation_source"], "motion") == 0;
        }
    }
    http.end();
    return false;
}

void updateRelayInDB(int relay, bool state, String source) {
    if (WiFi.status() != WL_CONNECTED) return;

    WiFiClient client;
    HTTPClient http;

    String url = String(serverUrl) + "relays";
    http.begin(client, url);
    http.addHeader("Content-Type", "application/json");
    http.addHeader("X-API-KEY", apiKey);

    String putData = "{\"relay\":" + String(relay) + 
                    ",\"state\":" + String(state ? "1" : "0") + 
                    ",\"source\":\"" + source + "\"}";
    
    http.PUT(putData);
    http.end();
}

void handleStatus() {
  DynamicJsonDocument doc(512);

  doc["relay1"] = relay1State;
  doc["relay2"] = relay2State;
  doc["time"] = getTimeWithSeconds();
  doc["smoke_value"] = analogRead(SMOKE_SENSOR);

  String json;
  serializeJson(doc, json);
  sendJsonResponse(200, json);
}

String getTimeWithSeconds() {
  time_t now = time(nullptr);
  struct tm* timeinfo = localtime(&now);

  char buf[20];
  int hour12 = timeinfo->tm_hour % 12;
  if (hour12 == 0) hour12 = 12;

  sprintf(buf, "%02d:%02d:%02d %s",
          hour12,
          timeinfo->tm_min,
          timeinfo->tm_sec,
          (timeinfo->tm_hour >= 12) ? "PM" : "AM");
        
  return String(buf);
}

void sendJsonResponse(int code, String content) {
  sendCORSHeaders();
  server.send(code, "application/json", content);
}

void sendCORSHeaders() {
  server.sendHeader("Access-Control-Allow-Origin", "*");
  server.sendHeader("Access-Control-Allow-Methods", "GET,POST,PUT,DELETE,OPTIONS");
  server.sendHeader("Access-Control-Allow-Headers", "Content-Type, X-API-KEY");
}