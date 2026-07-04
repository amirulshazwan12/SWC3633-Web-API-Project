<?php
// qrcode.php - Mengendalikan Integrasi API Kod QR Pihak Ketiga
header("Content-Type: application/json");
include 'db.php'; // Memastikan keselamatan API Key dan Jaring Error Handling aktif

checkApiKey(); // Pengguna Postman wajib masukkan X-API-KEY anda dulu

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method !== 'POST') {
        http_response_code(405); // Method Not Allowed
        echo json_encode(["success" => false, "message" => "Sila gunakan method POST untuk menjana QR Code."]);
        exit();
    }

    // 1. Ambil data input JSON daripada pelanggan (Postman)
    $data = json_decode(file_get_contents("php://input"), true);

    // Validasi: Pastikan pengguna menghantar teks/URL yang ingin ditukarkan kepada QR
    if (empty($data['text_to_convert'])) {
        http_response_code(400); // Bad Request
        echo json_encode(["success" => false, "message" => "Ralat Validasi: Ruangan 'text_to_convert' diperlukan."]);
        exit();
    }

    $text = urlencode($data['text_to_convert']); // Pastikan teks selamat untuk URL string
    $size = isset($data['size']) ? $data['size'] : '200x200'; // Saiz lalai jika tidak dinyatakan

    // 2. INTEGRASI THIRD-PARTY API
    // Bina URL penuh ke server API pihak ketiga
    $thirdPartyApiUrl = "https://api.qrserver.com/v1/create-qr-code/?size={$size}&data={$text}";

    // 3. Respon Kembali Kepada Pelanggan
    // Kita hantar URL imej QR Code tersebut supaya frontend boleh terus paparkan
    echo json_encode([
        "success" => true,
        "message" => "Kod QR berjaya dijana melalui Third-Party API!",
        "data" => [
            "input_text" => $data['text_to_convert'],
            "qr_image_url" => $thirdPartyApiUrl
        ]
    ]);

} catch (Exception $e) {
    // Jika berlaku apa-apa ralat luar jangka, ia akan ditangkap oleh middleware db.php anda
    jaringException($e);
}
?>