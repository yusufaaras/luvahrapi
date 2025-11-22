<?php
// Admin panelinden CV kayıtlarını çekmek için backend endpoint
header('Content-Type: application/json; charset=utf-8');

// MySQL bağlantı bilgileri (güvenlik için .env veya config dosyasından alınmalı)
$dbhost = 'localhost';
$dbname = 'vegastak_luvahrdb';
$dbuser = 'vegastak_luvahr';
$dbpass = 'luvahr.2025';

try {
    $pdo = new PDO("mysql:host=$dbhost;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Filtreleme için parametre oku
    $where = [];
    $params = [];
    if (!empty($_GET['expertise'])) {
        $where[] = "expertise LIKE ?";
        $params[] = '%' . $_GET['expertise'] . '%';
    }
    if (!empty($_GET['name'])) {
        $where[] = "name LIKE ?";
        $params[] = '%' . $_GET['name'] . '%';
    }
    if (!empty($_GET['email'])) {
        $where[] = "email LIKE ?";
        $params[] = '%' . $_GET['email'] . '%';
    }
    $sql = "SELECT * FROM candidates";
    if (count($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= " ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Dosya url oluştur (URL yolunu kendi sistemine göre değiştir)
    foreach ($rows as &$row) {
        $row['cv_url'] = !empty($row['stored_filename'])
            ? "/uploads/cvs/" . $row['stored_filename']
            : null;
    }

    echo json_encode(['status'=>'success', 'data'=>$rows]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}