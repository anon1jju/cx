<?php
// validate_email_api.php

// Include file konfigurasi dan fungsi database
require_once 'config.php'; // Jika Anda memisahkannya
require_once 'fungsi.php';
require_once 'randomuser.php';


// --- Dapatkan Koneksi Database ---
$pdo = connectDB();

if (!$pdo) {
    header('Content-Type: application/json');
    http_response_code(503); // Service Unavailable
    echo json_encode(['status' => 'error', 'message' => 'Tidak dapat terhubung ke layanan data. Silakan coba lagi nanti.']);
    exit;
}

// --- Dapatkan API Key untuk PostcodeAnywhere ---
$postcode_anywhere_credentials = getPostcodeAnywhereApiKey();
if (!$postcode_anywhere_credentials || !isset($postcode_anywhere_credentials['api']) || !isset($postcode_anywhere_credentials['site'])) {
    header('Content-Type: application/json');
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Gagal memuat konfigurasi API Key eksternal.']);
    exit;
}
$selected_postcode_api_key = $postcode_anywhere_credentials['api'];
$selected_site_referer = $postcode_anywhere_credentials['site'];
// Anda juga bisa mengambil $randuser dari sini jika ada di JSON, atau tetap statis

// --- 1. Receive Request ---
$user_provided_key = $_GET['user_api_key'] ?? null;
$email_to_validate = $_GET['email'] ?? null;

if (!$user_provided_key || !$email_to_validate) {
    header('Content-Type: application/json');
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'API key atau email tidak ditemukan']);
    exit;
}

try {
    // --- 2. Authenticate User (Sistem Anda) ---
    $stmt = $pdo->prepare("SELECT id, is_active FROM users WHERE system_api_key = :api_key");
    $stmt->bindParam(':api_key', $user_provided_key);
    $stmt->execute();
    $user = $stmt->fetch();

    if (!$user || !$user['is_active']) {
        header('Content-Type: application/json');
        http_response_code(401); // Unauthorized
        echo json_encode(['status' => 'error', 'message' => 'API Key sistem tidak valid atau tidak aktif']);
        exit;
    }
    $user_id = $user['id'];

    // --- 3. Check Rate Limit (Sistem Anda) ---
    $stmt = $pdo->prepare("SELECT COUNT(*) as daily_count FROM api_usage_logs WHERE user_id = :user_id AND DATE(request_timestamp) = CURDATE()");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $usage = $stmt->fetch();

    if ($usage && $usage['daily_count'] >= 15000) {
        header('Content-Type: application/json');
        http_response_code(429); // Too Many Requests
        echo json_encode(['status' => 'error', 'message' => 'Batas permintaan harian sistem telah tercapai']);
        exit;
    }

    // --- 4. Log the Request (Sistem Anda) ---
    $stmt = $pdo->prepare("INSERT INTO api_usage_logs (user_id) VALUES (:user_id)");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    header('Content-Type: application/json');
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan pada server.']);
    exit;
}

// --- 5. Perform Email Validation (Menggunakan key terpilih dari JSON) ---
$ch = curl_init();
$url = "https://services.postcodeanywhere.co.uk/EmailValidation/Interactive/Validate/v2.00/json3ex.ws?Key=" . urlencode($selected_postcode_api_key) . "&Email=" . urlencode($email_to_validate);

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_PROXY, "http://brd.superproxy.io:33335"); // Pastikan proxy ini masih relevan
curl_setopt($ch, CURLOPT_PROXYUSERPWD, "brd-customer-hl_11e62775-zone-datacenter_proxy1:wcnjalokvk2z"); // Pastikan proxy ini masih relevan
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'referer: ' . $selected_site_referer, // Menggunakan site dari JSON
    'priority: u=1, i',
    'origin: ' . $selected_site_referer, // Menggunakan site dari JSON sebagai origin juga
    'user-agent: ' . $randuser,
]);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response_from_service = curl_exec($ch);
$curl_error_msg = null;
$curl_error_no = curl_errno($ch);

if ($curl_error_no) {
    $curl_error_msg = curl_error($ch);
}
curl_close($ch);

// --- 6. Return Response ---
header('Content-Type: application/json');
if ($curl_error_msg) {
    error_log("cURL Error (" . $curl_error_no . ") using API key " . substr($selected_postcode_api_key, 0, 8) . "...: " . $curl_error_msg);
    http_response_code(502); // Bad Gateway
    echo json_encode(['status' => 'error', 'message' => 'Kesalahan saat menghubungi layanan validasi eksternal: ' . $curl_error_msg]);
} else {
    echo $response_from_service;
}
exit;

?>
