<?php
require_once "randomuser.php";

// --- Load Configuration ---
$jsonData = file_get_contents('key.json');
if ($jsonData === false) {
    die(json_encode(['status' => 'error', 'response' => 'Error reading']));
}

$config = json_decode($jsonData, true);
if (json_last_error() !== JSON_ERROR_NONE || !isset($config['apis']) || !is_array($config['apis'])) {
    die(json_encode(['status' => 'error', 'response' => 'Invalid configuration']));
}

// --- Load Users ---
$userFile = 'user.json';
$userData = file_get_contents($userFile);

if ($userData === false || empty($userData)) {
    die(json_encode(['status' => 'error', 'response' => 'Error reading user.json or file is empty']));
}

$users = json_decode($userData, true);
if (json_last_error() !== JSON_ERROR_NONE || !isset($users['user']) || !is_array($users['user'])) {
    die(json_encode(['status' => 'error', 'response' => 'Invalid or corrupted format']));
}

// --- Get Parameters ---
header('Content-Type: application/json');

if (!isset($_GET['user']) || empty($_GET['user'])) {
    echo json_encode(['status' => 'error', 'response' => 'User parameter is required']);
    exit();
}
$user = htmlspecialchars($_GET['user']); // Sanitasi input

if (!isset($_GET['email']) || empty($_GET['email'])) {
    echo json_encode(['status' => 'error', 'response' => 'Email parameter is required']);
    exit();
}
$email = filter_var($_GET['email'], FILTER_SANITIZE_EMAIL); // Validasi email

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'response' => 'Invalid email format']);
    exit();
}

// --- Current Date ---
$currentDate = date('Y-m-d');

// --- Validate User ---
$userValid = false;

// Implement flock() for safe file writing
function saveUserData($filePath, $data) {
    $backupFile = $filePath . '.bak';
    copy($filePath, $backupFile); // Create backup before writing
    
    $fp = fopen($filePath, 'c+');
    if ($fp === false) {
        return false;
    }

    if (flock($fp, LOCK_EX)) { // Lock file
        ftruncate($fp, 0); // Clear file
        fwrite($fp, json_encode($data, JSON_PRETTY_PRINT));
        fflush($fp);
        flock($fp, LOCK_UN); // Unlock file
        fclose($fp);
        return true;
    } else {
        fclose($fp);
        return false;
    }
}

foreach ($users['user'] as &$userEntry) {
    if ($userEntry['username'] === $user) {
        $userValid = true;

        // Reset usage if a new day
        if (!isset($userEntry['last_reset']) || $userEntry['last_reset'] !== $currentDate) {
            $userEntry['usage'] = 0;
            $userEntry['last_reset'] = $currentDate;
        }

        // Check usage limit
        if ($userEntry['usage'] >= $userEntry['limit']) {
            echo json_encode(['status' => 'error', 'response' => 'Daily limit exceeded']);
            exit();
        }

        // Increment usage
        $userEntry['usage'] += 1;

        // Save updated data back to user.json
        $success = saveUserData($userFile, $users);
        if (!$success) {
            echo json_encode(['status' => 'error', 'response' => 'Failed to update user data']);
            exit();
        }
        break;
    }
}

if (!$userValid) {
    echo json_encode(['status' => 'error', 'response' => 'Your API key is not valid']);
    exit();
}

// --- Select Random API Key ---
$apis = $config['apis'];
$randomIndex = array_rand($apis);
$selectedApi = $apis[$randomIndex];

$apiKey = $selectedApi['api'];
$site = $selectedApi['site'];

// --- Call Email Validation API ---
$ch = curl_init();
$url = "https://services.postcodeanywhere.co.uk/EmailValidation/Interactive/Validate/v2.00/json3ex.ws?Key=" . urlencode($apiKey) . "&Email=" . urlencode($email);

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_PROXY, "http://brd.superproxy.io:33335");
curl_setopt($ch, CURLOPT_PROXYUSERPWD, "brd-customer-hl_11e62775-zone-datacenter_proxy1:wcnjalokvk2z");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'referer: ' . $site,
    'priority: u=1, i',
    'origin: ' . $site,
    'user-agent: ' . $randuser,
]);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(['status' => 'error', 'message' => 'Request Error: ' . curl_error($ch)]);
    curl_close($ch);
    exit();
}

curl_close($ch);

// --- Final Response ---
echo json_encode([
    'status' => 'success',
    'api' => $apiKey,
    'response' => json_decode($response, true)
], JSON_PRETTY_PRINT);
?>
