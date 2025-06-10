<?php
// db_functions.php

// Include file konfigurasi jika Anda membuatnya
// require_once 'db_config.php'; // Sesuaikan path jika perlu

/**
 * Membuat dan mengembalikan koneksi PDO ke database.
 *
 * @return PDO|null Objek PDO jika koneksi berhasil, null jika gagal.
 */
function connectDB() {
    // Jika tidak menggunakan file konfigurasi terpisah, definisikan konstanta di sini
    // atau langsung gunakan string dalam DSN.
    // Contoh jika tidak menggunakan db_config.php:
    // $dbHost = 'localhost';
    // $dbName = 'nama_database_anda';
    // $dbUser = 'username_database_anda';
    // $dbPass = 'password_database_anda';
    // $dbCharset = 'utf8mb4';

    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Aktifkan exceptions untuk error
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Set fetch mode default ke associative array
        PDO::ATTR_EMULATE_PREPARES   => false,                  // Nonaktifkan emulasi prepared statements
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Log error ini ke file atau sistem logging, jangan tampilkan detail error ke user di production
        error_log("Koneksi Database Gagal: " . $e->getMessage());
        // Anda bisa memilih untuk mengembalikan null, atau melempar kembali exception,
        // atau menampilkan pesan error umum.
        // Untuk API, mungkin lebih baik mengembalikan null atau pesan error JSON.
        // die("Koneksi Database Gagal. Silakan coba lagi nanti."); // Hindari die() di production API
        return null;
    }
}

function getPostcodeAnywhereApiKey() {
    $keys_file = 'key.json'; // Path ke file JSON Anda
    if (!file_exists($keys_file)) {
        error_log("File key.json tidak ditemukan.");
        return null;
    }

    $json_data = file_get_contents($keys_file);
    $config = json_decode($json_data, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Gagal mem-parsing key.json: " . json_last_error_msg());
        return null;
    }

    if (empty($config['apis']) || !is_array($config['apis'])) {
        error_log("Format key.json tidak valid atau array 'apis' kosong.");
        return null;
    }

    // Pilih satu API key secara acak dari daftar
    $selected_key_index = array_rand($config['apis']);
    return $config['apis'][$selected_key_index];
}
?>
