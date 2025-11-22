<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// --- Authentication ---
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    http_response_code(401);
    echo json_encode(['status'=>'error','message'=>'Oturum süresi doldu!']); exit;
}

// --- MySQL Ayarları ---
$dbhost = 'localhost';
$dbname = 'vegastak_luvahrdb';
$dbuser = 'vegastak_luvahr';
$dbpass = 'luvahr.2025';

try {
    $pdo = new PDO("mysql:host=$dbhost;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>'DB bağlantı hatası','error'=>$e->getMessage()]); exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
switch($action) {
    case 'list':
        // Filtreler
        $expertise = trim($_POST['expertise'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        $sql = "SELECT * FROM candidates WHERE 1";
        $params = [];
        if($expertise) { $sql .= " AND expertise LIKE ?"; $params[] = "%$expertise%"; }
        if($name) { $sql .= " AND name LIKE ?"; $params[] = "%$name%"; }
        if($email) { $sql .= " AND email LIKE ?"; $params[] = "%$email%"; }
        $sql .= " ORDER BY id DESC LIMIT 500";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['status'=>'success','rows'=>$stmt->fetchAll()]);
        exit;
    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        if(!$id) { echo json_encode(['status'=>'error','message'=>'ID yok']); exit; }
        $stmt = $pdo->prepare("DELETE FROM candidates WHERE id=?");
        $stmt->execute([$id]);
        echo json_encode(['status'=>'success']);
        exit;
    case 'update':
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $expertise = trim($_POST['expertise'] ?? '');
        if(!$id || !$name || !$email) { echo json_encode(['status'=>'error','message'=>'Eksik veri']); exit; }
        $stmt = $pdo->prepare("UPDATE candidates SET name=?, email=?, phone=?, expertise=? WHERE id=?");
        $stmt->execute([$name, $email, $phone, $expertise, $id]);
        echo json_encode(['status'=>'success']);
        exit;
    default:
        echo json_encode(['status'=>'error','message'=>'Geçersiz işlem']); exit;
}