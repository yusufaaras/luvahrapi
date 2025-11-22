<?php
header('Content-Type: application/json; charset=utf-8');
$dbhost = 'localhost';
$dbname = 'vegastak_luvahrdb';
$dbuser = 'vegastak_luvahr';
$dbpass = 'luvahr.2025';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Sadece POST isteği desteklenir']);
    exit;
}

$id = intval($_POST['id'] ?? 0);
if ($id < 1) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'Geçersiz id!']);
    exit;
}
try {
    $pdo = new PDO("mysql:host=$dbhost;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Kaydı bul ve dosya adını al
    $stmt = $pdo->prepare("SELECT stored_filename FROM candidates WHERE id=?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        http_response_code(404);
        echo json_encode(['status'=>'error', 'message'=>'Kayıt bulunamadı']);
        exit;
    }
    $stored_filename = $row['stored_filename'];

    // Veritabanından sil
    $stmt = $pdo->prepare("DELETE FROM candidates WHERE id=?");
    $stmt->execute([$id]);

    // Dosyayı sil
    $filePath = __DIR__ . '/../uploads/cvs/' . $stored_filename;
    if ($stored_filename && file_exists($filePath)) {
        unlink($filePath);
    }

    echo json_encode(['status'=>'success', 'message'=>'Kayıt ve dosya silindi']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>'Sunucu hatası','error'=>$e->getMessage()]);
}