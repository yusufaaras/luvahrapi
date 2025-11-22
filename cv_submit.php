<?php
header('Content-Type: application/json; charset=utf-8');

// Bağlantı için MongoDB PHP extension (mongodb) olmalı!
$mongoUri = "mongodb+srv://yusuf:yusuf123@cluster0.l4tc32y.mongodb.net/luvahr?retryWrites=true&w=majority";
$client = new MongoDB\Driver\Manager($mongoUri);

// Dosya upload için klasörü tanımla
$uploadDir = __DIR__ . '/../uploads/';
if (!file_exists($uploadDir)) mkdir($uploadDir, 0775, true);

$ad = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$telefon = $_POST['phone'] ?? '';
$uzmanlik = $_POST['section_title'] ?? '';

// Dosya yükleme
if (isset($_FILES['cvFile']) && $_FILES['cvFile']['error'] === UPLOAD_ERR_OK) {
    $filename = basename($_FILES["cvFile"]["name"]);
    $targetFile = $uploadDir . uniqid('cv_', true) . '_' . $filename;
    if (move_uploaded_file($_FILES["cvFile"]["tmp_name"], $targetFile)) {
        $filePath = 'uploads/' . basename($targetFile);
    } else {
        http_response_code(500);
        echo json_encode(['message' => 'Dosya yüklenemedi']);
        exit;
    }
} else {
    http_response_code(400);
    echo json_encode(['message' => 'CV dosyası zorunlu']);
    exit;
}

// MongoDB kaydı
$cv_info = [
    'ad' => $ad,
    'email' => $email,
    'telefon' => $telefon,
    'uzmanlik' => $uzmanlik,
    'file_path' => $filePath,
    'created_at' => date('Y-m-d H:i:s'),
];

$bulk = new MongoDB\Driver\BulkWrite;
$bulk->insert($cv_info);

try {
    $client->executeBulkWrite('luvahr.cvs', $bulk);
    echo json_encode(['message' => 'CV başarıyla gönderildi!']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}