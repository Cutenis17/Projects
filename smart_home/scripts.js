
  // Constants
  const API_BASE = "api.php?api=";
  const SMOKE_THRESHOLD = 250;
  const MAX_SMOKE_VALUE = 1000;
let countdownInterval;
const AUTO_OFF_DURATION = 60; 
  
  document.addEventListener("DOMContentLoaded", function () {
      // Create gauge marks and numbers
      createGaugeMarks();
      loadInitialState();
      setInterval(updateStatus, 1000);
  });

  async function loadInitialState() {
      try {
          // Show loading state
          document.getElementById("relay1Sch").textContent = "Loading...";
          document.getElementById("relay2Sch").textContent = "Loading...";

          // Fetch initial state
          const response = await fetch(`${API_BASE}db/status`);
          if (!response.ok) {
              throw new Error("Failed to load initial state");
          }
          const data = await response.json();
          updateUI(data);

          if (data.alert_active) {
              document.getElementById("smokeAlert").style.display = "flex";
          }
      } catch (error) {
          console.error("Error loading initial state:", error);
          // Fall back to default "Not set" if loading fails
          document.getElementById("relay1Sch").textContent = "Not set";
          document.getElementById("relay2Sch").textContent = "Not set";
      }
  }

  function createGaugeMarks() {
      const gaugeBody = document.querySelector(".gauge-body");
      if (!gaugeBody) return;

      // Create marks container if it doesn't exist
      let marksContainer = document.getElementById("gaugeMarks");
      if (!marksContainer) {
          marksContainer = document.createElement("div");
          marksContainer.id = "gaugeMarks";
          marksContainer.style.position = "absolute";
          marksContainer.style.top = "0";
          marksContainer.style.left = "0";
          marksContainer.style.width = "100%";
          marksContainer.style.height = "100%";
      }

      // Create numbers container if it doesn't exist
      let numbersContainer = document.getElementById("gaugeNumbers");
      if (!numbersContainer) {
          numbersContainer = document.createElement("div");
          numbersContainer.id = "gaugeNumbers";
          numbersContainer.style.position = "absolute";
          numbersContainer.style.top = "0";
          numbersContainer.style.left = "0";
          numbersContainer.style.width = "100%";
          numbersContainer.style.height = "100%";
          gaugeBody.appendChild(numbersContainer);
      }

      // Clear existing marks and numbers
      marksContainer.innerHTML = "";
      numbersContainer.innerHTML = "";

      // Create marks every 30 degrees (12 marks total)
      for (let i = 0; i <= 180; i += 30) {
          // Create mark
          const mark = document.createElement("div");
          mark.style.position = "absolute";
          mark.style.width = "2px";
          mark.style.height = "10px";
          mark.style.background = "#666";
          mark.style.left = "50%";
          mark.style.bottom = "0";
          mark.style.transformOrigin = "bottom center";
          mark.style.transform = `rotate(${i - 90}deg) translateY(-90px)`;
          marksContainer.appendChild(mark);

          // Create number (only for every 60 degrees)
          if (i % 60 === 0) {
              const number = document.createElement("div");
              number.style.position = "absolute";
              number.style.fontSize = "0.7rem";
              number.style.color = "#666";
              const value = Math.round((i / 180) * MAX_SMOKE_VALUE);
              number.textContent = value;

              // Position the number
              const angle = ((i - 90) * Math.PI) / 180;
              const radius = 85;
              const x = 50 + radius * Math.cos(angle);
              const y = 50 + radius * Math.sin(angle);
              number.style.left = `${x}%`;
              number.style.top = `${y}%`;

              numbersContainer.appendChild(number);
          }
      }
  }

  let lastToggleTime = 0;
  async function toggleRelay(r) {
      // Save current time before making the request
      const relay1Sch = document.getElementById("relay1Sch").textContent;
      const relay2Sch = document.getElementById("relay2Sch").textContent;
      const currentTime = document.getElementById("currentTime").textContent;
      const now = Date.now();
      if (now - lastToggleTime < 500) return; // 500ms debounce
      lastToggleTime = now;

      try {
        const response = await fetch(`${API_BASE}db/relays`, {
            method: "PUT",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ 
                relay: r,
                source: "manual"  // Add this line
            })
        });

          if (!response.ok) {
              const error = await response.text();
              throw new Error(error);
          }

          const data = await response.json();
          // If no time in response, use the preserved time
          if (!data.relay1_schedule) data.relay1_schedule = relay1Sch;
          if (!data.relay2_schedule) data.relay2_schedule = relay2Sch;
          if (!data.time) data.time = currentTime;
          updateUI(data);
      } catch (error) {
          console.error("Error:", error);
          alert(`Failed to toggle relay: ${error.message}`);
      }
  }

  function validateHourInput(input) {
      const value = parseInt(input.value);
      if (isNaN(value) || value < 1 || value > 12) {
          input.value = "";
          alert("Hour must be between 1 and 12");
          input.focus();
          return false;
      }
      return true;
  }

  function validateMinuteInput(input) {
      const value = parseInt(input.value);
      if (isNaN(value) || value < 0 || value > 59) {
          input.value = "";
          alert("Minute must be between 0 and 59");
          input.focus();
          return false;
      }
      return true;
  }

  async function setSchedule() {
      const data = {
          on_hour: document.getElementById("on_hour").value,
          on_minute: document.getElementById("on_minute").value,
          on_ampm: document.getElementById("on_ampm").value,
          off_hour: document.getElementById("off_hour").value,
          off_minute: document.getElementById("off_minute").value,
          off_ampm: document.getElementById("off_ampm").value,
          relay: document.getElementById("relaySelect").value,
      };

      if (!data.on_hour || !data.on_minute || !data.off_hour || !data.off_minute) {
          alert("Please fill in all time fields");
          return;
      }

      try {
          const response = await fetch(`${API_BASE}db/schedules`, {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify(data),
          });

          if (!response.ok) {
              const error = await response.text();
              throw new Error(error);
          }

          const result = await response.json();

          if (result.relay1_schedule) {
              document.getElementById("relay1Sch").textContent = result.relay1_schedule;
          }
          if (result.relay2_schedule) {
              document.getElementById("relay2Sch").textContent = result.relay2_schedule;
          }

          alert("Schedule set successfully");
          updateStatus();
      } catch (error) {
          console.error("Error:", error);
          alert(`Failed to set schedule: ${error.message}`);
      }
  }

  async function resetAlert() {
      try {
          const response = await fetch(`${API_BASE}db/alerts`, {
              method: "DELETE",
          });

          if (!response.ok) {
              const errorData = await response.json();
              throw new Error(errorData.message || "Failed to reset alert");
          }

          const result = await response.json();

          // Manually hide the alert when reset is clicked
          document.getElementById("smokeAlert").style.display = "none";

          // Update the status to reflect the alert is no longer active
          updateStatus();

          // Show success message if needed
          if (result.message) {
              console.log(result.message);
          }
      } catch (error) {
          console.error("Error:", error);
          alert(`Failed to reset alert: ${error.message}`);
      }
  }

  async function updateStatus() {
    try {
        console.log("Fetching status from:", `${API_BASE}db/status`);
        const response = await fetch(`${API_BASE}db/status`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        console.log("Received data:", data);
        updateUI(data);
        
        
    } catch (error) {
        console.error("Error fetching status:", error);
    }
}

  function updateUI(data) {
      // Update relay buttons
      const relay1Btn = document.getElementById('relay1Btn');
      const relay2Btn = document.getElementById('relay2Btn');
      
      if (data.relay1 !== undefined) {
          const relayState = relay1Btn.querySelector('.relay-state');
          if (relayState) {
              relayState.textContent = data.relay1 ? 'ON' : 'OFF';
          }
          relay1Btn.className = `relay-toggle ${data.relay1 ? 'relay-on' : 'relay-off'}`;
      }
      
      if (data.relay2 !== undefined) {
          const relayState = relay2Btn.querySelector('.relay-state');
          if (relayState) {
              relayState.textContent = data.relay2 ? 'ON' : 'OFF';
          }
          relay2Btn.className = `relay-toggle ${data.relay2 ? 'relay-on' : 'relay-off'}`;
      }
      
     
      if (data.ultrasonic_distance !== undefined) {
        document.getElementById('ultrasonicValue').textContent = data.ultrasonic_distance;
        
        const motionStatus = document.getElementById('motionStatus');
        if (data.motion_detected) {
            motionStatus.textContent = 'Motion detected!';
            motionStatus.style.color = 'var(--primary-color)';
            motionStatus.style.fontWeight = 'bold';
            
            // Flash the relay card that's being controlled
            const relayCard = document.querySelector('.relay-card:nth-child(1)');
            relayCard.style.animation = 'pulse 1s 3';
        } else {
            motionStatus.textContent = 'No motion detected';
            motionStatus.style.color = 'var(--text-light)';
            motionStatus.style.fontWeight = 'normal';
        }
    }

    if (data.relay1 !== undefined && data.seconds_remaining !== undefined) {
        const countdownContainer = document.getElementById('countdownContainer');
        const countdownTimer = document.getElementById('countdownTimer');
        
        if (data.relay1 && data.motion_detected) {
            // Show countdown when light is on
            countdownContainer.style.display = 'block';
            
            // Clear any existing interval
            if (countdownInterval) clearInterval(countdownInterval);
            
            // Initialize countdown
            let secondsRemaining = data.seconds_remaining;
            updateCountdownDisplay(secondsRemaining);
            
            // Start countdown timer
            countdownInterval = setInterval(() => {
                secondsRemaining--;
                updateCountdownDisplay(secondsRemaining);
                
                if (secondsRemaining <= 0) {
                    clearInterval(countdownInterval);
                    countdownContainer.style.display = 'none';
                }
            }, 1000);
        } else {
            // Hide countdown when light is off
            countdownContainer.style.display = 'none';
            if (countdownInterval) clearInterval(countdownInterval);
        }
    }
    
      
      
      // Update schedule display
      if (data.relay1_schedule !== undefined) {
          document.getElementById('relay1Sch').textContent =
              data.relay1_schedule || 'Not set';
      }
      if (data.relay2_schedule !== undefined) {
          document.getElementById('relay2Sch').textContent =
              data.relay2_schedule || 'Not set';
      }
      
      // Update other status elements
      if (data.time !== undefined) {
          document.getElementById('currentTime').textContent = data.time || '--:--:-- --';
      }
      
      // Update smoke level with circular gauge
      if (data.smoke_value !== undefined) {
          const smokeValue = Math.min(data.smoke_value, MAX_SMOKE_VALUE);
          const smokeDisplay = document.getElementById('smokeValue');
          const gaugeFill = document.getElementById('smokeGaugeFill');
          
          // Calculate rotation (0-180 degrees)
          const rotation = (smokeValue / MAX_SMOKE_VALUE) * 180;
          gaugeFill.style.transform = `rotate(${rotation - 90}deg)`;
          
          // Update numeric display
          smokeDisplay.textContent = smokeValue;
          
          // Change color based on value
          if (smokeValue < SMOKE_THRESHOLD * 0.5) {
              smokeDisplay.className = 'gauge-value safe';
          } else if (smokeValue < SMOKE_THRESHOLD) {
              smokeDisplay.className = 'gauge-value warning';
          } else {
              smokeDisplay.className = 'gauge-value danger';
          }
          
          // Only show alert when smoke is detected
          if (smokeValue >= SMOKE_THRESHOLD) {
              document.getElementById('smokeAlert').style.display = 'flex';
          }
      }
  }

  function updateCountdownDisplay(seconds) {
    const countdownTimer = document.getElementById('countdownTimer');
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    
    countdownTimer.textContent = `${mins}:${secs.toString().padStart(2, '0')}`;
    
    // Change color based on remaining time
    if (seconds <= 10) {
        countdownTimer.style.color = 'var(--danger-color)';
    } else if (seconds <= 30) {
        countdownTimer.style.color = 'var(--warning-color)';
    } else {
        countdownTimer.style.color = 'var(--primary-color)';
    }
}

  function confirmLogout(event) {
      event.preventDefault(); // Prevent default link behavior
      const confirmation = confirm("Do you want to logout?");
      if (confirmation) {
          window.location.href = "logout.php"; // Redirect to logout.php if confirmed
      }
  }
