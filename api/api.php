<?php
// Untuk Debugging (HAPUS ATAU KOMENTARI DI PRODUKSI)
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Panggil file konfigurasi database Anda (yang menggunakan MySQLi)
require_once __DIR__ . '/../config.php'; // Path ini harus benar

// Fungsi helper untuk response (tetap sama)
function api_response($status_code, $message, $data = null) {
    http_response_code($status_code);
    $response = ['message' => $message];
    if ($data !== null) $response['data'] = $data;
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}


// ==========================================================
// 1. VALIDASI INPUT DASAR
// ==========================================================
if (!isset($_GET['customer_api_key']) || empty(trim($_GET['customer_api_key']))) {
    api_response(401, "API Key dibutuhkan.");
}
if (!isset($_GET['action']) || empty(trim($_GET['action']))) {
    api_response(400, "Parameter 'action' dibutuhkan.");
}

$customerApiKey = trim($_GET['customer_api_key']);
$action = trim($_GET['action']);


// ==========================================================
// 2. VALIDASI CUSTOMER & PENGATURANNYA (MENGGUNAKAN MySQLi)
// ==========================================================
$sql_customer = "SELECT id, daily_request_limit, is_active, email_check_server FROM customers WHERE api_key = ?";
$stmt_customer = mysqli_prepare($conn, $sql_customer);
mysqli_stmt_bind_param($stmt_customer, "s", $customerApiKey);
mysqli_stmt_execute($stmt_customer);
$result_customer = mysqli_stmt_get_result($stmt_customer);

if (mysqli_num_rows($result_customer) == 0) {
    api_response(403, 'API Key tidak valid.');
}

$customer = mysqli_fetch_assoc($result_customer);
mysqli_stmt_close($stmt_customer);

if ($customer['is_active'] != 1) {
    api_response(403, 'Akun Anda tidak aktif.');
}

$customerId = $customer['id'];
$dailyLimit = $customer['daily_request_limit'];
$server_script = $customer['email_check_server'];


// ==========================================================
// 3. CEK LIMIT PENGGUNAAN (MENGGUNAKAN MySQLi)
// ==========================================================
$today = date('Y-m-d');
$sql_usage = "SELECT request_count FROM customer_api_usage WHERE customer_id = ? AND usage_date = ?";
$stmt_usage = mysqli_prepare($conn, $sql_usage);
mysqli_stmt_bind_param($stmt_usage, "is", $customerId, $today);
mysqli_stmt_execute($stmt_usage);
$result_usage = mysqli_stmt_get_result($stmt_usage);
$usage_today = mysqli_fetch_assoc($result_usage);

if ($usage_today && $usage_today['request_count'] >= $dailyLimit) {
    api_response(429, 'Limit request harian Anda (' . $dailyLimit . ') telah tercapai.');
}
mysqli_stmt_close($stmt_usage);


// ==========================================================
// 4. CATAT PENGGUNAAN (MENGGUNAKAN MySQLi)
// ==========================================================
$sql_track = "
    INSERT INTO customer_api_usage (customer_id, usage_date, request_count) 
    VALUES (?, ?, 1)";
$stmt_track = mysqli_prepare($conn, $sql_track);
mysqli_stmt_bind_param($stmt_track, "is", $customerId, $today);
mysqli_stmt_execute($stmt_track);
mysqli_stmt_close($stmt_track);


// ==========================================================
// 5. ROUTING (PANGGIL SKRIP AKSI YANG TEPAT)
// ==========================================================
switch ($action) {
    case 'email_check':
        $script_path = __DIR__ . '/actions/' . $server_script;
        if (file_exists($script_path)) {
            // $conn (koneksi mysqli) dan variabel lain bisa diakses di file yang di-include
            require_once $script_path;
        } else {
            api_response(500, 'Error: Server pengecekan tidak dikonfigurasi dengan benar untuk akun Anda.');
        }
        break;

    default:
        api_response(400, 'Aksi tidak valid atau tidak ditemukan.');
        break;
}

mysqli_close($conn);
?>