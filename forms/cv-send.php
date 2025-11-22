<?php
// Tüm hatalar ve yanıtlar JSON formatında döner
header('Content-Type: application/json; charset=utf-8');

// Hata yakalayıcı
function errorJSON($msg, $extra = [], $code = 500) {
    http_response_code($code);
    echo json_encode(array_merge([
        'status' => 'error',
        'message' => $msg
    ], $extra));
    exit;
}

try {
    // 1. PHP hata ayarları (Debug için açın, yayında kapatın!)
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    // 2. MySQL Bağlantı
    $dbhost = 'localhost';
    $dbname = 'vegastak_luvahrdb';
    $dbuser = 'vegastak_luvahr';
    $dbpass = 'luvahr.2025';

    $dsn = "mysql:host=$dbhost;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbuser, $dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // 3. Gerekli tablo yoksa oluştur
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS candidates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(50) NULL,
            expertise VARCHAR(255) NULL,
            stored_filename VARCHAR(255) NOT NULL,
            original_filename VARCHAR(255) NOT NULL,
            file_size INT NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;
    ");

    // 4. Dosya yükleme dizini
    $uploadDir = __DIR__ . '/../uploads/cvs/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            errorJSON('uploads/cvs klasörü oluşturulamadı', ['dir' => $uploadDir], 500);
        }
    }
    if (!is_writable($uploadDir)) {
        errorJSON('uploads/cvs klasörü yazılamaz!', ['dir' => $uploadDir], 500);
    }

    // 5. CV gönderimi mi, ileti mi? (dosya varsa CV)
    if (isset($_FILES['cvFile']) && $_FILES['cvFile']['error'] !== UPLOAD_ERR_NO_FILE) {
        // CV kaydı
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $expertise = trim($_POST['section_title'] ?? '');

        if ($name === '' || $email === '') {
            errorJSON('Ad ve e‑posta zorunlu!', [], 400);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            errorJSON('Geçerli e‑posta girilmeli!', [], 400);
        }

        $file = $_FILES['cvFile'];
        $allowedExt = ['pdf','doc','docx','rtf','txt'];
        $maxSize = 5 * 1024 * 1024; // 5 MB

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt)) {
            errorJSON('Dosya türü geçerli değil!', ['valid_types'=>implode(', ', $allowedExt)], 400);
        }
        if ($file['size'] > $maxSize) {
            errorJSON('Dosya çok büyük!', ['size'=>$file['size'],'max'=>$maxSize], 400);
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            errorJSON('Dosya yükleme hatası!', ['error'=>$file['error']], 400);
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        // Güvenli dosya adı
        $stored = bin2hex(random_bytes(16)) . '.' . $ext;
        $dest = $uploadDir . $stored;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            errorJSON('Dosya sunucuya kaydedilemedi!', [], 500);
        }

        // SQL ekle
        try {
            $stmt = $pdo->prepare("INSERT INTO candidates (name,email,phone,expertise,stored_filename,original_filename,file_size,mime_type)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $name, $email, $phone, $expertise,
                $stored, $file['name'], $file['size'], $mime
            ]);
        } catch (Exception $e) {
            // Dosyayı geri sil
            if (file_exists($dest)) unlink($dest);
            errorJSON('Veritabanı ekleme hatası', ['sql_error'=>$e->getMessage()], 500);
        }

        echo json_encode([
            'status'=>'success',
            'message'=>'CV başarıyla yüklendi.',
            'filename'=>$file['name'],
            'name'=>$name,
            'email'=>$email,
            'phone'=>$phone,
            'expertise'=>$expertise
        ]);
        exit;

    } else {
        // Sadece ileti formu
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if ($name === '' || $email === '') {
            errorJSON('Ad ve e‑posta zorunlu!', [], 400);
        }

        // (İletişim tablosunun olması gerekirse ekleyebiliriz)
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS messages (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL,
                    subject VARCHAR(255) NULL,
                    message TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB;
            ");
            $stmt = $pdo->prepare("INSERT INTO messages (name,email,subject,message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $subject, $message]);
        } catch (Exception $e) {
            errorJSON('Mesaj kaydedilemedi.', ['sql_error'=>$e->getMessage()], 500);
        }

        echo json_encode([
            'status'=>'success',
            'message'=>'Mesajınız başarıyla gönderildi!',
            'name'=>$name,
            'email'=>$email
        ]);
        exit;
    }

} catch (Exception $e) {
    errorJSON('Sunucu hatası', ['error'=>$e->getMessage()], 500);
}