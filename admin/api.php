<?php
// Untuk Debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Panggil file konfigurasi & koneksi PDO
require_once __DIR__ . '/../config.php';

// Fungsi helper untuk response
function api_response($status_code, $message, $data = null) {
    http_response_code($status_code);
    $response = ['message' => $message];
    if ($data !== null) $response['data'] = $data;
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// 1. Validasi Input Dasar
if (!isset($_GET['customer_api_key']) || empty(trim($_GET['customer_api_key']))) {
    api_response(401, "API Key dibutuhkan.");
}
$action = $_GET['action'] ?? '';

// 2. Validasi Customer & Pengaturannya
try {
    $stmt = $pdo->prepare("SELECT id, daily_request_limit, is_active, email_check_server FROM customers WHERE api_key = :api_key LIMIT 1");
    $stmt->execute(['api_key' => trim($_GET['customer_api_key'])]);
    $customer = $stmt->fetch();

    if (!$customer) {
        api_response(403, 'API Key tidak valid.');
    }
    if ($customer['is_active'] != 1) {
        api_response(403, 'Akun Anda tidak aktif.');
    }

    $customerId = $customer['id'];
    $dailyLimit = $customer['daily_request_limit'];
    $server_script = $customer['email_check_server']; // Skrip spesifik untuk user ini

} catch (PDOException $e) {
    error_log("API_GATEWAY_ERROR (customer_check): " . $e->getMessage());
    api_response(500, 'Terjadi kesalahan pada server saat validasi akun.');
}

// 3. Cek Limit Penggunaan
try {
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT request_count FROM customer_api_usage WHERE customer_id = :customer_id AND usage_date = :usage_date LIMIT 1");
    $stmt->execute(['customer_id' => $customerId, 'usage_date' => $today]);
    $usage_today = $stmt->fetch();

    if ($usage_today && $usage_today['request_count'] >= $dailyLimit) {
        api_response(429, 'Limit request harian Anda (' . $dailyLimit . ') telah tercapai.');
    }
} catch (PDOException $e) {
    error_log("API_GATEWAY_ERROR (usage_check): " . $e->getMessage());
    api_response(500, 'Terjadi kesalahan pada server saat memeriksa kuota.');
}

// 4. Catat Penggunaan (SEBELUM Menjalankan Aksi)
try {
    $sql_track = "
        INSERT INTO customer_api_usage (customer_id, usage_date, request_count) 
        VALUES (:customer_id, :usage_date, 1) 
        ON DUPLICATE KEY UPDATE request_count = request_count + 1";
    $stmt = $pdo->prepare($sql_track);
    $stmt->execute(['customer_id' => $customerId, 'usage_date' => $today]);
} catch (PDOException $e) {
    error_log("API_GATEWAY_ERROR (usage_update): " . $e->getMessage());
    api_response(500, 'Terjadi kesalahan pada server saat mencatat penggunaan.');
}

// 5. Routing (Panggil Skrip Aksi yang Tepat)
switch ($action) {
    case 'email_check':
        // Gunakan nama skrip dari pengaturan per-user
        $script_path = __DIR__ . '/actions/' . $server_script;
        if (file_exists($script_path)) {
            // $pdo, $customer, $action bisa diakses di dalam file yang dipanggil
            require_once $script_path;
        } else {
            api_response(500, 'Error: Server pengecekan tidak dikonfigurasi dengan benar untuk akun Anda.');
        }
        break;

    // case 'card_check': ...

    default:
        api_response(400, 'Aksi tidak valid atau tidak ditemukan.');
        break;
}
?>